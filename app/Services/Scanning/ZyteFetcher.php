<?php

namespace App\Services\Scanning;

use App\DataTransferObjects\FetchResult;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches web pages via the Zyte API as a fallback when direct Guzzle
 * requests are blocked by bot protection (SiteGround, Cloudflare, Sucuri).
 *
 * Returns the same FetchResult DTO as HttpFetcher so the rest of the
 * scanning pipeline works identically regardless of which fetcher was used.
 *
 * Zyte API: POST https://api.zyte.com/v1/extract
 * Auth: Basic (API key as username, empty password)
 * Cost: ~$0.001-$0.016 per page depending on complexity.
 */
class ZyteFetcher
{
	private const API_ENDPOINT = "https://api.zyte.com/v1/extract";
	private const DEFAULT_TIMEOUT = 45;

	/**
	 * Fetch a page with full browser rendering (JS execution).
	 * Used for pages that need JavaScript to render content or
	 * to bypass JS-based bot protection challenges.
	 */
	public function fetchPage(string $url): FetchResult
	{
		if (!$this->isAvailable()) {
			return $this->unavailableResult("Zyte API is not configured");
		}

		$apiKey = Setting::getValue("zyte_api_key", "");

		try {
			$response = Http::withBasicAuth($apiKey, "")
				->timeout(self::DEFAULT_TIMEOUT)
				->connectTimeout(15)
				->post(self::API_ENDPOINT, array(
					"url" => $url,
					"browserHtml" => true,
				));

			if (!$response->successful()) {
				$errorBody = $response->json();
				$errorMessage = $errorBody["detail"] ?? $errorBody["message"] ?? "Zyte API returned HTTP {$response->status()}";

				Log::warning("ZyteFetcher API error", array(
					"url" => $url,
					"status" => $response->status(),
					"error" => $errorMessage,
				));

				return new FetchResult(
					successful: false,
					content: null,
					headers: array(),
					httpStatusCode: $response->status(),
					effectiveUrl: null,
					timeToFirstByte: null,
					totalTransferTime: null,
					errorMessage: "Zyte API error: " . $errorMessage,
				);
			}

			$data = $response->json();
			$browserHtml = $data["browserHtml"] ?? null;

			if (empty($browserHtml)) {
				return new FetchResult(
					successful: false,
					content: null,
					headers: array(),
					httpStatusCode: null,
					effectiveUrl: $data["url"] ?? $url,
					timeToFirstByte: null,
					totalTransferTime: null,
					errorMessage: "Zyte API returned empty browser HTML",
				);
			}

			Log::debug("ZyteFetcher page fetched", array(
				"url" => $url,
				"effective_url" => $data["url"] ?? $url,
				"content_length" => strlen($browserHtml),
			));

			return new FetchResult(
				successful: true,
				content: $browserHtml,
				headers: array(),
				httpStatusCode: $data["statusCode"] ?? 200,
				effectiveUrl: $data["url"] ?? $url,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: null,
			);
		} catch (\Illuminate\Http\Client\ConnectionException $exception) {
			Log::warning("ZyteFetcher connection failed", array(
				"url" => $url,
				"error" => $exception->getMessage(),
			));

			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Zyte connection failed: " . $exception->getMessage(),
			);
		} catch (\Throwable $exception) {
			Log::error("ZyteFetcher unexpected error", array(
				"url" => $url,
				"error" => $exception->getMessage(),
			));

			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Zyte fetch error: " . $exception->getMessage(),
			);
		}
	}

	/**
	 * Lightweight HTTP-only fetch without browser rendering.
	 * Cheaper than fetchPage() — used for auxiliary resources
	 * (robots.txt, sitemaps, etc.) that don't need JS execution.
	 */
	public function fetchResource(string $url): FetchResult
	{
		if (!$this->isAvailable()) {
			return $this->unavailableResult("Zyte API is not configured");
		}

		$apiKey = Setting::getValue("zyte_api_key", "");

		try {
			$response = Http::withBasicAuth($apiKey, "")
				->timeout(self::DEFAULT_TIMEOUT)
				->connectTimeout(15)
				->post(self::API_ENDPOINT, array(
					"url" => $url,
					"httpResponseBody" => true,
				));

			if (!$response->successful()) {
				$errorBody = $response->json();
				$errorMessage = $errorBody["detail"] ?? $errorBody["message"] ?? "Zyte API returned HTTP {$response->status()}";

				return new FetchResult(
					successful: false,
					content: null,
					headers: array(),
					httpStatusCode: $response->status(),
					effectiveUrl: null,
					timeToFirstByte: null,
					totalTransferTime: null,
					errorMessage: "Zyte API error: " . $errorMessage,
				);
			}

			$data = $response->json();
			$body = $data["httpResponseBody"] ?? null;

			if ($body !== null) {
				$body = base64_decode($body);
			}

			return new FetchResult(
				successful: !empty($body),
				content: $body ?: null,
				headers: $data["httpResponseHeaders"] ?? array(),
				httpStatusCode: $data["statusCode"] ?? 200,
				effectiveUrl: $data["url"] ?? $url,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: empty($body) ? "Zyte returned empty response body" : null,
			);
		} catch (\Throwable $exception) {
			Log::warning("ZyteFetcher resource fetch failed", array(
				"url" => $url,
				"error" => $exception->getMessage(),
			));

			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Zyte fetch error: " . $exception->getMessage(),
			);
		}
	}

	/**
	 * Capture a browser-rendered screenshot of a page via Zyte API.
	 * Returns raw base64-encoded image data or null on failure.
	 * Used as a fallback when the PageSpeed Insights screenshot is unavailable.
	 */
	public function fetchScreenshot(string $url): ?string
	{
		if (!$this->isAvailable()) {
			return null;
		}

		$apiKey = Setting::getValue("zyte_api_key", "");

		try {
			$response = Http::withBasicAuth($apiKey, "")
				->timeout(self::DEFAULT_TIMEOUT)
				->connectTimeout(15)
				->post(self::API_ENDPOINT, array(
					"url" => $url,
					"screenshot" => true,
				));

			if (!$response->successful()) {
				Log::warning("ZyteFetcher screenshot API error", array(
					"url" => $url,
					"status" => $response->status(),
				));

				return null;
			}

			$screenshotBase64 = $response->json("screenshot");

			if (empty($screenshotBase64)) {
				Log::warning("ZyteFetcher screenshot response empty", array("url" => $url));

				return null;
			}

			Log::debug("ZyteFetcher screenshot captured", array(
				"url" => $url,
				"data_length" => strlen($screenshotBase64),
			));

			return $screenshotBase64;
		} catch (\Throwable $exception) {
			Log::warning("ZyteFetcher screenshot failed", array(
				"url" => $url,
				"error" => $exception->getMessage(),
			));

			return null;
		}
	}

	/**
	 * Check whether the Zyte API is configured and enabled.
	 */
	public function isAvailable(): bool
	{
		return (bool) Setting::getValue("zyte_enabled", false)
			&& !empty(Setting::getValue("zyte_api_key", ""));
	}

	private function unavailableResult(string $message): FetchResult
	{
		return new FetchResult(
			successful: false,
			content: null,
			headers: array(),
			httpStatusCode: null,
			effectiveUrl: null,
			timeToFirstByte: null,
			totalTransferTime: null,
			errorMessage: $message,
		);
	}
}
