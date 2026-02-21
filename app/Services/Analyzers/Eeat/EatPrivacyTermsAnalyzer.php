<?php

namespace App\Services\Analyzers\Eeat;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Checks for the presence and content quality of Privacy Policy,
 * Terms of Service, and Cookie Policy pages. Uses mini-crawl data
 * from ScanContext::trustPages.
 */
class EatPrivacyTermsAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "eatPrivacyTerms";
	}

	public function label(): string
	{
		return "Privacy & Terms Pages";
	}

	public function category(): string
	{
		return "E-E-A-T Signals";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.eatPrivacyTerms", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$minContentWords = (int) config("scanning.thresholds.trustPages.minContentWords", 200);
		$findings = array();
		$recommendations = array();

		$privacyPage = $scanContext->getTrustPageByType("privacy");
		$termsPage = $scanContext->getTrustPageByType("terms");
		$cookiePage = $scanContext->getTrustPageByType("cookie");

		$privacyQuality = $this->evaluateLegalPage($privacyPage, "Privacy Policy", $minContentWords, $findings, $recommendations);
		$termsQuality = $this->evaluateLegalPage($termsPage, "Terms of Service", $minContentWords, $findings, $recommendations);

		if ($cookiePage !== null && ($cookiePage["exists"] ?? false)) {
			$findings[] = array("type" => "ok", "message" => "Cookie Policy page found: {$cookiePage["url"]}.");
		}

		if ($privacyQuality && $termsQuality) {
			$findings[] = array("type" => "ok", "message" => "Both Privacy Policy and Terms of Service are present with substantial content. This supports trust and compliance.");
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		if ($privacyQuality || $termsQuality) {
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array("type" => "bad", "message" => "Neither a Privacy Policy nor Terms of Service page was detected with substantial content.");
		return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Evaluate a legal page (Privacy or Terms). Returns true if quality criteria met.
	 */
	private function evaluateLegalPage(?array $page, string $pageName, int $minContentWords, array &$findings, array &$recommendations): bool
	{
		if ($page === null || !($page["exists"] ?? false)) {
			$findings[] = array("type" => "warning", "message" => "No {$pageName} page detected.");
			$recommendations[] = "Create a {$pageName} page. This is important for user trust and may be legally required.";
			return false;
		}

		$wordCount = $page["wordCount"] ?? 0;
		$findings[] = array("type" => "info", "message" => "{$pageName} found: {$page["url"]} ({$wordCount} words).");

		if ($wordCount >= $minContentWords) {
			$findings[] = array("type" => "ok", "message" => "{$pageName} has substantial content ({$wordCount} words).");
			return true;
		}

		$findings[] = array("type" => "warning", "message" => "{$pageName} appears thin ({$wordCount} words). Comprehensive legal pages typically contain {$minContentWords}+ words.");
		$recommendations[] = "Review and expand your {$pageName} to ensure it comprehensively covers the necessary legal aspects.";
		return false;
	}
}
