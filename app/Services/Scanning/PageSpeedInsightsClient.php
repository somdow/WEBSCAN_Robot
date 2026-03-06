<?php

namespace App\Services\Scanning;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Client for Google PageSpeed Insights API v5.
 * Returns Core Web Vitals metrics from field data (CrUX) with lab data fallback.
 * Works without an API key (rate-limited) but an API key is recommended for production.
 */
class PageSpeedInsightsClient
{
	private const API_ENDPOINT = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed";
	private const SETTINGS_KEY = "google_web_risk_api_key";
	private const TIMEOUT_SECONDS = 25;

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	/**
	 * Whether a PageSpeed Insights API key is configured in admin settings.
	 * The API works without a key but with severe rate limits.
	 */
	public function isConfigured(): bool
	{
		$apiKey = Setting::getValue(self::SETTINGS_KEY, "");

		return trim($apiKey) !== "";
	}

	/**
	 * Fetch Core Web Vitals and performance metrics for a URL.
	 *
	 * @return array{success: bool, hasFieldData: bool, fieldMetrics: array, labMetrics: array, performanceScore: ?float, error: ?string}
	 */
	public function fetchMetrics(string $targetUrl, string $strategy = "mobile"): array
	{
		if (!$this->isConfigured()) {
			Log::info("PageSpeedInsightsClient: no API key configured — skipping request", array(
				"url" => $targetUrl,
				"strategy" => $strategy,
			));

			return $this->buildErrorResult("No PageSpeed Insights API key configured.");
		}

		$apiUrl = $this->buildApiUrl($targetUrl, $strategy);

		try {
			$fetchResponse = $this->httpFetcher->fetchResource($apiUrl, self::TIMEOUT_SECONDS);

			if (!$fetchResponse->successful) {
				$apiErrorDetail = $this->extractApiErrorDetail($fetchResponse->content);
				$errorContext = $apiErrorDetail ?? ($fetchResponse->errorMessage ?? "unknown error");

				Log::warning("PageSpeedInsightsClient: API request failed", array(
					"url" => $targetUrl,
					"httpStatus" => $fetchResponse->httpStatusCode,
					"error" => $errorContext,
				));

				return $this->buildErrorResult("HTTP {$fetchResponse->httpStatusCode}: {$errorContext}");
			}

			return $this->parseApiResponse($fetchResponse->content);
		} catch (\Throwable $exception) {
			Log::error("PageSpeedInsightsClient: unexpected error", array(
				"url" => $targetUrl,
				"error" => $exception->getMessage(),
			));

			return $this->buildErrorResult("Exception: " . $exception->getMessage());
		}
	}

	/**
	 * Build the full API URL with query parameters.
	 */
	private function buildApiUrl(string $targetUrl, string $strategy): string
	{
		$params = array(
			"url" => $targetUrl,
			"strategy" => $strategy,
			"category" => "performance",
		);

		$apiKey = trim(Setting::getValue(self::SETTINGS_KEY, ""));
		if ($apiKey !== "") {
			$params["key"] = $apiKey;
		}

		return self::API_ENDPOINT . "?" . http_build_query($params);
	}

	/**
	 * Parse the raw JSON response from the PageSpeed Insights API.
	 */
	private function parseApiResponse(string $responseBody): array
	{
		$apiData = json_decode($responseBody, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->buildErrorResult("Failed to parse API response JSON.");
		}

		/** Check for API error responses */
		if (isset($apiData["error"])) {
			$errorMessage = $apiData["error"]["message"] ?? "Unknown API error";
			$errorCode = $apiData["error"]["code"] ?? 0;
			Log::warning("PageSpeedInsightsClient: API error response", array(
				"code" => $errorCode,
				"message" => $errorMessage,
			));

			return $this->buildErrorResult("API error ({$errorCode}): {$errorMessage}");
		}

		$fieldMetrics = $this->extractFieldMetrics($apiData);
		$labMetrics = $this->extractLabMetrics($apiData);
		$performanceScore = $this->extractPerformanceScore($apiData);
		$hasFieldData = !empty($fieldMetrics);
		$screenshotBase64 = $this->extractScreenshot($apiData);

		return array(
			"success" => true,
			"hasFieldData" => $hasFieldData,
			"fieldMetrics" => $fieldMetrics,
			"labMetrics" => $labMetrics,
			"performanceScore" => $performanceScore,
			"screenshotBase64" => $screenshotBase64,
			"error" => null,
		);
	}

