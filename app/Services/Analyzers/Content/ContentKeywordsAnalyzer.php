<?php

namespace App\Services\Analyzers\Content;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Analyzers\Concerns\DetectsKeywordPresence;
use App\Services\Analyzers\Concerns\ExtractsPageContent;

/**
 * Checks target keyword presence across 5 scored page elements:
 * title tag, H1, body (first paragraph), URL, meta description.
 * Uses user-defined keywords or auto-detects from page content.
 */
class ContentKeywordsAnalyzer implements AnalyzerInterface
{
	use ExtractsPageContent;
	use DetectsKeywordPresence;

	public function moduleKey(): string
	{
		return "contentKeywords";
	}

	public function label(): string
	{
		return "Target Keyword Presence";
	}

	public function category(): string
	{
		return "Content Analysis";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.contentKeywords", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$keyword = $scanContext->targetKeywords[0] ?? null;
		$keywordSource = "user-defined";

		if ($keyword === null || trim($keyword) === "") {
			$keyword = $this->autoDetectKeyword($scanContext);
			$keywordSource = "auto-detected";
		}

		if ($keyword === null || trim($keyword) === "") {
			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: array(
					array("type" => "info", "message" => "No target keyword set and none could be auto-detected from the page content."),
				),
				recommendations: array(
					"Set a target keyword in your project settings for keyword presence analysis.",
				),
			);
		}

		$keyword = strtolower(trim($keyword));
		$findings = array();
		$recommendations = array();
		$presenceCount = 0;

		/** Pre-compute reusable text extractions */
		$h1Text = $this->extractH1Text($scanContext);
		$bodyText = $this->extractVisibleBodyText($scanContext->htmlContent);
		$urlPath = strtolower(parse_url($scanContext->effectiveUrl, PHP_URL_PATH) ?? "");

		if ($keywordSource === "user-defined") {
			$findings[] = array("type" => "info", "message" => "Analyzing target keyword: \"{$keyword}\".");
		} else {
			$findings[] = array("type" => "info", "message" => "Auto-detected keyword: \"{$keyword}\". Set a target keyword in project settings for more accurate results.");
		}

		/** Scored check 1: Title tag */
		$inTitle = $this->textContainsKeyword($scanContext->titleContent, $keyword);
		if ($inTitle) {
			$presenceCount++;
			$findings[] = array("type" => "ok", "message" => "Keyword found in title tag.");
		} else {
			$findings[] = array("type" => "warning", "message" => "Keyword not found in title tag.");
			$recommendations[] = "Include your target keyword naturally in the page title.";
		}

		/** Scored check 2: H1 heading */
		$inH1 = $this->textContainsKeyword($h1Text, $keyword);
		if ($inH1) {
			$presenceCount++;
			$findings[] = array("type" => "ok", "message" => "Keyword found in H1 heading.");
		} else {
			$findings[] = array("type" => "warning", "message" => "Keyword not found in H1 heading.");
			$recommendations[] = "Include your target keyword in the main H1 heading.";
		}

		/** Scored check 3: Body content (first paragraph) */
		$inBody = $this->textContainsKeyword($bodyText, $keyword);
		if ($inBody) {
			$presenceCount++;
			$findings[] = array("type" => "ok", "message" => "Keyword found in body content.");
		} else {
			$findings[] = array("type" => "warning", "message" => "Keyword not found in visible body text.");
			$recommendations[] = "Include your target keyword naturally in the page body content to signal topic relevance.";
		}

		/** Scored check 4: URL path */
		$inUrl = $this->urlContainsKeyword($urlPath, $keyword);
		if ($inUrl) {
			$presenceCount++;
			$findings[] = array("type" => "ok", "message" => "Keyword found in URL path.");
		} else {
			$findings[] = array("type" => "info", "message" => "Keyword not found in URL path (less critical for existing pages).");
		}

		/** Scored check 5: Meta description */
		$inMeta = $this->textContainsKeyword($scanContext->metaDescriptionContent, $keyword);
		if ($inMeta) {
			$presenceCount++;
			$findings[] = array("type" => "ok", "message" => "Keyword found in meta description.");
		} else {
			$findings[] = array("type" => "warning", "message" => "Keyword not found in meta description.");
			$recommendations[] = "Include your target keyword in the meta description to improve click-through rates.";
		}

		$findings[] = array("type" => "info", "message" => "Keyword present in {$presenceCount} of 5 scored locations.");

		$goodThreshold = (int) config("scanning.thresholds.contentKeywords.goodPresenceCount", 4);
		$warnThreshold = (int) config("scanning.thresholds.contentKeywords.warnPresenceCount", 2);

		if ($presenceCount >= $goodThreshold) {
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		if ($presenceCount >= $warnThreshold) {
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
	}
}
