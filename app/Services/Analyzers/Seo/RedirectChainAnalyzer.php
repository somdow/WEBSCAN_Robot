<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Utils\UrlNormalizer;

/**
 * Detects redirects between the requested URL and the final effective URL.
 * Redirect chains waste crawl budget, dilute link equity, and increase page load time.
 */
class RedirectChainAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "redirectChain";
	}

	public function label(): string
	{
		return "Redirect Analysis";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.redirectChain", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$requestedUrl = $scanContext->requestedUrl;
		$effectiveUrl = $scanContext->effectiveUrl;
		$findings = array();
		$recommendations = array();

		$wasRedirected = $this->normalizeUrl($requestedUrl) !== $this->normalizeUrl($effectiveUrl);

		if (!$wasRedirected) {
			$findings[] = array("type" => "ok", "message" => "No redirect detected. The URL resolves directly without any redirects.");
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array(
			"type" => "data",
			"message" => "Requested: {$requestedUrl}",
		);
		$findings[] = array(
			"type" => "data",
			"message" => "Resolved to: {$effectiveUrl}",
		);

		$redirectType = $this->classifyRedirect($requestedUrl, $effectiveUrl);

		switch ($redirectType) {
			case "http_to_https":
				$findings[] = array(
					"type" => "ok",
					"message" => "HTTP to HTTPS redirect detected. This is expected behavior for secure sites.",
				);

				if ($this->hasOtherChanges($requestedUrl, $effectiveUrl, "scheme")) {
					$findings[] = array(
						"type" => "info",
						"message" => "Additional URL changes occurred beyond the protocol upgrade (path or domain changed).",
					);
				}
				break;

			case "www_normalization":
				$findings[] = array(
					"type" => "ok",
					"message" => "WWW normalization redirect detected (www/non-www). This is standard practice.",
				);
				break;

			case "trailing_slash":
				$findings[] = array(
					"type" => "ok",
					"message" => "Trailing slash normalization detected. Consistent slash handling prevents duplicate content.",
				);
				break;

			case "domain_change":
				$findings[] = array(
					"type" => "warning",
					"message" => "Domain redirect detected — the URL resolves to a different domain. Ensure this is intentional and uses a 301 (permanent) redirect to pass link equity.",
				);
				$recommendations[] = "Verify this domain redirect is configured as a 301 permanent redirect. 302 temporary redirects do not transfer full link equity.";
				break;

			case "path_change":
				$findings[] = array(
					"type" => "warning",
					"message" => "Path redirect detected — the URL content has moved. Ensure old URLs return 301 status to preserve search rankings.",
				);
				$recommendations[] = "Audit your redirect rules to ensure moved pages use 301 redirects. Update internal links to point directly to the new URL to avoid unnecessary redirect hops.";
				break;

			default:
				$findings[] = array(
					"type" => "warning",
					"message" => "URL redirect detected. The requested URL does not resolve directly, which adds latency and may affect crawl efficiency.",
				);
				$recommendations[] = "Update internal links and sitemaps to reference the final destination URL directly. Each redirect adds ~100-200ms of latency.";
				break;
		}

		$status = $this->determineStatus($redirectType);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Classify the type of redirect based on URL differences.
	 */
	private function classifyRedirect(string $requestedUrl, string $effectiveUrl): string
	{
		$requested = parse_url($requestedUrl);
		$effective = parse_url($effectiveUrl);

		$requestedScheme = strtolower($requested["scheme"] ?? "http");
		$effectiveScheme = strtolower($effective["scheme"] ?? "http");
		$requestedHost = strtolower($requested["host"] ?? "");
		$effectiveHost = strtolower($effective["host"] ?? "");
		$requestedPath = rtrim($requested["path"] ?? "/", "/");
		$effectivePath = rtrim($effective["path"] ?? "/", "/");

		if ($requestedScheme === "http" && $effectiveScheme === "https" && $requestedHost === $effectiveHost && $requestedPath === $effectivePath) {
			return "http_to_https";
		}

		$requestedHostBase = ltrim($requestedHost, "www.");
		$effectiveHostBase = ltrim($effectiveHost, "www.");

		if ($requestedHostBase === $effectiveHostBase && $requestedHost !== $effectiveHost && $requestedPath === $effectivePath) {
			return "www_normalization";
		}

		if ($requestedHost === $effectiveHost) {
			$reqPathNormalized = rtrim($requested["path"] ?? "/", "/");
			$effPathNormalized = rtrim($effective["path"] ?? "/", "/");

			if ($reqPathNormalized === $effPathNormalized) {
				return "trailing_slash";
			}

			return "path_change";
		}

		if ($requestedHostBase !== $effectiveHostBase) {
			return "domain_change";
		}

		return "other";
	}

	/**
	 * Check if there are URL changes beyond the specified component.
	 */
	private function hasOtherChanges(string $requestedUrl, string $effectiveUrl, string $ignoreComponent): bool
	{
		$requested = parse_url($requestedUrl);
		$effective = parse_url($effectiveUrl);

		$components = array("host", "path", "query");

		foreach ($components as $component) {
			if ($component === $ignoreComponent) {
				continue;
			}

			$requestedValue = strtolower(trim($requested[$component] ?? "", "/"));
			$effectiveValue = strtolower(trim($effective[$component] ?? "", "/"));

			if ($requestedValue !== $effectiveValue) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize a URL for redirect comparison: lowercase scheme+host,
	 * strip trailing slash, preserve query strings.
	 */
	private function normalizeUrl(string $url): string
	{
		return UrlNormalizer::forCrawl($url);
	}

	private function determineStatus(string $redirectType): ModuleStatus
	{
		$benignRedirects = array("http_to_https", "www_normalization", "trailing_slash");

		if (in_array($redirectType, $benignRedirects, true)) {
			return ModuleStatus::Ok;
		}

		return ModuleStatus::Warning;
	}
}
