<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

/**
 * Checks server compression (gzip/brotli), browser caching headers,
 * and asset minification signals from the page's HTML and response headers.
 */
class CompressionCheckAnalyzer implements AnalyzerInterface
{
	/** Minimum cache duration (seconds) considered acceptable for static assets. */
	private const MIN_CACHE_SECONDS = 86400;

	/** Cache duration (seconds) considered good practice (30 days). */
	private const GOOD_CACHE_SECONDS = 2592000;

	public function moduleKey(): string
	{
		return "compressionCheck";
	}

	public function label(): string
	{
		return "Compression & Caching";
	}

	public function category(): string
	{
		return "Usability & Performance";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.compressionCheck", 4);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();
		$issueCount = 0;

		$issueCount += $this->checkCompression($scanContext->responseHeaders, $findings, $recommendations);
		$this->measureHtmlSize($scanContext->htmlContent, $findings);
		$issueCount += $this->checkCacheHeaders($scanContext->responseHeaders, $findings, $recommendations);
		$issueCount += $this->checkAssetMinification($scanContext->xpath, $findings, $recommendations);

		$status = $this->determineStatus($issueCount);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Check if the server returns gzip, brotli, or deflate content encoding.
	 */
	private function checkCompression(array $headers, array &$findings, array &$recommendations): int
	{
		$encoding = $this->getHeaderValue($headers, "content-encoding");

		if ($encoding === null) {
			$findings[] = array("type" => "bad", "message" => "No content compression detected. The server is not using gzip, brotli, or deflate encoding.");
			$recommendations[] = "Enable gzip or brotli compression on your web server. This typically reduces HTML transfer size by 60-80%, improving load times significantly.";
			return 2;
		}

		$encodingLower = strtolower($encoding);

		if (str_contains($encodingLower, "br")) {
			$findings[] = array("type" => "ok", "message" => "Brotli compression enabled (Content-Encoding: {$encoding}). This is the most efficient compression algorithm for web content.");
			return 0;
		}

		if (str_contains($encodingLower, "gzip")) {
			$findings[] = array("type" => "ok", "message" => "Gzip compression enabled (Content-Encoding: {$encoding}).");
			return 0;
		}

		if (str_contains($encodingLower, "deflate")) {
			$findings[] = array("type" => "warning", "message" => "Deflate compression detected (Content-Encoding: {$encoding}). Consider upgrading to gzip or brotli for better compression ratios.");
			$recommendations[] = "Switch from deflate to gzip or brotli compression for improved performance.";
			return 1;
		}

		$findings[] = array("type" => "info", "message" => "Content-Encoding header present: {$encoding}.");
		return 0;
	}

	/**
	 * Report the raw HTML document size in kilobytes.
	 */
	private function measureHtmlSize(string $htmlContent, array &$findings): void
	{
		$sizeBytes = strlen($htmlContent);
		$sizeKb = round($sizeBytes / 1024, 1);

		if ($sizeKb > 200) {
			$findings[] = array("type" => "warning", "message" => "HTML document size: {$sizeKb} KB. Pages over 200 KB may benefit from code splitting or removing inline resources.");
		} elseif ($sizeKb > 100) {
			$findings[] = array("type" => "info", "message" => "HTML document size: {$sizeKb} KB.");
		} else {
			$findings[] = array("type" => "ok", "message" => "HTML document size: {$sizeKb} KB — lightweight.");
		}
	}

	/**
	 * Evaluate Cache-Control and ETag response headers.
	 */
	private function checkCacheHeaders(array $headers, array &$findings, array &$recommendations): int
	{
		$issueCount = 0;

		$cacheControl = $this->getHeaderValue($headers, "cache-control");
		$etag = $this->getHeaderValue($headers, "etag");
		$expires = $this->getHeaderValue($headers, "expires");

		if ($cacheControl === null && $expires === null) {
			$findings[] = array("type" => "warning", "message" => "No Cache-Control or Expires header found. Browsers cannot efficiently cache this page.");
			$recommendations[] = "Add a Cache-Control header to tell browsers how long to cache the page. For dynamic pages, use 'no-cache' with ETag for conditional requests. For static pages, set an appropriate max-age.";
			$issueCount++;
		} elseif ($cacheControl !== null) {
			$maxAge = $this->extractMaxAge($cacheControl);

			if (str_contains(strtolower($cacheControl), "no-store")) {
				$findings[] = array("type" => "info", "message" => "Cache-Control: {$cacheControl} — caching explicitly disabled.");
			} elseif ($maxAge !== null && $maxAge >= self::GOOD_CACHE_SECONDS) {
				$durationLabel = $this->humanizeDuration($maxAge);
				$findings[] = array("type" => "ok", "message" => "Cache-Control max-age: {$durationLabel} — good caching policy.");
			} elseif ($maxAge !== null && $maxAge >= self::MIN_CACHE_SECONDS) {
				$durationLabel = $this->humanizeDuration($maxAge);
				$findings[] = array("type" => "ok", "message" => "Cache-Control max-age: {$durationLabel}.");
			} elseif ($maxAge !== null && $maxAge > 0) {
				$durationLabel = $this->humanizeDuration($maxAge);
				$findings[] = array("type" => "info", "message" => "Cache-Control max-age: {$durationLabel} — relatively short cache duration.");
			} else {
				$findings[] = array("type" => "info", "message" => "Cache-Control: {$cacheControl}.");
			}
		}

		if ($etag !== null) {
			$findings[] = array("type" => "ok", "message" => "ETag header present — enables conditional requests (304 Not Modified).");
		}

		return $issueCount;
	}

	/**
	 * Check external CSS and JS files for minification signals.
	 * Counts files with .min. in URL vs those without.
	 */
	private function checkAssetMinification(\DOMXPath $xpath, array &$findings, array &$recommendations): int
	{
		$cssResults = $this->analyzeAssetMinification($xpath, "//link[@rel='stylesheet']", "href", "CSS");
		$jsResults = $this->analyzeAssetMinification($xpath, "//script[@src]", "src", "JS");

		$issueCount = 0;

		if ($cssResults["total"] > 0) {
			$findings[] = array(
				"type" => $cssResults["unminified"] > 0 ? "warning" : "ok",
				"message" => "CSS files: {$cssResults["minified"]}/{$cssResults["total"]} appear minified.",
			);
			if ($cssResults["unminified"] > 2) {
				$recommendations[] = "Minify CSS files to reduce transfer size. {$cssResults["unminified"]} file(s) don't appear to be minified.";
				$issueCount++;
			}
		}

		if ($jsResults["total"] > 0) {
			$findings[] = array(
				"type" => $jsResults["unminified"] > 0 ? "warning" : "ok",
				"message" => "JS files: {$jsResults["minified"]}/{$jsResults["total"]} appear minified.",
			);
			if ($jsResults["unminified"] > 2) {
				$recommendations[] = "Minify JavaScript files to reduce transfer size. {$jsResults["unminified"]} file(s) don't appear to be minified.";
				$issueCount++;
			}
		}

		return $issueCount;
	}

	/**
	 * Analyze a set of asset URLs for minification indicators.
	 * Checks for .min. in the filename or common build tool output patterns.
	 */
	private function analyzeAssetMinification(\DOMXPath $xpath, string $query, string $attribute, string $label): array
	{
		$nodes = $xpath->query($query);
		$total = 0;
		$minified = 0;

		if ($nodes === false || $nodes->length === 0) {
			return array("total" => 0, "minified" => 0, "unminified" => 0);
		}

		for ($index = 0; $index < $nodes->length; $index++) {
			$node = $nodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$url = $node->getAttribute($attribute);
			if ($url === "" || $this->isInlineOrDataUrl($url)) {
				continue;
			}

			$total++;

			if ($this->appearsMinified($url)) {
				$minified++;
			}
		}

		return array("total" => $total, "minified" => $minified, "unminified" => $total - $minified);
	}

	/**
	 * Determine if a URL appears to reference a minified file.
	 * Checks for .min. suffix, build tool hash patterns, and CDN paths.
	 */
	private function appearsMinified(string $url): bool
	{
		$urlLower = strtolower($url);

		/** .min.css / .min.js pattern */
		if (str_contains($urlLower, ".min.")) {
			return true;
		}

		/** Build tool hashed filenames (e.g., app-Bx3f8k.js, style.abc123.css) */
		if (preg_match("/[\.\-_][a-f0-9]{6,}\.(css|js)/i", $url)) {
			return true;
		}

		/** CDN-hosted libraries are virtually always minified */
		$cdnPatterns = array("cdnjs.cloudflare.com", "cdn.jsdelivr.net", "unpkg.com", "ajax.googleapis.com", "stackpath.bootstrapcdn.com", "cdn.tailwindcss.com");
		foreach ($cdnPatterns as $cdnPattern) {
			if (str_contains($urlLower, $cdnPattern)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a URL is inline (data: URI) or empty.
	 */
	private function isInlineOrDataUrl(string $url): bool
	{
		return str_starts_with(trim($url), "data:") || str_starts_with(trim($url), "blob:");
	}

	/**
	 * Extract the max-age value in seconds from a Cache-Control header.
	 */
	private function extractMaxAge(string $cacheControl): ?int
	{
		if (preg_match("/max-age\s*=\s*(\d+)/i", $cacheControl, $matches)) {
			return (int) $matches[1];
		}

		return null;
	}

	/**
	 * Convert seconds into a human-readable duration string.
	 */
	private function humanizeDuration(int $seconds): string
	{
		if ($seconds >= 86400) {
			$days = round($seconds / 86400);
			return "{$days} day" . ($days !== 1 ? "s" : "");
		}

		if ($seconds >= 3600) {
			$hours = round($seconds / 3600);
			return "{$hours} hour" . ($hours !== 1 ? "s" : "");
		}

		return "{$seconds} seconds";
	}

	/**
	 * Get a response header value (case-insensitive lookup).
	 */
	private function getHeaderValue(array $headers, string $headerName): ?string
	{
		$headerNameLower = strtolower($headerName);

		foreach ($headers as $key => $value) {
			if (strtolower($key) === $headerNameLower) {
				return is_array($value) ? ($value[0] ?? null) : $value;
			}
		}

		return null;
	}

	private function determineStatus(int $issueCount): ModuleStatus
	{
		if ($issueCount === 0) {
			return ModuleStatus::Ok;
		}

		if ($issueCount <= 2) {
			return ModuleStatus::Warning;
		}

		return ModuleStatus::Bad;
	}
}
