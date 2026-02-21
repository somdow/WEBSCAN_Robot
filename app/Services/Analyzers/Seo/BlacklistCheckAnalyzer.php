<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\WebRiskClient;

/**
 * Checks whether a website is flagged for malware, phishing, or unwanted software
 * using Google Safe Browsing v4 (free) or Web Risk API (commercial).
 *
 * Site-wide scope — domain reputation applies to the entire site, runs once per scan.
 * Admin toggle in Site Settings controls which Google endpoint is used.
 * Degrades gracefully when API key is not configured (returns Info status with guidance).
 */
class BlacklistCheckAnalyzer implements AnalyzerInterface
{
	/** Human-readable labels for Google Web Risk threat type codes. */
	private const THREAT_LABELS = array(
		"MALWARE" => "Malware",
		"SOCIAL_ENGINEERING" => "Phishing / Social Engineering",
		"UNWANTED_SOFTWARE" => "Unwanted Software",
	);

	public function __construct(
		private readonly WebRiskClient $webRiskClient,
	) {}

	public function moduleKey(): string
	{
		return "blacklistCheck";
	}

	public function label(): string
	{
		return "Blacklist & Malware Check";
	}

	public function category(): string
	{
		return "Security";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.blacklistCheck", 10);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();

		$domain = $this->extractDomainFromUrl($scanContext->effectiveUrl);

		if ($domain === null) {
			$findings[] = array(
				"type" => "info",
				"message" => "Could not extract domain from URL for blacklist checking.",
			);

			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: $findings,
				recommendations: $recommendations,
			);
		}

		$findings[] = array("type" => "data", "key" => "checkedDomain", "value" => $domain);

		/* Check if API key is configured */
		if (!$this->webRiskClient->isConfigured()) {
			$findings[] = array(
				"type" => "info",
				"message" => "Google API key not configured. Add your API key in Admin > Site Settings to enable malware and phishing detection.",
			);

			$recommendations[] = "Configure a Google API key in Site Settings to enable blacklist checking. Enable the Safe Browsing API (free) or Web Risk API (commercial) on your Google Cloud project.";

			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: $findings,
				recommendations: $recommendations,
			);
		}

		$apiMode = $this->webRiskClient->isCommercialMode() ? "Web Risk" : "Safe Browsing";

		/* Run the Google threat check */
		$result = $this->webRiskClient->checkUrl($scanContext->effectiveUrl);

		if (!$result["success"]) {
			$findings[] = array(
				"type" => "info",
				"message" => "Google {$apiMode}: Check failed — " . $result["error"],
			);

			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: $findings,
				recommendations: $recommendations,
			);
		}

		/* Determine status based on threats */
		if (empty($result["threats"])) {
			$findings[] = array(
				"type" => "ok",
				"message" => "Google {$apiMode}: No threats detected — site is not flagged for malware, phishing, or unwanted software.",
			);

			return new AnalysisResult(
				status: ModuleStatus::Ok,
				findings: $findings,
				recommendations: $recommendations,
			);
		}

		/* Threats found — build detailed findings */
		$threatLabels = array_map(
			fn(string $threatType) => self::THREAT_LABELS[$threatType] ?? $threatType,
			$result["threats"],
		);

		$findings[] = array(
			"type" => "bad",
			"message" => "Google {$apiMode}: Site flagged for " . implode(", ", $threatLabels) . ".",
		);

		$findings[] = array(
			"type" => "bad",
			"message" => "Browsers like Chrome, Firefox, and Safari will show interstitial warnings to visitors, severely impacting traffic and trust.",
		);

		$recommendations[] = "Critical: Your site is flagged by Google for security threats. Visit Google Search Console's Security Issues report to identify the specific problem, fix it, then request a review.";
		$recommendations[] = "Check your site for injected malicious code, compromised files, or unauthorized redirects. Scan all server files and database content for suspicious modifications.";

		return new AnalysisResult(
			status: ModuleStatus::Bad,
			findings: $findings,
			recommendations: $recommendations,
		);
	}

	private function extractDomainFromUrl(string $url): ?string
	{
		$parsed = parse_url($url);

		return $parsed["host"] ?? null;
	}
}
