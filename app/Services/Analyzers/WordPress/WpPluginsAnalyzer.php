<?php

namespace App\Services\Analyzers\WordPress;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\WordPressApiClient;
use DOMElement;

class WpPluginsAnalyzer implements AnalyzerInterface
{
	public function __construct(
		private readonly WordPressApiClient $wordPressApiClient,
	) {}

	public function moduleKey(): string
	{
		return "wpPlugins";
	}

	public function label(): string
	{
		return "WordPress Plugins";
	}

	public function category(): string
	{
		return "WordPress";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.wpPlugins", 7);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$htmlContent = $scanContext->htmlContent;
		$findings = array();
		$recommendations = array();
		$maxApiLookups = (int) config("scanning.max_plugin_api_lookups", 15);

		$pluginRegistry = array();
		$this->collectPluginsFromUrlAttributes($xpath, $pluginRegistry);
		$this->collectYoastFromHtmlComment($htmlContent, $pluginRegistry);
		$this->collectPluginsFromMetaGenerators($xpath, $pluginRegistry);

		$detectedPlugins = $this->buildDetectedPlugins($pluginRegistry);
		$pluginCount = count($detectedPlugins);

		if ($pluginCount === 0) {
			$findings[] = array("type" => "info", "message" => "No WordPress plugins could be detected from the page source.");

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		$lookupMetrics = $this->performApiLookups($detectedPlugins, $maxApiLookups);

		$findings[] = array("type" => "info", "message" => "Detected {$pluginCount} plugin(s) from the page source.");
		$findings[] = array("type" => "data", "key" => "detectedPlugins", "value" => array_values($detectedPlugins));

		$status = $this->buildStatusFindings($detectedPlugins, $lookupMetrics, $findings, $recommendations);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Scan link/script/img attributes for /wp-content/plugins/SLUG/ paths and extract versions.
	 */
	private function collectPluginsFromUrlAttributes(\DOMXPath $xpath, array &$pluginRegistry): void
	{
		$urlQueries = array(
			array("query" => "//link[contains(@href, '/wp-content/plugins/')]", "attr" => "href"),
			array("query" => "//script[contains(@src, '/wp-content/plugins/')]", "attr" => "src"),
			array("query" => "//img[contains(@src, '/wp-content/plugins/')]", "attr" => "src"),
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
				if (!preg_match("/\/wp-content\/plugins\/([a-zA-Z0-9_-]+)\//", $url, $slugMatch)) {
					continue;
				}

				$slug = strtolower($slugMatch[1]);
				$this->ensureRegistryEntry($pluginRegistry, $slug, "url-path");

				if (preg_match("/[?&]ver=([\d.]+)/", $url, $verMatch)) {
					$pluginRegistry[$slug]["versions"][] = $verMatch[1];
				}
			}
		}
	}

	/**
	 * Detect Yoast SEO from its HTML comment signature.
	 */
	private function collectYoastFromHtmlComment(string $htmlContent, array &$pluginRegistry): void
	{
		if (preg_match("/<!--\s*This site is optimized with the Yoast SEO.*?v([\d.]+)/i", $htmlContent, $yoastMatch)) {
			$this->ensureRegistryEntry($pluginRegistry, "wordpress-seo", "html-comment");
			$pluginRegistry["wordpress-seo"]["versions"][] = $yoastMatch[1];
		}
	}

	/**
	 * Check meta generator tags for known plugin-specific patterns.
	 */
	private function collectPluginsFromMetaGenerators(\DOMXPath $xpath, array &$pluginRegistry): void
	{
		$generatorNodes = $xpath->query("//meta[@name='generator']");
		if (!$generatorNodes) {
			return;
		}

		$generatorPatterns = array(
			"/Powered by Slider Revolution\s+([\d.]+)/i" => "revslider",
			"/Starter Templates\s*v([\d.]+)/i" => "starter-templates",
			"/Starter Sites\s*v([\d.]+)/i" => "starter-sites",
			"/Site Kit by Google\s+([\d.]+)/i" => "google-site-kit",
			"/Flavor\s+([\d.]+)/i" => "flavor",
		);

		for ($nodeIndex = 0; $nodeIndex < $generatorNodes->length; $nodeIndex++) {
			$genNode = $generatorNodes->item($nodeIndex);
			if (!($genNode instanceof DOMElement)) {
				continue;
			}

			$genContent = $genNode->getAttribute("content");
			if (empty($genContent) || preg_match("/^WordPress/i", $genContent)) {
				continue;
			}

			foreach ($generatorPatterns as $pattern => $slug) {
				if (preg_match($pattern, $genContent, $genMatch)) {
					$this->ensureRegistryEntry($pluginRegistry, $slug, "meta-generator");
					$pluginRegistry[$slug]["versions"][] = $genMatch[1];
					break;
				}
			}
		}
	}

	/**
	 * Initialize a plugin registry entry if it does not already exist.
	 */
	private function ensureRegistryEntry(array &$pluginRegistry, string $slug, string $source): void
	{
		if (!isset($pluginRegistry[$slug])) {
			$pluginRegistry[$slug] = array("versions" => array(), "sources" => array());
		}

		if (!in_array($source, $pluginRegistry[$slug]["sources"], true)) {
			$pluginRegistry[$slug]["sources"][] = $source;
		}
	}

	/**
	 * Transform the raw registry into a structured detected plugins array.
	 */
	private function buildDetectedPlugins(array $pluginRegistry): array
	{
		$detectedPlugins = array();

		foreach ($pluginRegistry as $slug => $registryEntry) {
			$detectedVersion = $this->determineBestVersion($registryEntry["versions"]);

			$detectedPlugins[$slug] = array(
				"slug" => $slug,
				"name" => $this->formatSlugAsName($slug),
				"detected_version" => $detectedVersion,
				"latest_version" => null,
				"version_status" => "unknown",
				"is_premium" => false,
				"vulnerabilities_count" => 0,
				"vulnerabilities" => array(),
			);
		}

		return $detectedPlugins;
	}

	/**
	 * Perform WordPress.org API and vulnerability lookups for each detected plugin.
	 */
	private function performApiLookups(array &$detectedPlugins, int $maxLookups): array
	{
		$lookupCount = 0;
		$skippedLookups = 0;

		foreach ($detectedPlugins as $slug => &$pluginData) {
			if ($lookupCount >= $maxLookups) {
				$skippedLookups++;
				continue;
			}

			$apiResult = $this->wordPressApiClient->fetchPluginInfo($slug);
			$lookupCount++;

			if ($apiResult["success"]) {
				$pluginData["name"] = $apiResult["name"] ?? $pluginData["name"];
				$pluginData["latest_version"] = $apiResult["latest_version"];

				if ($pluginData["detected_version"] !== null && $apiResult["latest_version"] !== null) {
					$pluginData["version_status"] = version_compare($pluginData["detected_version"], $apiResult["latest_version"]) >= 0
						? "current"
						: "outdated";
				}
			} else {
				$pluginData["is_premium"] = true;
			}

			$vulnResult = $this->wordPressApiClient->fetchPluginVulnerabilities($slug, $pluginData["detected_version"]);
			if ($vulnResult["success"] && $vulnResult["affecting_version"] > 0) {
				$pluginData["vulnerabilities_count"] = $vulnResult["affecting_version"];
				$pluginData["vulnerabilities"] = $vulnResult["vulnerabilities"];
			}
		}
		unset($pluginData);

		return array("skipped" => $skippedLookups);
	}

	/**
	 * Build findings and recommendations based on plugin analysis results.
	 */
	private function buildStatusFindings(array $detectedPlugins, array $lookupMetrics, array &$findings, array &$recommendations): ModuleStatus
	{
		$vulnerablePlugins = array();
		$outdatedOnlyPlugins = array();
		$premiumCount = 0;
		$unknownVersionCount = 0;

		foreach ($detectedPlugins as $pluginData) {
			if ($pluginData["vulnerabilities_count"] > 0) {
				$vulnerablePlugins[] = $pluginData;
			} elseif ($pluginData["version_status"] === "outdated") {
				$outdatedOnlyPlugins[] = $pluginData;
			}

			if ($pluginData["is_premium"]) {
				$premiumCount++;
			}
			if ($pluginData["detected_version"] === null) {
				$unknownVersionCount++;
			}
		}

		$status = ModuleStatus::Ok;

		if (!empty($vulnerablePlugins)) {
			$status = ModuleStatus::Bad;
			$vulnerableNames = array_map(
				fn(array $pluginEntry) => "{$pluginEntry["name"]} ({$pluginEntry["vulnerabilities_count"]} CVE(s))",
				$vulnerablePlugins,
			);
			$findings[] = array(
				"type" => "bad",
				"message" => count($vulnerablePlugins) . " plugin(s) have known security vulnerabilities: " . implode(", ", $vulnerableNames) . ".",
			);
			$recommendations[] = "Security Alert: Update vulnerable plugins immediately: " . implode(", ", $vulnerableNames) . ".";
		}

		if (!empty($outdatedOnlyPlugins)) {
			if ($status !== ModuleStatus::Bad) {
				$status = ModuleStatus::Warning;
			}
			$outdatedNames = array_map(fn(array $pluginEntry) => $pluginEntry["name"], $outdatedOnlyPlugins);
			$findings[] = array(
				"type" => "warning",
				"message" => count($outdatedOnlyPlugins) . " plugin(s) appear to be outdated: " . implode(", ", $outdatedNames) . ".",
			);
			$recommendations[] = "Update outdated plugins: " . implode(", ", $outdatedNames) . ".";
		}

		if (empty($vulnerablePlugins) && empty($outdatedOnlyPlugins)) {
			$findings[] = array("type" => "ok", "message" => "No outdated or vulnerable plugins detected.");
		}

		if ($premiumCount > 0) {
			$findings[] = array("type" => "info", "message" => "{$premiumCount} plugin(s) not listed on WordPress.org (likely premium or custom).");
		}

		if ($unknownVersionCount > 0) {
			$findings[] = array("type" => "info", "message" => "Version could not be determined for {$unknownVersionCount} plugin(s).");
		}

		if ($lookupMetrics["skipped"] > 0) {
			$findings[] = array("type" => "info", "message" => "{$lookupMetrics["skipped"]} additional plugin(s) detected but not looked up to keep scan time reasonable.");
		}

		return $status;
	}

	/**
	 * Return the most commonly occurring version from an array of detected versions.
	 */
	private function determineBestVersion(array $versions): ?string
	{
		if (empty($versions)) {
			return null;
		}

		$versionCounts = array_count_values($versions);
		arsort($versionCounts);

		return array_key_first($versionCounts);
	}

	/**
	 * Convert a plugin slug to a human-readable name.
	 */
	private function formatSlugAsName(string $slug): string
	{
		return ucwords(str_replace(array("-", "_"), " ", $slug));
	}
}
