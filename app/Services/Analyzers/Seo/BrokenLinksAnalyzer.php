<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;
use DOMElement;

/**
 * Probes outbound links on a page and reports broken ones (404, 410, 5xx, unreachable).
 *
 * Extracts all <a href> links, resolves relative URLs to absolute, deduplicates,
 * then HTTP-probes each one (capped at a configurable limit to avoid timeouts).
 */
class BrokenLinksAnalyzer implements AnalyzerInterface
{
	/** href prefixes that are not HTTP-probeable */
	private const SKIP_PREFIXES = array("javascript:", "mailto:", "tel:", "data:");

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	public function moduleKey(): string
	{
		return "brokenLinks";
	}

	public function label(): string
	{
		return "Broken Links";
	}

	public function category(): string
	{
		return "Graphs, Schema & Links";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.brokenLinks", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$urls = $this->extractProbeableUrls($scanContext);

		if (empty($urls)) {
			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: array(array("type" => "info", "message" => "No outbound links found on this page to check.")),
				recommendations: array(),
			);
		}

		$maxProbes = (int) config("scanning.broken_links_max_probes", 50);
		$probedUrls = array_slice($urls, 0, $maxProbes);
		$probeResults = $this->probeUrls($probedUrls);

		$findings = $this->buildFindings($probeResults, count($urls), $maxProbes);
		$recommendations = $this->buildRecommendations($probeResults);
		$status = $this->resolveStatus($probeResults);

