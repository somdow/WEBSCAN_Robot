<?php

namespace App\Services\Analyzers\WordPress;

use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;
use App\Services\Scanning\WordPressApiClient;
use Illuminate\Support\Facades\Log;

/**
 * Handles WordPress.org API lookups, REST API discovery, and health classification
 * for detected plugins. Separated from WpPluginsAnalyzer to keep detection logic
 * distinct from API interaction and reporting.
 */
class WpPluginResultsBuilder
{
	public function __construct(
		private readonly HttpFetcher $httpFetcher,
		private readonly WordPressApiClient $wordPressApiClient,
	) {}

	/**
	 * Fetch /wp-json/ and discover plugins from registered REST API namespaces.
	 * One HTTP request reveals all plugins that register REST endpoints.
	 */
	public function discoverPluginsFromRestApi(string $domainRoot, array &$pluginRegistry): void
	{
		$wpJsonUrl = rtrim($domainRoot, "/") . "/wp-json/";
		$namespaceSlugMap = config("wp-fingerprints.rest_namespace_to_slug", array());

		try {
			$fetchResponse = $this->httpFetcher->fetchResource($wpJsonUrl);
			if (!$fetchResponse->successful) {
				return;
			}

			$apiIndex = json_decode($fetchResponse->content, true);
			if (json_last_error() !== JSON_ERROR_NONE || !isset($apiIndex["namespaces"])) {
				return;
			}

			foreach ($apiIndex["namespaces"] as $namespace) {
				$namespacePrefix = strtolower(explode("/", $namespace)[0]);

				if ($namespacePrefix === "wp" || $namespacePrefix === "oembed") {
					continue;
				}

				$slug = $namespaceSlugMap[$namespacePrefix] ?? null;
				if ($slug !== null && $slug !== "developer") {
					$this->registerPlugin($pluginRegistry, $slug, "rest-namespace");
				}
			}
		} catch (\Throwable $exception) {
			Log::debug("WP REST namespace discovery failed", array("url" => $wpJsonUrl, "error" => $exception->getMessage()));
		}
	}

	/**
	 * Try unresolved script handles as plugin slugs via WordPress.org API.
	 * Strips common suffixes to derive candidate slugs.
	 */
	public function discoverPluginsFromHandles(array $unresolvedHandles, array &$pluginRegistry, int $maxAttempts = 5): void
	{
		$stripSuffixes = config("wp-fingerprints.handle_strip_suffixes", array());
		$attemptCount = 0;

		foreach ($unresolvedHandles as $handle) {
			if ($attemptCount >= $maxAttempts) {
				break;
			}

			$candidateSlugs = $this->buildCandideSlugsFromHandle($handle, $stripSuffixes);

			foreach ($candidateSlugs as $candidateSlug) {
				if (isset($pluginRegistry[$candidateSlug])) {
					break;
				}

				$attemptCount++;
				$apiResult = $this->wordPressApiClient->fetchPluginInfo($candidateSlug);

				if ($apiResult["success"]) {
					$this->registerPlugin($pluginRegistry, $candidateSlug, "handle-api-lookup");
					break;
				}

				if ($attemptCount >= $maxAttempts) {
					break;
				}
			}
		}
	}

	/**
	 * Generate candidate plugin slugs from a script handle by stripping known suffixes.
	 */
	private function buildCandideSlugsFromHandle(string $handle, array $stripSuffixes): array
	{
		$candidates = array($handle);

		foreach ($stripSuffixes as $suffix) {
			if (str_ends_with($handle, $suffix)) {
				$stripped = substr($handle, 0, -strlen($suffix));
				if (strlen($stripped) >= 3 && !in_array($stripped, $candidates, true)) {
					$candidates[] = $stripped;
				}
			}
		}

		return $candidates;
	}

	private function registerPlugin(array &$pluginRegistry, string $slug, string $source): void
	{
		if (!isset($pluginRegistry[$slug])) {
			$pluginRegistry[$slug] = array("versions" => array(), "sources" => array());
		}

		if (!in_array($source, $pluginRegistry[$slug]["sources"], true)) {
			$pluginRegistry[$slug]["sources"][] = $source;
		}
	}

	/**
	 * Transform raw plugin registry into structured plugin array with display names.
	 */
	public function buildDetectedPlugins(array $pluginRegistry): array
	{
		$detectedPlugins = array();

		foreach ($pluginRegistry as $slug => $registryEntry) {
			$detectedPlugins[$slug] = array(
				"slug" => $slug,
				"name" => $this->formatSlugAsName($slug),
				"detected_version" => $this->pickMostFrequentVersion($registryEntry["versions"]),
				"latest_version" => null,
				"version_status" => "unknown",
				"is_premium" => false,
				"vulnerabilities_count" => 0,
				"vulnerabilities" => array(),
				"detection_sources" => $registryEntry["sources"],
			);
		}

		return $detectedPlugins;
	}

