<?php

namespace App\Services\Analyzers\Eeat;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Evaluates the presence and quality of About and Contact pages,
 * which are key trust signals for E-E-A-T. Uses mini-crawl data
 * from ScanContext::trustPages.
 */
class EatTrustPagesAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "eatTrustPages";
	}

	public function label(): string
	{
		return "Trust Pages (About & Contact)";
	}

	public function category(): string
	{
		return "E-E-A-T Signals";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.eatTrustPages", 7);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$minContentWords = (int) config("scanning.thresholds.trustPages.minContentWords", 200);
		$findings = array();
		$recommendations = array();

		$aboutPage = $scanContext->getTrustPageByType("about");
		$contactPage = $scanContext->getTrustPageByType("contact");

		$aboutQuality = $this->evaluateAboutPage($aboutPage, $minContentWords, $findings, $recommendations);
		$contactQuality = $this->evaluateContactPage($contactPage, $findings, $recommendations);

		if ($aboutQuality && $contactQuality) {
			$findings[] = array("type" => "ok", "message" => "Both About and Contact pages detected with quality content. This strongly supports E-E-A-T trust signals.");
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		if ($aboutQuality || $contactQuality) {
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array("type" => "bad", "message" => "Neither a quality About page nor a quality Contact page was detected. These are fundamental E-E-A-T trust signals.");
		return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Evaluate About page quality. Returns true if quality criteria met.
	 */
	private function evaluateAboutPage(?array $aboutPage, int $minContentWords, array &$findings, array &$recommendations): bool
	{
		if ($aboutPage === null || !($aboutPage["exists"] ?? false)) {
			$findings[] = array("type" => "warning", "message" => "No About page detected. An About page helps establish who is behind the website.");
			$recommendations[] = "Create an About page that describes your organization, mission, and the people behind it.";
			return false;
		}

		$wordCount = $aboutPage["wordCount"] ?? 0;
		$findings[] = array("type" => "info", "message" => "About page found: {$aboutPage["url"]} ({$wordCount} words).");

		if ($wordCount >= $minContentWords) {
			$findings[] = array("type" => "ok", "message" => "About page has substantial content ({$wordCount} words).");
			return true;
		}

		$findings[] = array("type" => "warning", "message" => "About page appears thin ({$wordCount} words). Aim for at least {$minContentWords} words describing your organization.");
		$recommendations[] = "Expand your About page with details about your team, mission, history, and expertise.";
		return false;
	}

	/**
	 * Evaluate Contact page quality. Returns true if quality criteria met.
	 */
	private function evaluateContactPage(?array $contactPage, array &$findings, array &$recommendations): bool
	{
		if ($contactPage === null || !($contactPage["exists"] ?? false)) {
			$findings[] = array("type" => "warning", "message" => "No Contact page detected. A contact page demonstrates accessibility and transparency.");
			$recommendations[] = "Create a Contact page with at least two forms of contact (email, phone, address, or contact form).";
			return false;
		}

		$findings[] = array("type" => "info", "message" => "Contact page found: {$contactPage["url"]}.");

		$contactSignals = 0;
		$signalDetails = array();

		if ($contactPage["hasAddress"] ?? false) {
			$contactSignals++;
			$signalDetails[] = "physical address";
		}

		if ($contactPage["hasPhone"] ?? false) {
			$contactSignals++;
			$signalDetails[] = "phone number";
		}

		if ($contactPage["hasEmail"] ?? false) {
			$contactSignals++;
			$signalDetails[] = "email address";
		}

		if ($contactPage["hasForm"] ?? false) {
			$contactSignals++;
			$signalDetails[] = "contact form";
		}

		if ($contactSignals >= 2) {
			$findings[] = array("type" => "ok", "message" => "Contact page includes: " . implode(", ", $signalDetails) . ".");
			return true;
		}

		if ($contactSignals === 1) {
			$findings[] = array("type" => "warning", "message" => "Contact page only includes: " . implode(", ", $signalDetails) . ". Consider adding more contact methods.");
		} else {
			$findings[] = array("type" => "warning", "message" => "Contact page exists but no recognizable contact information (address, phone, email, form) was detected.");
		}

		$recommendations[] = "Include at least two contact methods on your Contact page (physical address, phone, email, or a contact form).";
		return false;
	}
}
