<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;

/**
 * Proactively checks whether the HTTP version of a site properly 301-redirects
 * to HTTPS. Unlike RedirectChainAnalyzer (which only checks the user's entered URL),
 * this module always tests http:// regardless of what was entered.
 *
 * Site-wide scope — HTTPS configuration applies to the entire domain.
 */
class HttpsRedirectAnalyzer implements AnalyzerInterface
{
	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	public function moduleKey(): string
	{
		return "httpsRedirect";
	}

	public function label(): string
	{
		return "HTTPS Redirect";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.httpsRedirect", 7);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();

		$effectiveUrl = $scanContext->effectiveUrl;
		$parsed = parse_url($effectiveUrl);
		$scheme = strtolower($parsed["scheme"] ?? "http");
		$host = strtolower($parsed["host"] ?? "");

		if ($host === "") {
			$findings[] = array("type" => "info", "message" => "Could not extract domain from URL for HTTPS redirect testing.");

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		/* If the site resolved to HTTP, it has no HTTPS at all */
		if ($scheme === "http") {
			$findings[] = array("type" => "bad", "message" => "Your site is served over HTTP without HTTPS encryption. Search engines penalize non-HTTPS sites and browsers show \"Not Secure\" warnings to visitors.");
			$recommendations[] = "Install an SSL/TLS certificate and configure your server to serve all pages over HTTPS. Most hosting providers offer free certificates via Let's Encrypt.";
			$recommendations[] = "Once HTTPS is active, set up a 301 redirect from http:// to https:// so all traffic and link equity is consolidated.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		/* Site is HTTPS — now test if HTTP properly redirects */
		$httpUrl = "http://{$host}" . ($parsed["path"] ?? "/");

		$fetchResult = $this->httpFetcher->fetchWithoutRedirects($httpUrl);

		/* Connection failed — HTTP port not accessible (fine, HTTPS-only) */
		if (!$fetchResult->successful) {
			$findings[] = array("type" => "ok", "message" => "HTTP connection is not accessible — your site is HTTPS-only. Visitors cannot accidentally access an insecure version.");
			$findings[] = array("type" => "data", "key" => "httpsRedirectStatus", "value" => "https_only");

			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		$statusCode = $fetchResult->httpStatusCode;
		$locationHeader = $fetchResult->headers["location"] ?? null;

		$findings[] = array("type" => "data", "key" => "httpStatusCode", "value" => $statusCode);
		$findings[] = array("type" => "data", "key" => "httpRedirectTarget", "value" => $locationHeader);

		/* 301 permanent redirect — ideal */
		if ($statusCode === 301 && $locationHeader !== null) {
			$redirectsToHttps = str_starts_with(strtolower($locationHeader), "https://");

			if ($redirectsToHttps) {
				$findings[] = array("type" => "ok", "message" => "HTTP properly 301-redirects to HTTPS ({$locationHeader}). This is the ideal configuration for SEO.");
				$findings[] = array("type" => "data", "key" => "httpsRedirectStatus", "value" => "301_to_https");

				return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
			}

			$findings[] = array("type" => "warning", "message" => "HTTP 301-redirects to {$locationHeader}, but the target is not HTTPS. The redirect chain should end at an HTTPS URL.");
			$recommendations[] = "Update your HTTP redirect to point directly to the HTTPS version of your site.";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		/* 302/307 temporary redirect — works but not ideal for SEO */
		if (in_array($statusCode, array(302, 307), true) && $locationHeader !== null) {
			$redirectsToHttps = str_starts_with(strtolower($locationHeader), "https://");
			$redirectLabel = $redirectsToHttps ? "HTTPS" : $locationHeader;

			$findings[] = array("type" => "warning", "message" => "HTTP uses a {$statusCode} temporary redirect to {$redirectLabel}. Temporary redirects do not transfer full link equity to the HTTPS version.");
			$findings[] = array("type" => "data", "key" => "httpsRedirectStatus", "value" => "{$statusCode}_temporary");
			$recommendations[] = "Change the HTTP-to-HTTPS redirect from a {$statusCode} (temporary) to a 301 (permanent) redirect. This ensures search engines transfer all ranking signals to your HTTPS URL.";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		/* 200 — HTTP serves content without redirecting (duplicate content risk) */
		if ($statusCode === 200) {
			$findings[] = array("type" => "bad", "message" => "HTTP version of your site serves content (HTTP {$statusCode}) instead of redirecting to HTTPS. This creates duplicate content — search engines may index both versions separately, diluting your rankings.");
			$findings[] = array("type" => "data", "key" => "httpsRedirectStatus", "value" => "no_redirect");
			$recommendations[] = "Configure a 301 permanent redirect from http:// to https:// in your server configuration (Apache .htaccess, Nginx config, or hosting panel).";
			$recommendations[] = "Ensure your canonical tags point to the HTTPS version to help search engines consolidate indexing signals.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		/* Other status codes (non-200 2xx like bot challenges, 4xx, 5xx) — not serving real content */
		$findings[] = array("type" => "ok", "message" => "HTTP version returned status {$statusCode} and is not serving page content. Your HTTPS site is active and this is unlikely to cause issues.");
		$findings[] = array("type" => "data", "key" => "httpsRedirectStatus", "value" => "http_not_serving_content");

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}
}
