<?php

namespace App\Services\Scanning;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class WhatCmsClient
{
	private const API_BASE_URL = "https://whatcms.org/API/Tech";
	private const SETTINGS_KEY = "whatcms_api_key";
	private const TIMEOUT_SECONDS = 10;

	private const RESPONSE_CODES = array(
		0 => "Server failure",
		100 => "API key not provided",
		101 => "Invalid API key",
		110 => "URL not provided",
		111 => "Invalid URL",
		120 => "Rate limit exceeded",
		121 => "Monthly quota exceeded",
		200 => "Success",
		201 => "CMS not detected",
		202 => "Target URL unavailable",
	);

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	/**
	 * Whether a WhatCMS API key is configured in admin settings.
	 */
	public function isConfigured(): bool
	{
		$apiKey = Setting::getValue(self::SETTINGS_KEY, "");

		return trim($apiKey) !== "";
	}

	/**
	 * Call WhatCMS.org Tech endpoint to detect the technology stack of a URL.
	 * Returns a structured result with WordPress detection, version, and full tech stack.
	 *
	 * @return array{success: bool, isWordPress: bool, detectedVersion: ?string, techStack: array, error: ?string}
	 */
	public function detectTechStack(string $targetUrl): array
	{
		$apiKey = trim(Setting::getValue(self::SETTINGS_KEY, ""));
		if ($apiKey === "") {
			return $this->buildErrorResult("WhatCMS API key not configured.");
		}

		try {
			$apiUrl = $this->buildApiUrl($apiKey, $targetUrl);
			$fetchResponse = $this->httpFetcher->fetchResource($apiUrl, self::TIMEOUT_SECONDS);

			if (!$fetchResponse->successful) {
				Log::warning("WhatCmsClient: API request failed", array(
					"url" => $targetUrl,
					"error" => $fetchResponse->errorMessage,
				));

				return $this->buildErrorResult("API request failed: " . $fetchResponse->errorMessage);
			}

			return $this->parseApiResponse($fetchResponse->content);
		} catch (\Throwable $exception) {
			Log::error("WhatCmsClient: unexpected error", array(
				"url" => $targetUrl,
				"error" => $exception->getMessage(),
			));

			return $this->buildErrorResult("Exception: " . $exception->getMessage());
		}
	}

	private function buildApiUrl(string $apiKey, string $targetUrl): string
	{
		return self::API_BASE_URL
			. "?key=" . urlencode($apiKey)
			. "&url=" . urlencode($targetUrl);
	}

	/**
	 * Parse the raw JSON response from WhatCMS and extract structured results.
	 */
	private function parseApiResponse(string $responseBody): array
	{
		$apiData = json_decode($responseBody, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->buildErrorResult("Failed to parse API response JSON.");
		}

		$responseCode = $apiData["result"]["code"] ?? 0;

		if ($responseCode === 201) {
			return array(
				"success" => true,
				"isWordPress" => false,
				"detectedVersion" => null,
				"techStack" => array(),
				"error" => null,
			);
		}

		if ($responseCode !== 200) {
			$codeMessage = $this->resolveResponseCodeMessage($responseCode);
			Log::warning("WhatCmsClient: non-success response", array(
				"code" => $responseCode,
				"message" => $codeMessage,
			));

			return $this->buildErrorResult("API responded with code {$responseCode}: {$codeMessage}");
		}

		$rawResults = $apiData["results"] ?? array();
		$techStack = array();
		$isWordPress = false;
		$detectedVersion = null;

		foreach ($rawResults as $entry) {
			$entryName = $entry["name"] ?? "";
			$entryVersion = (!empty($entry["version"])) ? $entry["version"] : null;
			$entryCategories = $entry["categories"] ?? array();

			$techStack[] = array(
				"name" => $entryName,
				"version" => $entryVersion,
				"categories" => $entryCategories,
			);

			if (strcasecmp($entryName, "WordPress") === 0) {
				$isWordPress = true;
				$detectedVersion = $entryVersion;
			}
		}

		return array(
			"success" => true,
			"isWordPress" => $isWordPress,
			"detectedVersion" => $detectedVersion,
			"techStack" => $techStack,
			"error" => null,
		);
	}

	private function buildErrorResult(string $errorMessage): array
	{
		return array(
			"success" => false,
			"isWordPress" => false,
			"detectedVersion" => null,
			"techStack" => array(),
			"error" => $errorMessage,
		);
	}

	private function resolveResponseCodeMessage(int $code): string
	{
		return self::RESPONSE_CODES[$code] ?? "Unknown response code: {$code}";
	}
}
