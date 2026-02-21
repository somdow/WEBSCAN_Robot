<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;

/**
 * Checks whether URL variations (www vs non-www, trailing slash vs no slash)
 * properly redirect to the preferred version. Serving the same content at
 * multiple URLs splits ranking signals and causes duplicate indexing.
 *
 * Site-wide scope — URL normalization is a domain-level configuration.
 */
class DuplicateUrlAnalyzer implements AnalyzerInterface
{
	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	public function moduleKey(): string
	{
		return "duplicateUrl";
	}

	public function label(): string
	{
		return "Duplicate URL Detection";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.duplicateUrl", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();
		$issues = 0;
		$warnings = 0;

		$effectiveUrl = $scanContext->effectiveUrl;
		$parsed = parse_url($effectiveUrl);
		$scheme = strtolower($parsed["scheme"] ?? "https");
		$host = strtolower($parsed["host"] ?? "");
		$path = $parsed["path"] ?? "/";

		if ($host === "") {
			$findings[] = array("type" => "info", "message" => "Could not extract domain from URL for duplicate URL testing.");

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		$hasCanonical = !empty($scanContext->canonicalHrefs);

		/* ── Test 1: www vs non-www ── */
		$wwwResult = $this->checkWwwVariation($scheme, $host, $path, $effectiveUrl, $hasCanonical);
		$findings = array_merge($findings, $wwwResult["findings"]);
		$recommendations = array_merge($recommendations, $wwwResult["recommendations"]);
		$issues += $wwwResult["issues"];
		$warnings += $wwwResult["warnings"];

		/* ── Test 2: Trailing slash variation ── */
		$slashResult = $this->checkTrailingSlashVariation($scheme, $host, $path, $effectiveUrl, $hasCanonical);
		$findings = array_merge($findings, $slashResult["findings"]);
		$recommendations = array_merge($recommendations, $slashResult["recommendations"]);
		$issues += $slashResult["issues"];
		$warnings += $slashResult["warnings"];

		/* ── Determine overall status ── */
		if ($issues > 0) {
			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		if ($warnings > 0) {
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		if (empty($findings)) {
			$findings[] = array("type" => "ok", "message" => "URL variations are properly handled. No duplicate content risk detected.");
		}

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Check if the www/non-www alternate resolves to the preferred URL.
	 */
	private function checkWwwVariation(string $scheme, string $host, string $path, string $effectiveUrl, bool $hasCanonical): array
	{
		$findings = array();
		$recommendations = array();
		$issues = 0;
		$warnings = 0;

		$isWww = str_starts_with($host, "www.");
		$alternateHost = $isWww ? substr($host, 4) : "www.{$host}";
		$alternateUrl = "{$scheme}://{$alternateHost}{$path}";
		$preferredLabel = $isWww ? "www" : "non-www";
		$alternateLabel = $isWww ? "non-www" : "www";

		/* First check: does the alternate return a redirect status code? */
		$rawResult = $this->httpFetcher->fetchWithoutRedirects($alternateUrl, 8);

		if (!$rawResult->successful && $rawResult->httpStatusCode === null) {
			$findings[] = array("type" => "ok", "message" => "The {$alternateLabel} version ({$alternateHost}) is not accessible — no duplicate content risk from www/non-www variations.");
			$findings[] = array("type" => "data", "key" => "wwwRedirectStatus", "value" => "alternate_not_accessible");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		$statusCode = $rawResult->httpStatusCode ?? 0;
		$isRedirect = $statusCode >= 300 && $statusCode < 400;

		if ($isRedirect) {
			$findings[] = array("type" => "ok", "message" => "The {$alternateLabel} version ({$alternateHost}) properly redirects ({$statusCode}) to the preferred {$preferredLabel} version.");
			$findings[] = array("type" => "data", "key" => "wwwRedirectStatus", "value" => "redirects_correctly");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		/* Non-200 responses (bot challenges, 403s, etc.) mean the alternate isn't serving real content */
		if ($statusCode !== 200) {
			$findings[] = array("type" => "ok", "message" => "The {$alternateLabel} version ({$alternateHost}) does not serve page content (HTTP {$statusCode}) — no duplicate content risk.");
			$findings[] = array("type" => "data", "key" => "wwwRedirectStatus", "value" => "alternate_not_serving_content");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		/* Check if the 200 response contains a meta/JS redirect or canonical pointing to preferred URL */
		if ($this->responseRedirectsTo($rawResult->content, $host)) {
			$findings[] = array("type" => "ok", "message" => "The {$alternateLabel} version ({$alternateHost}) redirects to the preferred {$preferredLabel} version via client-side redirect or canonical tag.");
			$findings[] = array("type" => "data", "key" => "wwwRedirectStatus", "value" => "redirects_correctly");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		/* Alternate genuinely serves its own content — duplicate */
		if ($hasCanonical) {
			$findings[] = array("type" => "warning", "message" => "The {$alternateLabel} version ({$alternateHost}) serves content instead of redirecting. Your canonical tag helps, but a server-side redirect is more reliable.");
			$findings[] = array("type" => "data", "key" => "wwwRedirectStatus", "value" => "serves_content_with_canonical");
			$recommendations[] = "Set up a 301 redirect from {$alternateHost} to {$host} in your server configuration. While your canonical tag mitigates the risk, a redirect is the definitive fix.";
			$warnings++;
		} else {
			$findings[] = array("type" => "bad", "message" => "The {$alternateLabel} version ({$alternateHost}) serves content without redirecting and no canonical tag is set. Search engines may index both versions, splitting your ranking signals.");
			$findings[] = array("type" => "data", "key" => "wwwRedirectStatus", "value" => "serves_content_no_canonical");
			$recommendations[] = "Set up a 301 redirect from {$alternateHost} to {$host}. Additionally, add a canonical tag on all pages pointing to the preferred URL version.";
			$issues++;
		}

		return array("findings" => $findings, "recommendations" => $recommendations, "issues" => $issues, "warnings" => $warnings);
	}

	/**
	 * Check if the trailing slash variation resolves to the preferred URL.
	 */
	private function checkTrailingSlashVariation(string $scheme, string $host, string $path, string $effectiveUrl, bool $hasCanonical): array
	{
		$findings = array();
		$recommendations = array();
		$issues = 0;
		$warnings = 0;

		/* Only test non-root paths — root "/" doesn't have a meaningful slash variation */
		$normalizedPath = rtrim($path, "/");
		if ($normalizedPath === "" || $normalizedPath === "/") {
			$findings[] = array("type" => "data", "key" => "trailingSlashStatus", "value" => "root_path_skipped");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		$hasTrailingSlash = str_ends_with($path, "/");
		$alternatePath = $hasTrailingSlash ? rtrim($path, "/") : $path . "/";
		$alternateUrl = "{$scheme}://{$host}{$alternatePath}";

		$rawResult = $this->httpFetcher->fetchWithoutRedirects($alternateUrl, 8);

		if (!$rawResult->successful && $rawResult->httpStatusCode === null) {
			$findings[] = array("type" => "ok", "message" => "The trailing slash variation is not accessible — no duplicate content risk from slash inconsistency.");
			$findings[] = array("type" => "data", "key" => "trailingSlashStatus", "value" => "alternate_not_accessible");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		$statusCode = $rawResult->httpStatusCode ?? 0;
		$isRedirect = $statusCode >= 300 && $statusCode < 400;

		if ($isRedirect) {
			$findings[] = array("type" => "ok", "message" => "Trailing slash variation properly redirects ({$statusCode}) to the preferred URL format.");
			$findings[] = array("type" => "data", "key" => "trailingSlashStatus", "value" => "redirects_correctly");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		if ($statusCode !== 200) {
			$findings[] = array("type" => "ok", "message" => "The trailing slash variation does not serve page content (HTTP {$statusCode}) — no duplicate content risk.");
			$findings[] = array("type" => "data", "key" => "trailingSlashStatus", "value" => "alternate_not_serving_content");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		/* Check if the 200 response contains a meta/JS redirect or canonical pointing to preferred URL */
		if ($this->responseRedirectsTo($rawResult->content, $host)) {
			$findings[] = array("type" => "ok", "message" => "Trailing slash variation redirects to the preferred URL format via client-side redirect or canonical tag.");
			$findings[] = array("type" => "data", "key" => "trailingSlashStatus", "value" => "redirects_correctly");

			return array("findings" => $findings, "recommendations" => $recommendations, "issues" => 0, "warnings" => 0);
		}

		/* Both versions serve content (200) */
		if ($hasCanonical) {
			$findings[] = array("type" => "warning", "message" => "Both trailing-slash and non-trailing-slash versions serve content. Your canonical tag helps mitigate the duplicate risk, but a redirect is more reliable.");
			$findings[] = array("type" => "data", "key" => "trailingSlashStatus", "value" => "serves_content_with_canonical");
			$recommendations[] = "Configure your server to 301-redirect one trailing slash variant to the other for consistent URL handling.";
			$warnings++;
		} else {
			$findings[] = array("type" => "warning", "message" => "Both trailing-slash and non-trailing-slash versions serve content without a canonical tag. This can cause duplicate indexing for inner pages.");
			$findings[] = array("type" => "data", "key" => "trailingSlashStatus", "value" => "serves_content_no_canonical");
			$recommendations[] = "Choose a preferred URL format (with or without trailing slash) and 301-redirect the other. Add canonical tags as a backup signal.";
			$warnings++;
		}

		return array("findings" => $findings, "recommendations" => $recommendations, "issues" => $issues, "warnings" => $warnings);
	}

	/**
	 * Check if a 200 response body contains a canonical tag, meta refresh,
	 * or JS redirect pointing to the preferred host. CDNs like Cloudflare
	 * often return 200 with client-side redirects instead of server-side 3xx.
	 */
	private function responseRedirectsTo(?string $html, string $preferredHost): bool
	{
		if (empty($html)) {
			return false;
		}

		$preferredHostLower = strtolower($preferredHost);
		$htmlLower = strtolower($html);

		/* Canonical tag pointing to preferred host */
		if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
			$canonicalHost = strtolower(parse_url($matches[1], PHP_URL_HOST) ?? "");
			if ($canonicalHost === $preferredHostLower) {
				return true;
			}
		}

		/* Meta refresh redirect */
		if (preg_match('/content=["\'][^"\']*url\s*=\s*([^"\';\s]+)/i', $html, $matches)) {
			$refreshHost = strtolower(parse_url($matches[1], PHP_URL_HOST) ?? "");
			if ($refreshHost === $preferredHostLower) {
				return true;
			}
		}

		/* JS redirect (window.location, location.href, location.replace) */
		if (preg_match('/(?:window\.)?location(?:\.href)?\s*=\s*["\']([^"\']+)["\']/i', $html, $matches)) {
			$jsHost = strtolower(parse_url($matches[1], PHP_URL_HOST) ?? "");
			if ($jsHost === $preferredHostLower) {
				return true;
			}
		}

		if (preg_match('/location\.replace\s*\(\s*["\']([^"\']+)["\']\s*\)/i', $html, $matches)) {
			$jsHost = strtolower(parse_url($matches[1], PHP_URL_HOST) ?? "");
			if ($jsHost === $preferredHostLower) {
				return true;
			}
		}

		return false;
	}
}