	/**
	 * Extract field (CrUX) metrics from the loadingExperience section.
	 * Field data represents real user measurements and is preferred over lab data.
	 *
	 * @return array{lcp: ?array, cls: ?array, inp: ?array}
	 */
	private function extractFieldMetrics(array $apiData): array
	{
		$loadingExperience = $apiData["loadingExperience"] ?? array();
		$metrics = $loadingExperience["metrics"] ?? array();

		if (empty($metrics)) {
			return array();
		}

		$fieldMetrics = array();

		if (isset($metrics["LARGEST_CONTENTFUL_PAINT_MS"])) {
			$fieldMetrics["lcp"] = array(
				"value" => $metrics["LARGEST_CONTENTFUL_PAINT_MS"]["percentile"] ?? null,
				"unit" => "ms",
				"category" => $metrics["LARGEST_CONTENTFUL_PAINT_MS"]["category"] ?? null,
			);
		}

		if (isset($metrics["CUMULATIVE_LAYOUT_SHIFT_SCORE"])) {
			/** CLS field data uses percentile × 100 in some responses — normalize to 0-1 scale */
			$clsRaw = $metrics["CUMULATIVE_LAYOUT_SHIFT_SCORE"]["percentile"] ?? null;
			$clsValue = ($clsRaw !== null && $clsRaw > 1) ? $clsRaw / 100 : $clsRaw;

			$fieldMetrics["cls"] = array(
				"value" => $clsValue,
				"unit" => "",
				"category" => $metrics["CUMULATIVE_LAYOUT_SHIFT_SCORE"]["category"] ?? null,
			);
		}

		if (isset($metrics["INTERACTION_TO_NEXT_PAINT"])) {
			$fieldMetrics["inp"] = array(
				"value" => $metrics["INTERACTION_TO_NEXT_PAINT"]["percentile"] ?? null,
				"unit" => "ms",
				"category" => $metrics["INTERACTION_TO_NEXT_PAINT"]["category"] ?? null,
			);
		}

		return $fieldMetrics;
	}

	/**
	 * Extract lab (Lighthouse) metrics from the lighthouseResult section.
	 * Lab data is always available and serves as fallback when field data is absent.
	 *
	 * @return array{lcp: ?array, cls: ?array, tbt: ?array, fcp: ?array, speedIndex: ?array}
	 */
	private function extractLabMetrics(array $apiData): array
	{
		$audits = $apiData["lighthouseResult"]["audits"] ?? array();
		$labMetrics = array();

		$metricMap = array(
			"largest-contentful-paint" => array("key" => "lcp", "unit" => "ms", "divisor" => 1),
			"cumulative-layout-shift" => array("key" => "cls", "unit" => "", "divisor" => 1),
			"total-blocking-time" => array("key" => "tbt", "unit" => "ms", "divisor" => 1),
			"first-contentful-paint" => array("key" => "fcp", "unit" => "ms", "divisor" => 1),
			"speed-index" => array("key" => "speedIndex", "unit" => "ms", "divisor" => 1),
		);

		foreach ($metricMap as $auditKey => $config) {
			if (isset($audits[$auditKey])) {
				$numericValue = $audits[$auditKey]["numericValue"] ?? null;
				$score = $audits[$auditKey]["score"] ?? null;

				$labMetrics[$config["key"]] = array(
					"value" => $numericValue !== null ? round($numericValue / $config["divisor"], 2) : null,
					"unit" => $config["unit"],
					"score" => $score,
				);
			}
		}

		return $labMetrics;
	}

	/**
	 * Extract the overall Lighthouse performance score (0-1 scale).
	 */
	private function extractPerformanceScore(array $apiData): ?float
	{
		return $apiData["lighthouseResult"]["categories"]["performance"]["score"] ?? null;
	}

	/**
	 * Try to extract a human-readable error message from a Google API JSON error response.
	 * Returns null if the body is empty or not parseable.
	 */
	private function extractApiErrorDetail(?string $responseBody): ?string
	{
		if ($responseBody === null || $responseBody === "") {
			return null;
		}

		$decoded = json_decode($responseBody, true);
		if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded["error"])) {
			return null;
		}

		return $decoded["error"]["message"] ?? null;
	}

	/**
	 * Extract the final screenshot from the Lighthouse audit results.
	 * Returns raw base64 image data (JPEG) or null if unavailable.
	 */
	private function extractScreenshot(array $apiData): ?string
	{
		$dataUri = $apiData["lighthouseResult"]["audits"]["final-screenshot"]["details"]["data"] ?? null;

		if ($dataUri === null || $dataUri === "") {
			return null;
		}

		/** Strip the data URI prefix (e.g. "data:image/jpeg;base64,") to get raw base64 */
		$commaPosition = strpos($dataUri, ",");
		if ($commaPosition === false) {
			return $dataUri;
		}

		return substr($dataUri, $commaPosition + 1);
	}

	private function buildErrorResult(string $errorMessage): array
	{
		return array(
			"success" => false,
			"hasFieldData" => false,
			"fieldMetrics" => array(),
			"labMetrics" => array(),
			"performanceScore" => null,
			"screenshotBase64" => null,
			"error" => $errorMessage,
		);
	}
}
