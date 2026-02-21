<?php

namespace App\Services\Analyzers\Security;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Checks whether the server sends recommended security response headers.
 *
 * Site-wide scope — security headers are configured at the server level and
 * apply consistently across pages. Runs once per scan against homepage headers.
 */
class SecurityHeadersAnalyzer implements AnalyzerInterface
{
	/**
	 * Security headers to check, with human-readable labels and recommendations.
	 * Keys must match the lowercase header names from $scanContext->responseHeaders.
	 */
	private const SECURITY_HEADERS = array(
		array(
			"header" => "content-security-policy",
			"label" => "Content-Security-Policy",
			"description" => "Prevents cross-site scripting (XSS) and code injection attacks by controlling which resources the browser is allowed to load.",
			"recommendation" => "Add a Content-Security-Policy header. Start with a report-only policy to identify violations before enforcing: Content-Security-Policy: default-src 'self'; script-src 'self'",
		),
		array(
			"header" => "x-frame-options",
			"label" => "X-Frame-Options",
			"description" => "Prevents clickjacking attacks by controlling whether the page can be embedded in frames on other sites.",
			"recommendation" => "Add X-Frame-Options: DENY (or SAMEORIGIN if you embed your own pages in frames). Alternatively, use CSP frame-ancestors directive.",
			"alternativeHeader" => "content-security-policy",
			"alternativePattern" => "frame-ancestors",
		),
		array(
			"header" => "strict-transport-security",
			"label" => "Strict-Transport-Security (HSTS)",
			"description" => "Forces browsers to always use HTTPS for your domain, preventing protocol downgrade attacks and cookie hijacking.",
			"recommendation" => "Add Strict-Transport-Security: max-age=31536000; includeSubDomains to enforce HTTPS for at least one year.",
		),
		array(
			"header" => "x-content-type-options",
			"label" => "X-Content-Type-Options",
			"description" => "Prevents browsers from MIME-type sniffing, which can turn non-executable content into executable content.",
			"recommendation" => "Add X-Content-Type-Options: nosniff to prevent MIME-type sniffing attacks.",
		),
		array(
			"header" => "referrer-policy",
			"label" => "Referrer-Policy",
			"description" => "Controls how much referrer information is shared when navigating away from your site, protecting user privacy and preventing data leakage.",
			"recommendation" => "Add Referrer-Policy: strict-origin-when-cross-origin (recommended balance of functionality and privacy).",
		),
		array(
			"header" => "permissions-policy",
			"label" => "Permissions-Policy",
			"description" => "Restricts which browser features (camera, microphone, geolocation, etc.) your site and embedded content can access.",
			"recommendation" => "Add a Permissions-Policy header to disable features you don't use: Permissions-Policy: camera=(), microphone=(), geolocation=()",
		),
	);

	public function moduleKey(): string
	{
		return "securityHeaders";
	}

	public function label(): string
	{
		return "Security Headers";
	}

	public function category(): string
	{
		return "Security";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.securityHeaders", 4);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();
		$headers = $scanContext->responseHeaders;
		$presentCount = 0;

		foreach (self::SECURITY_HEADERS as $headerSpec) {
			$checkResult = $this->checkSingleHeader($headerSpec, $headers);
			$findings[] = $checkResult["finding"];

			if ($checkResult["present"]) {
				$presentCount++;
			} elseif (!empty($headerSpec["recommendation"])) {
				$recommendations[] = $headerSpec["recommendation"];
			}
		}

		$totalHeaders = count(self::SECURITY_HEADERS);

		$findings[] = array(
			"type" => "data",
			"key" => "headersSummary",
			"value" => "{$presentCount} of {$totalHeaders} recommended security headers present",
		);

		$status = match (true) {
			$presentCount >= 5 => ModuleStatus::Ok,
			$presentCount >= 3 => ModuleStatus::Warning,
			default => ModuleStatus::Bad,
		};

		return new AnalysisResult(
			status: $status,
			findings: $findings,
			recommendations: $recommendations,
		);
	}

	/**
	 * Check a single security header against the response headers.
	 * Supports alternative header detection (e.g. CSP frame-ancestors for X-Frame-Options).
	 *
	 * @return array{present: bool, finding: array}
	 */
	private function checkSingleHeader(array $headerSpec, array $headers): array
	{
		$headerValue = $headers[$headerSpec["header"]] ?? null;
		$isPresent = !empty($headerValue);

		/* Check alternative header (e.g. CSP frame-ancestors as substitute for X-Frame-Options) */
		if (!$isPresent && isset($headerSpec["alternativeHeader"], $headerSpec["alternativePattern"])) {
			$alternativeValue = $headers[$headerSpec["alternativeHeader"]] ?? "";
			$valueToCheck = is_array($alternativeValue) ? implode(" ", $alternativeValue) : $alternativeValue;

			if (stripos($valueToCheck, $headerSpec["alternativePattern"]) !== false) {
				$isPresent = true;
				$headerValue = "Via CSP frame-ancestors directive";
			}
		}

		if ($isPresent) {
			$displayValue = is_array($headerValue) ? implode(", ", $headerValue) : $headerValue;
			$truncatedValue = mb_strlen($displayValue) > 120
				? mb_substr($displayValue, 0, 117) . "..."
				: $displayValue;

			return array(
				"present" => true,
				"finding" => array(
					"type" => "ok",
					"message" => "{$headerSpec['label']}: Present — {$truncatedValue}",
				),
			);
		}

		return array(
			"present" => false,
			"finding" => array(
				"type" => "bad",
				"message" => "{$headerSpec['label']}: Missing — {$headerSpec['description']}",
			),
		);
	}
}
