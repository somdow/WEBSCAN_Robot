<?php

namespace App\Services\Scanning;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google threat detection client supporting two modes:
 *
 * - Safe Browsing v4 (default): Free, unlimited, non-commercial use only.
 *   POST to safebrowsing.googleapis.com/v4/threatMatches:find
 *
 * - Web Risk (commercial mode): Commercially licensed, 100K free lookups/month.
 *   GET to webrisk.googleapis.com/v1/uris:search
 *
 * Both use the same API key. Admin toggle in Site Settings controls which endpoint.
 * Module degrades gracefully without a configured key.
 */
class WebRiskClient
{
	private const SAFE_BROWSING_ENDPOINT = "https://safebrowsing.googleapis.com/v4/threatMatches:find";
	private const WEB_RISK_ENDPOINT = "https://webrisk.googleapis.com/v1/uris:search";

	private const API_KEY_SETTING = GoogleApiSettings::SHARED_API_KEY;
	private const COMMERCIAL_MODE_SETTING = "google_threat_commercial_mode";
	private const TIMEOUT_SECONDS = 10;

	private const THREAT_TYPES = array(
		"MALWARE",
		"SOCIAL_ENGINEERING",
		"UNWANTED_SOFTWARE",
	);

	/**
	 * Whether a Google API key is configured in admin settings.
	 */
	public function isConfigured(): bool
	{
		$apiKey = Setting::getValue(self::API_KEY_SETTING, "");

		return trim($apiKey) !== "";
	}

	/**
	 * Whether commercial mode (Web Risk API) is enabled.
	 */
	public function isCommercialMode(): bool
	{
		return (bool) Setting::getValue(self::COMMERCIAL_MODE_SETTING, false);
	}

	/**
	 * Check a URL against Google's threat database.
	 * Routes to Web Risk or Safe Browsing based on admin toggle.
	 *
	 * @return array{success: bool, threats: array<string>, error: ?string}
	 */
	public function checkUrl(string $targetUrl): array
	{
		$apiKey = trim(Setting::getValue(self::API_KEY_SETTING, ""));

		if ($apiKey === "") {
			return $this->buildErrorResult("Google API key not configured.");
		}

		try {
			if ($this->isCommercialMode()) {
				return $this->checkViaWebRisk($targetUrl, $apiKey);
			}

			return $this->checkViaSafeBrowsing($targetUrl, $apiKey);
		} catch (\Illuminate\Http\Client\ConnectionException $exception) {
			Log::warning("WebRiskClient: connection failed", array(
				"url" => $targetUrl,
				"mode" => $this->isCommercialMode() ? "web_risk" : "safe_browsing",
				"error" => $exception->getMessage(),
			));

			return $this->buildErrorResult("Connection failed: " . $exception->getMessage());
		} catch (\Throwable $exception) {
			Log::error("WebRiskClient: unexpected error", array(
				"url" => $targetUrl,
				"mode" => $this->isCommercialMode() ? "web_risk" : "safe_browsing",
				"error" => $exception->getMessage(),
			));

			return $this->buildErrorResult("Exception: " . $exception->getMessage());
		}
	}

	/**
	 * Web Risk Lookup API (commercial) — GET request with repeated threatTypes params.
	 */
	private function checkViaWebRisk(string $targetUrl, string $apiKey): array
	{
		$queryParts = array();

		foreach (self::THREAT_TYPES as $threatType) {
			$queryParts[] = "threatTypes=" . urlencode($threatType);
		}

		$queryParts[] = "uri=" . urlencode($targetUrl);
		$queryParts[] = "key=" . urlencode($apiKey);

		$requestUrl = self::WEB_RISK_ENDPOINT . "?" . implode("&", $queryParts);

		$response = Http::timeout(self::TIMEOUT_SECONDS)->get($requestUrl);

		if (!$response->successful()) {
			$this->logApiFailure($targetUrl, $response, "web_risk");

			return $this->buildErrorResult("API request failed with status " . $response->status());
		}

		return $this->parseWebRiskResponse($response->body());
	}

	/**
	 * Safe Browsing v4 (free/non-commercial) — POST request with JSON payload.
	 */
	private function checkViaSafeBrowsing(string $targetUrl, string $apiKey): array
	{
		$requestUrl = self::SAFE_BROWSING_ENDPOINT . "?key=" . urlencode($apiKey);

		$payload = array(
			"client" => array(
				"clientId" => "hello-web-scans",
				"clientVersion" => "2.0",
			),
			"threatInfo" => array(
				"threatTypes" => self::THREAT_TYPES,
				"platformTypes" => array("ANY_PLATFORM"),
				"threatEntryTypes" => array("URL"),
				"threatEntries" => array(
					array("url" => $targetUrl),
				),
			),
		);

		$response = Http::timeout(self::TIMEOUT_SECONDS)
			->withHeaders(array("Content-Type" => "application/json"))
			->post($requestUrl, $payload);

		if (!$response->successful()) {
			$this->logApiFailure($targetUrl, $response, "safe_browsing");

			return $this->buildErrorResult("API request failed with status " . $response->status());
		}

		return $this->parseSafeBrowsingResponse($response->body());
	}

	/**
	 * Parse Web Risk response. Empty {} = clean, "threat" key = flagged.
	 */
	private function parseWebRiskResponse(string $responseBody): array
	{
		$apiData = json_decode($responseBody, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->buildErrorResult("Failed to parse API response JSON.");
		}

		$threat = $apiData["threat"] ?? null;

		if ($threat === null) {
			return array(
				"success" => true,
				"threats" => array(),
				"error" => null,
			);
		}

		return array(
			"success" => true,
			"threats" => $threat["threatTypes"] ?? array(),
			"error" => null,
		);
	}

	/**
	 * Parse Safe Browsing v4 response. Empty {} = clean, "matches" key = flagged.
	 */
	private function parseSafeBrowsingResponse(string $responseBody): array
	{
		$apiData = json_decode($responseBody, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->buildErrorResult("Failed to parse API response JSON.");
		}

		$matches = $apiData["matches"] ?? array();

		if (empty($matches)) {
			return array(
				"success" => true,
				"threats" => array(),
				"error" => null,
			);
		}

		$threatTypes = array_unique(array_column($matches, "threatType"));

		return array(
			"success" => true,
			"threats" => array_values($threatTypes),
			"error" => null,
		);
	}

	private function logApiFailure(string $targetUrl, $response, string $mode): void
	{
		Log::warning("WebRiskClient: API request failed", array(
			"url" => $targetUrl,
			"mode" => $mode,
			"status" => $response->status(),
			"body" => mb_substr($response->body(), 0, 500),
		));
	}

	private function buildErrorResult(string $errorMessage): array
	{
		return array(
			"success" => false,
			"threats" => array(),
			"error" => $errorMessage,
		);
	}
}