		return new AnalysisResult(
			status: $status,
			findings: $findings,
			recommendations: $recommendations,
		);
	}

	/**
	 * Extract all unique absolute URLs from the page that can be HTTP-probed.
	 *
	 * @return string[]
	 */
	private function extractProbeableUrls(ScanContext $scanContext): array
	{
		$linkNodes = $scanContext->xpath->query("//a[@href]");

		if (!$linkNodes || $linkNodes->length === 0) {
			return array();
		}

		$baseUrl = $scanContext->effectiveUrl;
		$baseHost = strtolower(parse_url($baseUrl, PHP_URL_HOST) ?? "");
		$seenUrls = array();
		$urls = array();

		foreach ($linkNodes as $node) {
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$href = trim($node->getAttribute("href"));

			/* Strip backslashes — never valid in URLs, common in JS/JSON-generated content.
			   chr(92) is the backslash character, avoiding PHP/regex escaping issues. */
			$href = str_replace(chr(92), "", $href);

			if (!$this->isProbeable($href)) {
				continue;
			}

			$absoluteUrl = $this->resolveUrl($href, $baseUrl);

			if ($absoluteUrl === null) {
				continue;
			}

			/* Final backslash cleanup on resolved URL */
			$absoluteUrl = str_replace(chr(92), "", $absoluteUrl);

			/* Strip fragment — we only care about the actual resource */
			$urlWithoutFragment = preg_replace('/#.*$/', "", $absoluteUrl);

			if ($urlWithoutFragment === null || $urlWithoutFragment === "") {
				continue;
			}

			/* Skip same-page links (same host + same path as the current page) */
			$linkHost = strtolower(parse_url($urlWithoutFragment, PHP_URL_HOST) ?? "");
			$linkPath = parse_url($urlWithoutFragment, PHP_URL_PATH) ?? "/";
			$basePath = parse_url($baseUrl, PHP_URL_PATH) ?? "/";

			if ($linkHost === $baseHost && $linkPath === $basePath) {
				continue;
			}

			$normalizedKey = strtolower($urlWithoutFragment);

			if (isset($seenUrls[$normalizedKey])) {
				continue;
			}

			$seenUrls[$normalizedKey] = true;
			$urls[] = $urlWithoutFragment;
		}

		return $urls;
	}

	/**
	 * Determine whether an href value can be HTTP-probed.
	 */
	private function isProbeable(string $href): bool
	{
		if ($href === "" || $href[0] === "#") {
			return false;
		}

		foreach (self::SKIP_PREFIXES as $prefix) {
			if (str_starts_with(strtolower($href), $prefix)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Resolve a potentially relative href to an absolute URL.
	 */
	private function resolveUrl(string $href, string $baseUrl): ?string
	{
		/* Already absolute */
		if (preg_match('#^https?://#i', $href)) {
			return $href;
		}

		$base = parse_url($baseUrl);

		if (!isset($base["scheme"], $base["host"])) {
			return null;
		}

		$root = $base["scheme"] . "://" . $base["host"];

		/* Protocol-relative */
		if (str_starts_with($href, "//")) {
			return $base["scheme"] . ":" . $href;
		}

		/* Root-relative */
		if (str_starts_with($href, "/")) {
			return $root . $href;
		}

		/* Path-relative */
		$basePath = isset($base["path"]) ? dirname($base["path"]) : "/";

		return $root . rtrim($basePath, "/") . "/" . $href;
	}

	/**
	 * Probe each URL concurrently and classify the response.
	 * Uses Guzzle Pool via HttpFetcher for 5-concurrent requests.
	 *
	 * @param string[] $urls
	 * @return array{broken: array, serverErrors: array, unreachable: array, okCount: int}
	 */
	private function probeUrls(array $urls): array
	{
		$broken = array();
		$serverErrors = array();
		$unreachable = array();
		$okCount = 0;

		$keyedUrls = array();
		foreach ($urls as $index => $url) {
			$keyedUrls["link_{$index}"] = $url;
		}

		$results = $this->httpFetcher->fetchResourcesConcurrent($keyedUrls, 5, 5);

		foreach ($urls as $index => $url) {
			$key = "link_{$index}";
			$fetchResult = $results[$key] ?? null;

			if ($fetchResult === null) {
				$unreachable[] = array(
					"url" => $url,
					"reason" => "Probe failed",
				);
				continue;
			}

			$statusCode = $fetchResult->httpStatusCode;

			if ($statusCode === null) {
				$unreachable[] = array(
					"url" => $url,
					"reason" => $fetchResult->errorMessage ?? "Connection failed",
				);
				continue;
			}

			if ($statusCode === 404 || $statusCode === 410) {
				$broken[] = array(
					"url" => $url,
					"statusCode" => $statusCode,
					"reason" => $statusCode === 404 ? "Not Found" : "Gone",
				);
				continue;
			}

			if ($statusCode >= 500) {
				$serverErrors[] = array(
					"url" => $url,
					"statusCode" => $statusCode,
					"reason" => "Server Error ({$statusCode})",
				);
				continue;
			}

			$okCount++;
		}

		return array(
			"broken" => $broken,
			"serverErrors" => $serverErrors,
			"unreachable" => $unreachable,
			"okCount" => $okCount,
		);
	}

	/**
	 * Build findings from probe results.
	 */
	private function buildFindings(array $probeResults, int $totalLinksFound, int $maxProbes): array
	{
		$findings = array();
		$brokenCount = count($probeResults["broken"]);
		$serverErrorCount = count($probeResults["serverErrors"]);
		$unreachableCount = count($probeResults["unreachable"]);
		$probedCount = $brokenCount + $serverErrorCount + $unreachableCount + $probeResults["okCount"];
		$issueCount = $brokenCount + $serverErrorCount + $unreachableCount;

		/* Summary info finding */
		$summaryParts = array("{$probedCount} links checked");
		if ($totalLinksFound > $maxProbes) {
			$summaryParts[] = "{$totalLinksFound} total found (capped at {$maxProbes})";
		}
		$findings[] = array(
			"type" => "info",
			"message" => implode(" — ", $summaryParts) . ". {$probeResults['okCount']} OK, {$issueCount} issue(s).",
		);

		/* Individual broken link findings */
		foreach ($probeResults["broken"] as $link) {
			$findings[] = array(
				"type" => "bad",
				"message" => "Broken link (HTTP {$link['statusCode']}): {$link['url']}",
			);
		}

		foreach ($probeResults["serverErrors"] as $link) {
			$findings[] = array(
				"type" => "warning",
				"message" => "Server error (HTTP {$link['statusCode']}): {$link['url']}",
			);
		}

		foreach ($probeResults["unreachable"] as $link) {
			$findings[] = array(
				"type" => "warning",
				"message" => "Unreachable ({$link['reason']}): {$link['url']}",
			);
		}

		/* Structured data for potential UI rendering */
		$allIssues = array_merge(
			array_map(fn($link) => array("url" => $link["url"], "statusCode" => $link["statusCode"], "reason" => $link["reason"], "severity" => "broken"), $probeResults["broken"]),
			array_map(fn($link) => array("url" => $link["url"], "statusCode" => $link["statusCode"], "reason" => $link["reason"], "severity" => "serverError"), $probeResults["serverErrors"]),
			array_map(fn($link) => array("url" => $link["url"], "statusCode" => null, "reason" => $link["reason"], "severity" => "unreachable"), $probeResults["unreachable"]),
		);

		if (!empty($allIssues)) {
			$findings[] = array("type" => "data", "key" => "brokenLinks", "value" => $allIssues);
		}

		/* Pass finding when everything is clean */
		if (empty($allIssues)) {
			$findings[] = array("type" => "ok", "message" => "All {$probedCount} links are reachable and returning valid responses.");
		}

		return $findings;
	}

	/**
	 * Build actionable recommendations based on the issues found.
	 */
	private function buildRecommendations(array $probeResults): array
	{
		$recommendations = array();

		if (!empty($probeResults["broken"])) {
			$brokenCount = count($probeResults["broken"]);
			$recommendations[] = "Fix or remove {$brokenCount} broken link(s) returning 404/410 errors. Broken links hurt user experience and waste crawl budget.";
			$recommendations[] = "For removed pages, set up 301 redirects to the most relevant alternative page. For external links, find an updated URL or remove the link.";
		}

		if (!empty($probeResults["serverErrors"])) {
			$recommendations[] = "Investigate links returning server errors (5xx). These may indicate temporary issues with external sites, or they could be permanently unreliable destinations.";
		}

		if (!empty($probeResults["unreachable"])) {
			$recommendations[] = "Review unreachable links — these may point to sites with DNS issues, expired SSL certificates, or excessive timeouts. Consider removing or replacing them.";
		}

		return $recommendations;
	}

	/**
	 * Determine the overall module status from probe results.
	 */
	private function resolveStatus(array $probeResults): ModuleStatus
	{
		if (!empty($probeResults["broken"])) {
			return ModuleStatus::Bad;
		}

		if (!empty($probeResults["serverErrors"]) || !empty($probeResults["unreachable"])) {
			return ModuleStatus::Warning;
		}

		return ModuleStatus::Ok;
	}
}
