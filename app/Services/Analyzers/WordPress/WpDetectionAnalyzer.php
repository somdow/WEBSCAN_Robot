<?php

namespace App\Services\Analyzers\WordPress;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;
use App\Services\Scanning\WhatCmsClient;
use App\Services\Scanning\WordPressApiClient;
use DOMElement;

class WpDetectionAnalyzer implements AnalyzerInterface
{
	public function __construct(
		private readonly HttpFetcher $httpFetcher,
		private readonly WordPressApiClient $wordPressApiClient,
		private readonly WhatCmsClient $whatCmsClient,
	) {}

	public function moduleKey(): string
	{
		return "wpDetection";
	}

	public function label(): string
	{
		return "WordPress Detection";
	}

	public function category(): string
	{
		return "WordPress";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.wpDetection", 0);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$htmlContent = $scanContext->htmlContent;
		$effectiveUrl = $scanContext->effectiveUrl;
		$findings = array();
		$recommendations = array();
		$detectedVersion = null;

		$apiDetection = $this->detectViaWhatCmsApi($effectiveUrl);
		if ($apiDetection["attempted"]) {
			if ($apiDetection["detectedVersion"] !== null) {
				$detectedVersion = $apiDetection["detectedVersion"];
			}
			if (!empty($apiDetection["techStack"])) {
				$findings[] = array("type" => "data", "key" => "techStack", "value" => $apiDetection["techStack"]);
			}
		}

		$hasHtmlSignals = $this->detectWordPressSignals($xpath, $htmlContent, $detectedVersion);

		$rssFeedVersion = $this->fetchVersionFromRssFeed($effectiveUrl);
		if ($rssFeedVersion !== null) {
			$detectedVersion = $rssFeedVersion;
		}

		$isWordPress = $apiDetection["isWordPress"] || $hasHtmlSignals || $rssFeedVersion !== null;

		if (!$isWordPress) {
			$findings[] = array("type" => "data", "key" => "isWordPress", "value" => false);
			$findings[] = array("type" => "data", "key" => "detectionMethod", "value" => null);
			$findings[] = array("type" => "info", "message" => "This site does not appear to be running WordPress.");

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		$detectionMethod = match (true) {
			$apiDetection["isWordPress"] => "whatcms_api",
			$hasHtmlSignals => "html_signals",
			$rssFeedVersion !== null => "rss_feed",
			default => "html_signals",
		};

		$findings[] = array("type" => "data", "key" => "isWordPress", "value" => true);
		$findings[] = array("type" => "data", "key" => "detectionMethod", "value" => $detectionMethod);
		$findings[] = array("type" => "ok", "message" => "This site is running WordPress.");

		$status = $this->assessCoreVersion($detectedVersion, $findings, $recommendations);
		$this->checkCoreVulnerabilities($detectedVersion, $findings, $recommendations, $status);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Attempt CMS detection via WhatCMS.org API.
	 * Returns early with attempted=false if the API key is not configured.
	 */
	private function detectViaWhatCmsApi(string $effectiveUrl): array
	{
		$emptyResult = array(
			"attempted" => false,
			"isWordPress" => false,
			"detectedVersion" => null,
			"techStack" => array(),
		);

		if (!$this->whatCmsClient->isConfigured()) {
			return $emptyResult;
		}

		$apiResult = $this->whatCmsClient->detectTechStack($effectiveUrl);

		if (!$apiResult["success"]) {
			return array_merge($emptyResult, array("attempted" => true));
		}

		return array(
			"attempted" => true,
			"isWordPress" => $apiResult["isWordPress"],
			"detectedVersion" => $apiResult["detectedVersion"],
			"techStack" => $apiResult["techStack"],
		);
	}

	/**
	 * Detect WordPress presence via wp-content, wp-includes, and meta generator in HTML.
	 * Updates $detectedVersion by reference when a version is found.
	 * Returns true if any WordPress signal is detected.
	 */
	private function detectWordPressSignals(\DOMXPath $xpath, string $htmlContent, ?string &$detectedVersion): bool
	{
		$detected = false;

		$wpContentNodes = $xpath->query(
			"//link[contains(@href, '/wp-content/')]"
			. " | //script[contains(@src, '/wp-content/')]"
			. " | //img[contains(@src, '/wp-content/')]"
		);
		if ($wpContentNodes && $wpContentNodes->length > 0) {
			$detected = true;
		}

		$wpIncludesNodes = $xpath->query(
			"//link[contains(@href, '/wp-includes/')]"
			. " | //script[contains(@src, '/wp-includes/')]"
		);
		if ($wpIncludesNodes && $wpIncludesNodes->length > 0) {
			$detected = true;
			$versionFromIncludes = $this->extractVersionFromWpIncludes($xpath);
			if ($versionFromIncludes !== null) {
				$detectedVersion = $versionFromIncludes;
			}
		}

		$generatorNodes = $xpath->query("//meta[@name='generator']");
		if ($generatorNodes) {
			for ($nodeIndex = 0; $nodeIndex < $generatorNodes->length; $nodeIndex++) {
				$genNode = $generatorNodes->item($nodeIndex);
				if (!($genNode instanceof DOMElement)) {
					continue;
				}
				$genContent = $genNode->getAttribute("content");
				if (preg_match("/WordPress\s+([\d.]+)/i", $genContent, $wpVersionMatch)) {
					$detected = true;
					if ($detectedVersion === null) {
						$detectedVersion = $wpVersionMatch[1];
					}
				}
			}
		}

		return $detected;
	}

	/**
	 * Extract the most common version string from ?ver= parameters on wp-includes assets.
	 */
	private function extractVersionFromWpIncludes(\DOMXPath $xpath): ?string
	{
		$versionCounts = array();

		$queries = array(
			array("query" => "//link[contains(@href, '/wp-includes/')]", "attr" => "href"),
			array("query" => "//script[contains(@src, '/wp-includes/')]", "attr" => "src"),
		);

		foreach ($queries as $queryDef) {
			$nodes = $xpath->query($queryDef["query"]);
			if (!$nodes) {
				continue;
			}
			for ($nodeIndex = 0; $nodeIndex < $nodes->length; $nodeIndex++) {
				$node = $nodes->item($nodeIndex);
				if (!($node instanceof DOMElement)) {
					continue;
				}
				$attrValue = $node->getAttribute($queryDef["attr"]);
				if (preg_match("/[?&]ver=([\d.]+)/", $attrValue, $verMatch)) {
					$ver = $verMatch[1];
					$versionCounts[$ver] = ($versionCounts[$ver] ?? 0) + 1;
				}
			}
		}

		if (empty($versionCounts)) {
			return null;
		}

		arsort($versionCounts);

		return array_key_first($versionCounts);
	}

	/**
	 * Fetch the site's RSS feed and extract the WordPress core version from the generator tag.
	 */
	private function fetchVersionFromRssFeed(string $effectiveUrl): ?string
	{
		$urlParts = parse_url($effectiveUrl);
		if (!$urlParts || !isset($urlParts["scheme"]) || !isset($urlParts["host"])) {
			return null;
		}

		$feedUrl = $urlParts["scheme"] . "://" . $urlParts["host"] . "/feed/";
		$fetchResponse = $this->httpFetcher->fetchResource($feedUrl);

		if (!$fetchResponse->successful || $fetchResponse->content === null) {
			return null;
		}

		if (preg_match("/<generator>https?:\/\/wordpress\.org\/\?v=([\d.]+)<\/generator>/i", $fetchResponse->content, $match)) {
			return $match[1];
		}

		return null;
	}

	/**
	 * Compare detected core version against the latest available version.
	 */
	private function assessCoreVersion(?string $detectedVersion, array &$findings, array &$recommendations): ModuleStatus
	{
		if ($detectedVersion === null) {
			$findings[] = array("type" => "data", "key" => "coreDetails", "value" => array(
				"detected_version" => null,
				"latest_version" => null,
				"version_status" => "unknown",
			));

			return ModuleStatus::Ok;
		}

		$coreApiResult = $this->wordPressApiClient->fetchLatestCoreVersion();
		$latestVersion = ($coreApiResult["success"]) ? $coreApiResult["latest_version"] : null;

		if ($latestVersion === null) {
			$findings[] = array("type" => "data", "key" => "coreDetails", "value" => array(
				"detected_version" => $detectedVersion,
				"latest_version" => null,
				"version_status" => "unknown",
			));

			return ModuleStatus::Ok;
		}

		$isUpToDate = version_compare($detectedVersion, $latestVersion) >= 0;

		$findings[] = array("type" => "data", "key" => "coreDetails", "value" => array(
			"detected_version" => $detectedVersion,
			"latest_version" => $latestVersion,
			"version_status" => $isUpToDate ? "current" : "outdated",
		));

		if (!$isUpToDate) {
			$recommendations[] = "Update WordPress core from {$detectedVersion} to {$latestVersion} for security patches, bug fixes, and compatibility.";

			return ModuleStatus::Warning;
		}

		return ModuleStatus::Ok;
	}

	/**
	 * Check the detected core version against WPVulnerability.net for known CVEs.
	 */
	private function checkCoreVulnerabilities(?string $detectedVersion, array &$findings, array &$recommendations, ModuleStatus &$status): void
	{
		if ($detectedVersion === null) {
			return;
		}

		$coreVulnResult = $this->wordPressApiClient->fetchCoreVulnerabilities($detectedVersion);

		if (!$coreVulnResult["success"] || $coreVulnResult["affecting_version"] === 0) {
			return;
		}

		$vulnCount = $coreVulnResult["affecting_version"];
		$status = ModuleStatus::Bad;

		$findings[] = array(
			"type" => "bad",
			"message" => "WordPress Core {$detectedVersion} has {$vulnCount} known security "
				. ($vulnCount !== 1 ? "vulnerabilities" : "vulnerability") . " affecting this version.",
		);
		$findings[] = array("type" => "data", "key" => "coreVulnerabilities", "value" => $coreVulnResult["vulnerabilities"]);

		$recommendations[] = "Critical: WordPress core {$detectedVersion} has {$vulnCount} known "
			. ($vulnCount !== 1 ? "vulnerabilities" : "vulnerability")
			. ". Update immediately to protect against known exploits.";
	}
}
