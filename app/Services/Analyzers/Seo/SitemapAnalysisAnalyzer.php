<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\FetchResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;

class SitemapAnalysisAnalyzer implements AnalyzerInterface
{
	/** Maximum URLs to extract across all sitemaps to prevent memory bloat */
	private const MAX_PARSED_URLS = 1000;

	/** Maximum child sitemaps to fetch from a sitemap index */
	private const MAX_CHILD_SITEMAPS = 50;

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	public function moduleKey(): string
	{
		return "sitemapAnalysis";
	}

	public function label(): string
	{
		return "XML Sitemap";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.sitemapAnalysis", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$robotsTxtContent = $scanContext->robotsTxtContent;
		$findings = array();
		$recommendations = array();
		$sitemapDetails = array("sitemaps" => array(), "total_url_count" => 0, "parsed_urls" => array());

		$sitemapUrls = $this->extractSitemapUrlsFromRobotsTxt($robotsTxtContent);

		if (empty($sitemapUrls)) {
			$defaultSitemapUrl = rtrim($scanContext->domainRoot(), "/") . "/sitemap.xml";
			$fetchResult = $this->httpFetcher->fetchResource($defaultSitemapUrl);

			if ($fetchResult->successful) {
				$findings[] = array("type" => "ok", "message" => "Sitemap found at default location: {$defaultSitemapUrl}");
				$recommendations[] = "Add a Sitemap directive to robots.txt for explicit discovery.";

				$this->parseSitemapFromFetch($fetchResult, $defaultSitemapUrl, $sitemapDetails, $findings);

				$findings[] = $this->buildSitemapDataFinding($sitemapDetails);

				return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
			}

			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: array(array("type" => "warning", "message" => "No sitemap declared in robots.txt and none found at /sitemap.xml.")),
				recommendations: array(
					"Create an XML sitemap and submit it to search engines.",
					"Declare the sitemap location in your robots.txt file.",
				),
			);
		}

		$findings[] = array("type" => "info", "message" => "Found " . count($sitemapUrls) . " sitemap(s) declared in robots.txt.");

		$accessibleCount = 0;
		$inaccessibleCount = 0;

		$keyedSitemapUrls = array();
		foreach ($sitemapUrls as $index => $sitemapUrl) {
			$keyedSitemapUrls["sitemap_{$index}"] = $sitemapUrl;
		}

		$fetchResults = $this->httpFetcher->fetchResourcesConcurrent($keyedSitemapUrls, 5, 5);

		foreach ($sitemapUrls as $index => $sitemapUrl) {
			$fetchResult = $fetchResults["sitemap_{$index}"] ?? null;

			if ($fetchResult !== null && $fetchResult->successful) {
				$accessibleCount++;
				$findings[] = array("type" => "ok", "message" => "Sitemap accessible: {$sitemapUrl}");

				$this->parseSitemapFromFetch($fetchResult, $sitemapUrl, $sitemapDetails, $findings);
			} else {
				$inaccessibleCount++;
				$findings[] = array("type" => "warning", "message" => "Sitemap not accessible: {$sitemapUrl}");

				$sitemapDetails["sitemaps"][] = array(
					"url" => $sitemapUrl,
					"accessible" => false,
					"url_count" => 0,
					"is_index" => false,
				);
			}
		}

		$findings[] = $this->buildSitemapDataFinding($sitemapDetails);

		if ($sitemapDetails["total_url_count"] === 0 && $accessibleCount > 0) {
			$recommendations[] = "Your sitemap is accessible but contains no URLs. Add page URLs to help search engines discover your content.";
		}

		if ($inaccessibleCount > 0) {
			$recommendations[] = "Fix or remove inaccessible sitemap URLs from robots.txt.";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Parse a fetched sitemap response and accumulate results into the shared details structure.
	 */
	private function parseSitemapFromFetch(
		FetchResult $fetchResult,
		string $sitemapUrl,
		array &$sitemapDetails,
		array &$findings,
	): void {
		$xmlContent = $fetchResult->content ?? "";
		$parseResult = $this->parseSitemapContent($xmlContent, $sitemapUrl);

		$sitemapDetails["sitemaps"][] = array(
			"url" => $sitemapUrl,
			"accessible" => true,
			"url_count" => $parseResult["url_count"],
			"is_index" => $parseResult["is_index"],
			"child_count" => $parseResult["child_count"],
		);

		$remainingCapacity = self::MAX_PARSED_URLS - count($sitemapDetails["parsed_urls"]);
		$urlsToAdd = array_slice($parseResult["urls"], 0, max(0, $remainingCapacity));

		$sitemapDetails["parsed_urls"] = array_merge($sitemapDetails["parsed_urls"], $urlsToAdd);
		$sitemapDetails["total_url_count"] += $parseResult["url_count"];

		if ($parseResult["url_count"] > 0) {
			$typeLabel = $parseResult["is_index"] ? "sitemap index" : "sitemap";
			$findings[] = array(
				"type" => "info",
				"message" => "Parsed {$typeLabel}: {$parseResult["url_count"]} URL(s) found in {$sitemapUrl}",
			);
		}
	}

	/**
	 * Parse sitemap XML content, detecting index vs urlset format.
	 * For sitemap indexes, fetches each child sitemap and aggregates URLs.
	 *
	 * @return array{urls: string[], url_count: int, is_index: bool, child_count: int}
	 */
	private function parseSitemapContent(string $xmlContent, string $sitemapUrl): array
	{
		$emptyResult = array("urls" => array(), "url_count" => 0, "is_index" => false, "child_count" => 0);

		if (trim($xmlContent) === "") {
			return $emptyResult;
		}

		$previousErrorState = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xmlContent);
		libxml_clear_errors();
		libxml_use_internal_errors($previousErrorState);

		if ($xml === false) {
			return $emptyResult;
		}

		$rootName = $xml->getName();

		if ($rootName === "sitemapindex") {
			return $this->parseSitemapIndex($xml);
		}

		if ($rootName === "urlset") {
			$urls = $this->extractUrlsFromUrlset($xml);

			return array("urls" => $urls, "url_count" => count($urls), "is_index" => false, "child_count" => 0);
		}

		return $emptyResult;
	}

