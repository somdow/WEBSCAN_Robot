<?php

namespace App\Services\Analyzers\WordPress;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;
use App\Services\Scanning\WordPressApiClient;
use DOMElement;

class WpThemeAnalyzer implements AnalyzerInterface
{
	public function __construct(
		private readonly HttpFetcher $httpFetcher,
		private readonly WordPressApiClient $wordPressApiClient,
	) {}

	public function moduleKey(): string
	{
		return "wpTheme";
	}

	public function label(): string
	{
		return "WordPress Theme";
	}

	public function category(): string
	{
		return "WordPress";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.wpTheme", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$effectiveUrl = $scanContext->effectiveUrl;
		$findings = array();
		$recommendations = array();

		$themeData = $this->detectActiveTheme($xpath);

		if ($themeData === null) {
			$findings[] = array("type" => "info", "message" => "No WordPress theme could be detected from the page source.");

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		$activeSlug = $themeData["slug"];
		$detectedVersion = $themeData["version"];

		if ($detectedVersion === null) {
			$detectedVersion = $this->fetchVersionFromStylesheet($effectiveUrl, $activeSlug);
		}

		$apiResult = $this->wordPressApiClient->fetchThemeInfo($activeSlug);
		$themeName = $apiResult["success"] ? ($apiResult["name"] ?? $this->formatSlugAsName($activeSlug)) : $this->formatSlugAsName($activeSlug);
		$isPremium = !$apiResult["success"];
		$latestVersion = $apiResult["success"] ? $apiResult["latest_version"] : null;

		$findings[] = array("type" => "ok", "message" => "Active Theme: {$themeName} (slug: {$activeSlug}).");

		$status = $this->assessThemeVersion($detectedVersion, $latestVersion, $isPremium, $themeName, $findings, $recommendations);
		$this->checkThemeVulnerabilities($activeSlug, $detectedVersion, $themeName, $findings, $recommendations, $status);

		if ($isPremium) {
			$findings[] = array("type" => "info", "message" => "This theme is not listed on WordPress.org — likely a premium or custom theme.");
		}

		$this->reportAdditionalThemes($themeData["allSlugs"], $activeSlug, $findings);

		$findings[] = array("type" => "data", "key" => "themeDetails", "value" => array(
			"slug" => $activeSlug,
			"name" => $themeName,
			"detected_version" => $detectedVersion,
			"latest_version" => $latestVersion,
			"is_premium" => $isPremium,
		));

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Detect the active theme slug and version from wp-content/themes/ paths in HTML.
	 */
	private function detectActiveTheme(\DOMXPath $xpath): ?array
	{
		$slugCounts = array();
		$slugVersions = array();

		$urlQueries = array(
			array("query" => "//link[contains(@href, '/wp-content/themes/')]", "attr" => "href"),
			array("query" => "//script[contains(@src, '/wp-content/themes/')]", "attr" => "src"),
		);

		foreach ($urlQueries as $queryDef) {
			$nodes = $xpath->query($queryDef["query"]);
			if (!$nodes) {
				continue;
			}

			for ($nodeIndex = 0; $nodeIndex < $nodes->length; $nodeIndex++) {
				$node = $nodes->item($nodeIndex);
				if (!($node instanceof DOMElement)) {
					continue;
				}

				$url = $node->getAttribute($queryDef["attr"]);
				if (!preg_match("/\/wp-content\/themes\/([a-zA-Z0-9_-]+)\//", $url, $slugMatch)) {
					continue;
				}

				$slug = strtolower($slugMatch[1]);
				$slugCounts[$slug] = ($slugCounts[$slug] ?? 0) + 1;

				if (preg_match("/[?&]ver=([\d.]+)/", $url, $verMatch)) {
					$slugVersions[$slug][] = $verMatch[1];
				}
			}
		}

		if (empty($slugCounts)) {
			return null;
		}

		arsort($slugCounts);
		$activeSlug = array_key_first($slugCounts);
		$detectedVersion = null;

		if (!empty($slugVersions[$activeSlug])) {
			$versionCounts = array_count_values($slugVersions[$activeSlug]);
			arsort($versionCounts);
			$detectedVersion = array_key_first($versionCounts);
		}

		return array(
			"slug" => $activeSlug,
			"version" => $detectedVersion,
			"allSlugs" => array_keys($slugCounts),
		);
	}

	/**
	 * Fetch the theme's style.css and extract the Version: header line.
	 */
	private function fetchVersionFromStylesheet(string $effectiveUrl, string $themeSlug): ?string
	{
		$urlParts = parse_url($effectiveUrl);
		if (!$urlParts || !isset($urlParts["scheme"]) || !isset($urlParts["host"])) {
			return null;
		}

		$stylesheetUrl = $urlParts["scheme"] . "://" . $urlParts["host"]
			. "/wp-content/themes/" . urlencode($themeSlug) . "/style.css";

		$fetchResponse = $this->httpFetcher->fetchResource($stylesheetUrl);

		if (!$fetchResponse->successful || $fetchResponse->content === null) {
			return null;
		}

		$headerSection = substr($fetchResponse->content, 0, 2000);

		if (preg_match("/^\s*Version:\s*([\d.]+)/mi", $headerSection, $versionMatch)) {
			return $versionMatch[1];
		}

		return null;
	}

	/**
	 * Compare detected version against the latest available version.
	 */
	private function assessThemeVersion(
		?string $detectedVersion,
		?string $latestVersion,
		bool $isPremium,
		string $themeName,
		array &$findings,
		array &$recommendations,
	): ModuleStatus {
		if ($detectedVersion === null) {
			$findings[] = array("type" => "info", "message" => "Theme version could not be determined.");
			return ModuleStatus::Ok;
		}

		if ($latestVersion === null) {
			$message = "Theme Version: {$detectedVersion}";
			$message .= $isPremium
				? " — Premium/custom theme, version comparison not available."
				: " — Could not verify latest version.";
			$findings[] = array("type" => "info", "message" => $message);
			return ModuleStatus::Ok;
		}

		if (version_compare($detectedVersion, $latestVersion) >= 0) {
			$findings[] = array("type" => "ok", "message" => "Theme Version: {$detectedVersion} — Up to date (latest: {$latestVersion}).");
			return ModuleStatus::Ok;
		}

		$findings[] = array("type" => "warning", "message" => "Theme Version: {$detectedVersion} — Outdated (latest: {$latestVersion}).");
		$recommendations[] = "Update theme {$themeName} from {$detectedVersion} to {$latestVersion} for security patches and compatibility.";

		return ModuleStatus::Warning;
	}

	/**
	 * Check the active theme against WPVulnerability.net for known CVEs.
	 */
	private function checkThemeVulnerabilities(
		string $themeSlug,
		?string $detectedVersion,
		string $themeName,
		array &$findings,
		array &$recommendations,
		ModuleStatus &$status,
	): void {
		if ($detectedVersion === null) {
			return;
		}

		$vulnResult = $this->wordPressApiClient->fetchThemeVulnerabilities($themeSlug, $detectedVersion);

		if (!$vulnResult["success"] || $vulnResult["affecting_version"] === 0) {
			return;
		}

		$vulnCount = $vulnResult["affecting_version"];
		$status = ModuleStatus::Bad;

		$findings[] = array(
			"type" => "bad",
			"message" => "Theme {$themeName} version {$detectedVersion} has {$vulnCount} known security "
				. ($vulnCount !== 1 ? "vulnerabilities" : "vulnerability") . " affecting this version.",
		);

		$recommendations[] = "Security Alert: Theme {$themeName} version {$detectedVersion} has known vulnerabilities. Update immediately.";

		$findings[] = array(
			"type" => "data",
			"key" => "themeVulnerabilities",
			"value" => $vulnResult["vulnerabilities"],
		);
	}

	/**
	 * Report any additional theme slugs detected (possible child/parent relationship).
	 */
	private function reportAdditionalThemes(array $allSlugs, string $activeSlug, array &$findings): void
	{
		$otherSlugs = array_filter($allSlugs, fn(string $slug) => $slug !== $activeSlug);

		if (empty($otherSlugs)) {
			return;
		}

		$findings[] = array(
			"type" => "info",
			"message" => "Additional theme reference(s) detected: " . implode(", ", $otherSlugs)
				. ". This may indicate a child/parent theme relationship.",
		);
	}

	/**
	 * Convert a theme slug to a human-readable name.
	 */
	private function formatSlugAsName(string $slug): string
	{
		return ucwords(str_replace(array("-", "_"), " ", $slug));
	}
}
