<?php

namespace App\Services\Scanning;

use Illuminate\Support\Facades\Log;

class WordPressApiClient
{
	private const PLUGIN_API_URL = "https://api.wordpress.org/plugins/info/1.2/";
	private const THEME_API_URL = "https://api.wordpress.org/themes/info/1.2/";
	private const CORE_VERSION_API_URL = "https://api.wordpress.org/core/version-check/1.7/";
	private const WPVULN_BASE_URL = "https://www.wpvulnerability.net/";

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	/**
	 * Fetch plugin metadata from api.wordpress.org
	 */
	public function fetchPluginInfo(string $pluginSlug): array
	{
		$apiUrl = self::PLUGIN_API_URL
			. "?action=plugin_information"
			. "&request[slug]=" . urlencode($pluginSlug)
			. "&request[fields][sections]=0"
			. "&request[fields][reviews]=0"
			. "&request[fields][banners]=0"
			. "&request[fields][screenshots]=0"
			. "&request[fields][contributors]=0"
			. "&request[fields][tags]=0";

		return $this->fetchWordPressOrgInfo($apiUrl, $pluginSlug, "plugin");
	}

	/**
	 * Fetch theme metadata from api.wordpress.org
	 */
	public function fetchThemeInfo(string $themeSlug): array
	{
		$apiUrl = self::THEME_API_URL
			. "?action=theme_information"
			. "&request[slug]=" . urlencode($themeSlug)
			. "&request[fields][sections]=0"
			. "&request[fields][description]=0"
			. "&request[fields][screenshot_url]=0";

		return $this->fetchWordPressOrgInfo($apiUrl, $themeSlug, "theme");
	}

	/**
	 * Fetch latest WordPress core version from api.wordpress.org
	 */
	public function fetchLatestCoreVersion(): array
	{
		$result = array("success" => false, "latest_version" => null, "error" => null);

		try {
			$fetchResponse = $this->httpFetcher->fetchResource(self::CORE_VERSION_API_URL);

			if (!$fetchResponse->successful) {
				$result["error"] = "WordPress.org Core API request failed: " . $fetchResponse->errorMessage;
				return $result;
			}

			$apiData = json_decode($fetchResponse->content, true);
			if (json_last_error() !== JSON_ERROR_NONE || !isset($apiData["offers"][0]["version"])) {
				$result["error"] = "Unexpected Core API response structure.";
				return $result;
			}

			$result["success"] = true;
			$result["latest_version"] = $apiData["offers"][0]["version"];
		} catch (\Throwable $exception) {
			Log::error("WordPressApiClient::fetchLatestCoreVersion failed", array("error" => $exception->getMessage()));
			$result["error"] = "Exception: " . $exception->getMessage();
		}

		return $result;
	}

	/**
	 * Fetch plugin vulnerabilities from wpvulnerability.net
	 */
	public function fetchPluginVulnerabilities(string $pluginSlug, ?string $detectedVersion = null): array
	{
		$apiUrl = self::WPVULN_BASE_URL . "plugin/" . urlencode($pluginSlug) . "/";

		return $this->fetchVulnerabilities($apiUrl, $detectedVersion);
	}

	/**
	 * Fetch theme vulnerabilities from wpvulnerability.net
	 */
	public function fetchThemeVulnerabilities(string $themeSlug, ?string $detectedVersion = null): array
	{
		$apiUrl = self::WPVULN_BASE_URL . "theme/" . urlencode($themeSlug) . "/";

		return $this->fetchVulnerabilities($apiUrl, $detectedVersion);
	}

	/**
	 * Fetch WordPress core vulnerabilities from wpvulnerability.net
	 */
	public function fetchCoreVulnerabilities(string $coreVersion): array
	{
		$apiUrl = self::WPVULN_BASE_URL . "core/" . urlencode($coreVersion) . "/";

		return $this->fetchVulnerabilities($apiUrl, $coreVersion);
	}