	/**
	 * Parse a <sitemapindex> element: fetch child sitemaps concurrently and aggregate URLs.
	 *
	 * @return array{urls: string[], url_count: int, is_index: bool, child_count: int}
	 */
	private function parseSitemapIndex(\SimpleXMLElement $xml): array
	{
		$childUrls = $this->extractChildSitemapUrls($xml);
		$childCount = count($childUrls);
		$fetchableChildren = array_slice($childUrls, 0, self::MAX_CHILD_SITEMAPS);

		if (empty($fetchableChildren)) {
			return array("urls" => array(), "url_count" => 0, "is_index" => true, "child_count" => $childCount);
		}

		$keyedUrls = array();
		foreach ($fetchableChildren as $index => $childSitemapUrl) {
			$keyedUrls["child_{$index}"] = $childSitemapUrl;
		}

		$fetchResults = $this->httpFetcher->fetchResourcesConcurrent($keyedUrls, 5, 5);

		$allUrls = array();

		foreach ($fetchableChildren as $index => $childSitemapUrl) {
			if (count($allUrls) >= self::MAX_PARSED_URLS) {
				break;
			}

			$childFetch = $fetchResults["child_{$index}"] ?? null;
			if ($childFetch === null || !$childFetch->successful || empty($childFetch->content)) {
				continue;
			}

			$previousErrorState = libxml_use_internal_errors(true);
			$childXml = simplexml_load_string($childFetch->content);
			libxml_clear_errors();
			libxml_use_internal_errors($previousErrorState);

			if ($childXml === false) {
				continue;
			}

			$pageUrls = $this->extractUrlsFromUrlset($childXml);
			$remaining = self::MAX_PARSED_URLS - count($allUrls);
			$allUrls = array_merge($allUrls, array_slice($pageUrls, 0, $remaining));
		}

		return array(
			"urls" => $allUrls,
			"url_count" => count($allUrls),
			"is_index" => true,
			"child_count" => $childCount,
		);
	}

	/**
	 * Extract page URLs from a <urlset> element, handling XML namespaces.
	 *
	 * @return string[]
	 */
	private function extractUrlsFromUrlset(\SimpleXMLElement $xml): array
	{
		$urls = array();

		$namespaces = $xml->getNamespaces(true);
		$sitemapNs = $namespaces[""] ?? "http://www.sitemaps.org/schemas/sitemap/0.9";

		$xml->registerXPathNamespace("sm", $sitemapNs);
		$locNodes = $xml->xpath("//sm:url/sm:loc");

		if ($locNodes !== false) {
			foreach ($locNodes as $locNode) {
				$url = trim((string) $locNode);
				if ($url !== "") {
					$urls[] = $url;
				}

				if (count($urls) >= self::MAX_PARSED_URLS) {
					break;
				}
			}
		}

		return $urls;
	}

	/**
	 * Extract child sitemap URLs from a <sitemapindex> element.
	 *
	 * @return string[]
	 */
	private function extractChildSitemapUrls(\SimpleXMLElement $xml): array
	{
		$urls = array();

		$namespaces = $xml->getNamespaces(true);
		$sitemapNs = $namespaces[""] ?? "http://www.sitemaps.org/schemas/sitemap/0.9";

		$xml->registerXPathNamespace("sm", $sitemapNs);
		$locNodes = $xml->xpath("//sm:sitemap/sm:loc");

		if ($locNodes !== false) {
			foreach ($locNodes as $locNode) {
				$url = trim((string) $locNode);
				if ($url !== "") {
					$urls[] = $url;
				}
			}
		}

		return $urls;
	}

	/**
	 * Build the structured data finding for the sitemap detail component.
	 */
	private function buildSitemapDataFinding(array $sitemapDetails): array
	{
		return array(
			"type" => "data",
			"key" => "sitemapDetails",
			"value" => $sitemapDetails,
		);
	}

	/**
	 * Extract Sitemap URLs from robots.txt content
	 */
	private function extractSitemapUrlsFromRobotsTxt(?string $robotsTxtContent): array
	{
		if ($robotsTxtContent === null || $robotsTxtContent === "") {
			return array();
		}

		$urls = array();
		$lines = explode("\n", $robotsTxtContent);

		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			if (stripos($trimmedLine, "sitemap:") === 0) {
				$url = trim(substr($trimmedLine, 8));
				if (!empty($url)) {
					$urls[] = $url;
				}
			}
		}

		return $urls;
	}
}