	/**
	 * Enrich detected plugins with WordPress.org API data and vulnerability info.
	 */
	public function performApiLookups(array &$detectedPlugins, int $maxLookups): array
	{
		$lookupCount = 0;
		$skippedLookups = 0;

		foreach ($detectedPlugins as $slug => &$pluginEntry) {
			if ($lookupCount >= $maxLookups) {
				$skippedLookups++;
				continue;
			}

			$this->enrichPluginFromApi($slug, $pluginEntry);
			$lookupCount++;
		}
		unset($pluginEntry);

		return array("skipped" => $skippedLookups);
	}

	/**
	 * Classify overall plugin health and generate findings/recommendations.
	 */
	public function classifyPluginHealth(array $detectedPlugins, array $lookupMetrics, array &$findings, array &$recommendations): ModuleStatus
	{
		$vulnerablePlugins = array();
		$outdatedPlugins = array();
		$premiumCount = 0;
		$unknownVersionCount = 0;

		foreach ($detectedPlugins as $pluginEntry) {
			if ($pluginEntry["vulnerabilities_count"] > 0) {
				$vulnerablePlugins[] = $pluginEntry;
			} elseif ($pluginEntry["version_status"] === "outdated") {
				$outdatedPlugins[] = $pluginEntry;
			}

			if ($pluginEntry["is_premium"]) {
				$premiumCount++;
			}
			if ($pluginEntry["detected_version"] === null) {
				$unknownVersionCount++;
			}
		}

		$status = ModuleStatus::Ok;

		if (!empty($vulnerablePlugins)) {
			$status = ModuleStatus::Bad;
			$this->appendPluginIssueFindings($vulnerablePlugins, "bad", "known security vulnerabilities", $findings, $recommendations, true);
		}

		if (!empty($outdatedPlugins)) {
			if ($status !== ModuleStatus::Bad) {
				$status = ModuleStatus::Warning;
			}
			$this->appendPluginIssueFindings($outdatedPlugins, "warning", "appear to be outdated", $findings, $recommendations, false);
		}

		if (empty($vulnerablePlugins) && empty($outdatedPlugins)) {
			$findings[] = array("type" => "ok", "message" => "No outdated or vulnerable plugins detected.");
		}

		$this->appendInfoFindings($premiumCount, $unknownVersionCount, $lookupMetrics["skipped"], $findings);

		return $status;
	}

	private function enrichPluginFromApi(string $slug, array &$pluginEntry): void
	{
		$apiResult = $this->wordPressApiClient->fetchPluginInfo($slug);

		if ($apiResult["success"]) {
			$pluginEntry["name"] = $apiResult["name"] ?? $pluginEntry["name"];
			$pluginEntry["latest_version"] = $apiResult["latest_version"];

			if ($pluginEntry["detected_version"] !== null && $apiResult["latest_version"] !== null) {
				$pluginEntry["version_status"] = version_compare($pluginEntry["detected_version"], $apiResult["latest_version"]) >= 0
					? "current"
					: "outdated";
			}
		} else {
			$pluginEntry["is_premium"] = !empty($apiResult["not_found"]);
		}

		$vulnResult = $this->wordPressApiClient->fetchPluginVulnerabilities($slug, $pluginEntry["detected_version"]);
		if ($vulnResult["success"] && $vulnResult["affecting_version"] > 0) {
			$pluginEntry["vulnerabilities_count"] = $vulnResult["affecting_version"];
			$pluginEntry["vulnerabilities"] = $vulnResult["vulnerabilities"];
		}
	}

	private function appendPluginIssueFindings(array $plugins, string $severity, string $issueLabel, array &$findings, array &$recommendations, bool $includeCveCount): void
	{
		$pluginNames = $includeCveCount
			? array_map(fn(array $entry) => "{$entry["name"]} ({$entry["vulnerabilities_count"]} CVE(s))", $plugins)
			: array_map(fn(array $entry) => $entry["name"], $plugins);

		$findings[] = array(
			"type" => $severity,
			"message" => count($plugins) . " plugin(s) have {$issueLabel}: " . implode(", ", $pluginNames) . ".",
		);

		$actionLabel = $includeCveCount ? "Security Alert: Update vulnerable" : "Update outdated";
		$recommendations[] = "{$actionLabel} plugins: " . implode(", ", $pluginNames) . ".";
	}

	private function appendInfoFindings(int $premiumCount, int $unknownVersionCount, int $skippedCount, array &$findings): void
	{
		if ($premiumCount > 0) {
			$findings[] = array("type" => "info", "message" => "{$premiumCount} plugin(s) not listed on WordPress.org (likely premium or custom).");
		}

		if ($unknownVersionCount > 0) {
			$findings[] = array("type" => "info", "message" => "Version could not be determined for {$unknownVersionCount} plugin(s).");
		}

		if ($skippedCount > 0) {
			$findings[] = array("type" => "info", "message" => "{$skippedCount} additional plugin(s) detected but not looked up to keep scan time reasonable.");
		}
	}

	private function pickMostFrequentVersion(array $versions): ?string
	{
		if (empty($versions)) {
			return null;
		}

		$versionCounts = array_count_values($versions);
		arsort($versionCounts);

		return array_key_first($versionCounts);
	}

	private function formatSlugAsName(string $slug): string
	{
		return ucwords(str_replace(array("-", "_"), " ", $slug));
	}
}
