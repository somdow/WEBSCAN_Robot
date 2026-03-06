<?php

namespace App\Services\Scanning;

use App\DataTransferObjects\FetchResult;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpFetcher
{
	/**
	 * Fetch a page URL with full timing data and redirect following.
	 * Used for the primary target URL of a scan.
	 */
	public function fetchPage(string $url, ?int $timeoutSeconds = null): FetchResult
	{
		$timeout = $timeoutSeconds ?? config("scanning.page_timeout", 15);

		$result = $this->executeRequest($url, $timeout, true, true);

		if (!$result->successful && $this->isSslError($result->errorMessage)) {
			if ($this->shouldAllowInsecureFallback($url)) {
				Log::info("TLS verification failed, retrying without verification", array("url" => $url));
				$result = $this->executeRequest($url, $timeout, true, false);
			}
		}

		return $result;
	}

	/**
	 * Lightweight fetch for auxiliary resources (robots.txt, sitemaps, RSS feeds, etc.).
	 * Shorter timeout, no timing data captured.
	 */
	public function fetchResource(string $url, ?int $timeoutSeconds = null): FetchResult
	{
		$timeout = $timeoutSeconds ?? config("scanning.resource_timeout", 5);

		$result = $this->executeRequest($url, $timeout, false, true);

		if (!$result->successful && $this->isSslError($result->errorMessage)) {
			if ($this->shouldAllowInsecureFallback($url)) {
				$result = $this->executeRequest($url, $timeout, false, false);
			}
		}

		return $result;
	}

	/**
	 * Fetch a URL without following redirects. Returns the raw status code
	 * and Location header so callers can inspect redirect behavior.
	 */
	public function fetchWithoutRedirects(string $url, ?int $timeoutSeconds = null): FetchResult
	{
		$timeout = $timeoutSeconds ?? config("scanning.resource_timeout", 5);

		try {
			$response = Http::withOptions(array(
				"allow_redirects" => false,
				"verify" => true,
			))
				->timeout($timeout)
				->connectTimeout($timeout)
				->withUserAgent(config("scanning.user_agent"))
				->get($url);

			$headers = $this->normalizeHeaders($response->headers());

			return new FetchResult(
				successful: true,
				content: $response->body(),
				headers: $headers,
				httpStatusCode: $response->status(),
				effectiveUrl: $url,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: null,
			);
		} catch (\Illuminate\Http\Client\ConnectionException $exception) {
			if ($this->isSslError($exception->getMessage())) {
				return $this->fetchWithoutRedirectsInsecure($url, $timeout);
			}

			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Connection failed: " . $exception->getMessage(),
			);
		} catch (\Throwable $exception) {
			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Fetch error: " . $exception->getMessage(),
			);
		}
	}

	/**
	 * Fallback for fetchWithoutRedirects when TLS verification fails.
	 */
	private function fetchWithoutRedirectsInsecure(string $url, int $timeout): FetchResult
	{
		if (!$this->shouldAllowInsecureFallback($url)) {
			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Insecure TLS fallback denied for unsafe target.",
			);
		}

		try {
			$response = Http::withOptions(array(
				"allow_redirects" => false,
				"verify" => false,
			))
				->timeout($timeout)
				->connectTimeout($timeout)
				->withUserAgent(config("scanning.user_agent"))
				->get($url);

			$headers = $this->normalizeHeaders($response->headers());

			return new FetchResult(
				successful: true,
				content: $response->body(),
				headers: $headers,
				httpStatusCode: $response->status(),
				effectiveUrl: $url,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: null,
				insecureTransportUsed: true,
			);
		} catch (\Throwable $exception) {
			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Fetch error (insecure fallback): " . $exception->getMessage(),
			);
		}
	}

	/**
	 * Fetch multiple URLs concurrently, returning keyed FetchResult objects.
	 * Failed requests return a FetchResult with successful=false and error message.
	 * Uses Guzzle Pool for concurrent HTTP with configurable parallelism.
	 *
	 * @param array<string, string> $urls Keyed array: ["label" => "https://..."]
	 * @param int $timeoutSeconds Timeout per individual request
	 * @param int $concurrency Max simultaneous in-flight requests
	 * @return array<string, FetchResult> Results keyed to match input keys
	 */
	public function fetchResourcesConcurrent(array $urls, int $timeoutSeconds = 5, int $concurrency = 5): array
	{
		if (empty($urls)) {
			return array();
		}

		$userAgent = config("scanning.user_agent");
		$client = new GuzzleClient(array(
			"timeout" => $timeoutSeconds,
			"connect_timeout" => $timeoutSeconds,
			"verify" => true,
			"headers" => array(
				"User-Agent" => $userAgent,
			),
			"allow_redirects" => array(
				"max" => config("scanning.max_redirects", 5),
				"track_redirects" => true,
			),
		));

		$results = array();

		$requests = function () use ($urls) {
			foreach ($urls as $key => $url) {
				yield $key => new Request("GET", $url);
			}
		};

		$pool = new Pool($client, $requests(), array(
			"concurrency" => $concurrency,
			"fulfilled" => function (GuzzleResponse $response, $key) use (&$results, $urls) {
				$body = (string) $response->getBody();
				$statusCode = $response->getStatusCode();
				$headers = $this->normalizeHeaders($response->getHeaders());
				$effectiveUrl = $this->resolveEffectiveUrlFromHeaders($response, $urls[$key]);

				$successful = $statusCode >= 200 && $statusCode < 300 && !empty($body);

				$results[$key] = new FetchResult(
					successful: $successful,
					content: $body,
					headers: $headers,
					httpStatusCode: $statusCode,
					effectiveUrl: $effectiveUrl,
					timeToFirstByte: null,
					totalTransferTime: null,
					errorMessage: $successful ? null : "HTTP {$statusCode}",
				);
			},
			"rejected" => function (\Throwable $exception, $key) use (&$results) {
				Log::warning("Concurrent fetch failed", array(
					"key" => $key,
					"error" => $exception->getMessage(),
				));

				$results[$key] = new FetchResult(
					successful: false,
					content: null,
					headers: array(),
					httpStatusCode: null,
					effectiveUrl: null,
					timeToFirstByte: null,
					totalTransferTime: null,
					errorMessage: "Connection failed: " . $exception->getMessage(),
				);
			},
		));

		$pool->promise()->wait();

		return $results;
	}

	/**
	 * Resolve effective URL from Guzzle PSR-7 response redirect tracking headers.
	 */
	private function resolveEffectiveUrlFromHeaders(GuzzleResponse $response, string $originalUrl): string
	{
		$redirectHistory = $response->getHeader("X-Guzzle-Redirect-History");

		if (!empty($redirectHistory)) {
			return end($redirectHistory) ?: $originalUrl;
		}

		return $originalUrl;
	}

	/**
	 * Execute an HTTP GET request and return a structured FetchResult.
	 */
	private function executeRequest(string $url, int $timeout, bool $captureTimingData, bool $verifySsl = true): FetchResult
	{
		$timeToFirstByte = null;
		$totalTransferTime = null;

		try {
			$guzzleOptions = array(
				"allow_redirects" => array(
					"max" => config("scanning.max_redirects", 5),
					"track_redirects" => true,
				),
				"verify" => $verifySsl,
			);

			if ($captureTimingData) {
				$guzzleOptions["on_stats"] = function (\GuzzleHttp\TransferStats $stats) use (&$timeToFirstByte, &$totalTransferTime) {
					$totalTransferTime = $stats->getTransferTime();
					$handlerStats = $stats->getHandlerStats();
					$timeToFirstByte = $handlerStats["starttransfer_time"] ?? null;
				};
			}

			$response = Http::withOptions($guzzleOptions)
				->timeout($timeout)
				->connectTimeout($timeout)
				->withUserAgent(config("scanning.user_agent"))
				->get($url);

			$httpStatusCode = $response->status();
			$effectiveUrl = $this->resolveEffectiveUrl($response, $url);
			$headers = $this->normalizeHeaders($response->headers());
			$body = $response->body();

			if (!$verifySsl && !$this->isSameHost($url, $effectiveUrl)) {
				return new FetchResult(
					successful: false,
					content: null,
					headers: $headers,
					httpStatusCode: $httpStatusCode,
					effectiveUrl: $effectiveUrl,
					timeToFirstByte: $timeToFirstByte,
					totalTransferTime: $totalTransferTime,
					errorMessage: "Insecure TLS fallback denied cross-host redirect chain.",
				);
			}

			if ($httpStatusCode < 200 || $httpStatusCode >= 300) {
				return new FetchResult(
					successful: false,
					content: $body,
					headers: $headers,
					httpStatusCode: $httpStatusCode,
					effectiveUrl: $effectiveUrl,
					timeToFirstByte: $timeToFirstByte,
					totalTransferTime: $totalTransferTime,
					errorMessage: "Server responded with HTTP {$httpStatusCode}",
				);
			}

			if (empty($body)) {
				return new FetchResult(
					successful: false,
					content: null,
					headers: $headers,
					httpStatusCode: $httpStatusCode,
					effectiveUrl: $effectiveUrl,
					timeToFirstByte: $timeToFirstByte,
					totalTransferTime: $totalTransferTime,
					errorMessage: "Successfully connected (HTTP {$httpStatusCode}), but received empty body",
				);
			}

			$maxSize = config("scanning.max_content_size", 5 * 1024 * 1024);
			if (strlen($body) > $maxSize) {
				return new FetchResult(
					successful: false,
					content: null,
					headers: $headers,
					httpStatusCode: $httpStatusCode,
					effectiveUrl: $effectiveUrl,
					timeToFirstByte: $timeToFirstByte,
					totalTransferTime: $totalTransferTime,
					errorMessage: "Response body exceeds maximum allowed size of " . round($maxSize / 1024 / 1024) . "MB",
				);
			}

			return new FetchResult(
				successful: true,
				content: $body,
				headers: $headers,
				httpStatusCode: $httpStatusCode,
				effectiveUrl: $effectiveUrl,
				timeToFirstByte: $timeToFirstByte,
				totalTransferTime: $totalTransferTime,
				errorMessage: null,
				insecureTransportUsed: !$verifySsl,
			);
		} catch (\Illuminate\Http\Client\ConnectionException $exception) {
			Log::warning("HttpFetcher connection failed", array("url" => $url, "error" => $exception->getMessage()));

			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Connection failed: " . $exception->getMessage(),
			);
		} catch (\Throwable $exception) {
			Log::error("HttpFetcher unexpected error", array("url" => $url, "error" => $exception->getMessage()));

			return new FetchResult(
				successful: false,
				content: null,
				headers: array(),
				httpStatusCode: null,
				effectiveUrl: null,
				timeToFirstByte: null,
				totalTransferTime: null,
				errorMessage: "Fetch error: " . $exception->getMessage(),
			);
		}
	}

	/**
	 * Determine the final URL after redirects using Guzzle's redirect tracking.
	 */
	private function resolveEffectiveUrl(\Illuminate\Http\Client\Response $response, string $originalUrl): string
	{
		$redirectHistory = $response->header("X-Guzzle-Redirect-History");

		if (!empty($redirectHistory)) {
			$redirects = is_array($redirectHistory) ? $redirectHistory : explode(", ", $redirectHistory);

			return end($redirects) ?: $originalUrl;
		}

		return $originalUrl;
	}

	/**
	 * Normalize response headers to lowercase-keyed associative array.
	 * Multi-value headers are stored as arrays.
	 */
	private function normalizeHeaders(array $rawHeaders): array
	{
		$normalized = array();

		foreach ($rawHeaders as $name => $values) {
			$key = strtolower($name);
			$normalized[$key] = count($values) === 1 ? $values[0] : $values;
		}

		return $normalized;
	}

	/**
	 * Check if an error message indicates an SSL/TLS certificate failure.
	 */
	private function isSslError(?string $errorMessage): bool
	{
		if ($errorMessage === null) {
			return false;
		}

		$sslPatterns = array(
			"SSL certificate",
			"SSL: no alternative",
			"certificate verify failed",
			"unable to get local issuer",
			"self.signed certificate",
			"CERT_",
			"ssl_error",
		);

		$lowerError = strtolower($errorMessage);

		foreach ($sslPatterns as $pattern) {
			if (str_contains($lowerError, strtolower($pattern))) {
				return true;
			}
		}

		return false;
	}

	private function shouldAllowInsecureFallback(string $url): bool
	{
		$scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? "");
		$host = parse_url($url, PHP_URL_HOST);

		if (!in_array($scheme, array("http", "https"), true) || empty($host)) {
			return false;
		}

		if (in_array(strtolower($host), array("localhost", "127.0.0.1", "::1", "0.0.0.0"), true)) {
			return false;
		}

		$resolvedIps = gethostbynamel($host);
		if ($resolvedIps === false || empty($resolvedIps)) {
			return false;
		}

		foreach ($resolvedIps as $ip) {
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
				return false;
			}
		}

		return true;
	}

	private function isSameHost(string $sourceUrl, ?string $targetUrl): bool
	{
		$sourceHost = strtolower(parse_url($sourceUrl, PHP_URL_HOST) ?? "");
		$targetHost = strtolower(parse_url((string) $targetUrl, PHP_URL_HOST) ?? "");

		return $sourceHost !== "" && $sourceHost === $targetHost;
	}
}