	/**
	 * Shared logic for fetching from WordPress.org plugin/theme APIs
	 */
	private function fetchWordPressOrgInfo(string $apiUrl, string $slug, string $type): array
	{
		$result = array(
			"success" => false, "not_found" => false, "name" => null, "slug" => $slug,
			"latest_version" => null, "last_updated" => null, "author" => null,
			"homepage" => null, "error" => null,
		);

		try {
			$fetchResponse = $this->httpFetcher->fetchResource($apiUrl);

			if (!$fetchResponse->successful) {
				$isNotFound = $fetchResponse->httpStatusCode === 404;
				$result["not_found"] = $isNotFound;
				$result["error"] = $isNotFound
					? ucfirst($type) . " not found on WordPress.org (likely premium or custom)."
					: "WordPress.org {$type} API request failed: " . $fetchResponse->errorMessage;

				return $result;
			}

			$apiData = json_decode($fetchResponse->content, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$result["error"] = "Failed to parse WordPress.org API response.";
				return $result;
			}

			if (isset($apiData["error"])) {
				$result["error"] = "WordPress.org API returned error: " . $apiData["error"];
				return $result;
			}

			$result["success"] = true;
			$result["name"] = $apiData["name"] ?? null;
			$result["slug"] = $apiData["slug"] ?? $slug;
			$result["latest_version"] = $apiData["version"] ?? null;
			$result["last_updated"] = $apiData["last_updated"] ?? null;
			$result["author"] = isset($apiData["author"]) ? strip_tags($apiData["author"]) : null;
			$result["homepage"] = $apiData["homepage"] ?? null;
		} catch (\Throwable $exception) {
			Log::error("WordPressApiClient::{$type} info failed", array("slug" => $slug, "error" => $exception->getMessage()));
			$result["error"] = "Exception: " . $exception->getMessage();
		}

		return $result;
	}

	/**
	 * Shared vulnerability fetching and version-filtering logic
	 */
	private function fetchVulnerabilities(string $apiUrl, ?string $detectedVersion): array
	{
		$result = array("success" => false, "total_vulnerabilities" => 0, "affecting_version" => 0, "vulnerabilities" => array(), "error" => null);

		try {
			$fetchResponse = $this->httpFetcher->fetchResource($apiUrl);

			if (!$fetchResponse->successful) {
				if ($fetchResponse->httpStatusCode === 404) {
					$result["success"] = true;
					return $result;
				}
				$result["error"] = "WPVulnerability API request failed: " . $fetchResponse->errorMessage;
				return $result;
			}

			$apiData = json_decode($fetchResponse->content, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$result["error"] = "Failed to parse WPVulnerability API response.";
				return $result;
			}

			$allVulnerabilities = $apiData["data"]["vulnerability"] ?? array();
			$result["total_vulnerabilities"] = count($allVulnerabilities);
			$result["success"] = true;

			foreach ($allVulnerabilities as $vuln) {
				$operator = $vuln["operator"] ?? array();

				if ($this->isVersionAffected($detectedVersion, $operator)) {
					$result["vulnerabilities"][] = $this->extractVulnerabilityDetails($vuln, $operator);
				}
			}

			$result["affecting_version"] = count($result["vulnerabilities"]);
		} catch (\Throwable $exception) {
			Log::error("WordPressApiClient vulnerability fetch failed", array("url" => $apiUrl, "error" => $exception->getMessage()));
			$result["error"] = "Exception: " . $exception->getMessage();
		}

		return $result;
	}

	/**
	 * Check if a detected version falls within a vulnerability's affected range
	 */
	private function isVersionAffected(?string $detectedVersion, array $operator): bool
	{
		if ($detectedVersion === null) {
			return false;
		}

		$minVersion = $operator["min_version"] ?? null;
		$maxVersion = $operator["max_version"] ?? null;
		$maxOperator = $operator["max_operator"] ?? "le";
		$unfixed = ($operator["unfixed"] ?? "0") === "1";

		if ($maxVersion === null || $maxVersion === "") {
			return $unfixed;
		}

		if ($minVersion !== null && $minVersion !== "" && version_compare($detectedVersion, $minVersion, "<")) {
			return false;
		}

		$compareOp = $maxOperator === "lt" ? "<" : "<=";

		return version_compare($detectedVersion, $maxVersion, $compareOp);
	}

	/**
	 * Extract structured details from a raw vulnerability entry
	 */
	private function extractVulnerabilityDetails(array $vuln, array $operator): array
	{
		$cveId = null;
		$cveDescription = null;
		$sources = $vuln["source"] ?? array();

		foreach ($sources as $source) {
			if (isset($source["id"]) && str_starts_with($source["id"], "CVE-")) {
				$cveId = $source["id"];
				$cveDescription = $source["description"] ?? null;
				break;
			}
		}

		if ($cveId === null && !empty($sources)) {
			$cveId = $sources[0]["id"] ?? null;
			$cveDescription = $sources[0]["description"] ?? null;
		}

		$cvssScore = null;
		$impacts = $vuln["impact"] ?? array();
		foreach ($impacts as $impact) {
			if (isset($impact["cvss"]["score"])) {
				$cvssScore = (float) $impact["cvss"]["score"];
				break;
			}
		}

		return array(
			"name" => $vuln["name"] ?? "Unknown Vulnerability",
			"cve_id" => $cveId,
			"description" => $cveDescription,
			"cvss_score" => $cvssScore,
			"fixed_in" => $operator["max_version"] ?? null,
			"unfixed" => ($operator["unfixed"] ?? "0") === "1",
		);
	}
}
