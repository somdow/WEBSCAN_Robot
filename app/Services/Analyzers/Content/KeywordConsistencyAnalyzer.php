<?php

namespace App\Services\Analyzers\Content;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Analyzers\Concerns\DetectsKeywordPresence;
use App\Services\Analyzers\Concerns\ExtractsPageContent;

/**
 * Builds a visual keyword consistency matrix showing presence of target keywords
 * across 7 page locations: Title, Meta Description, H1, H2+, Body, URL, Image Alt.
 * Info-only module — keyword scoring lives in ContentKeywordsAnalyzer.
 */
class KeywordConsistencyAnalyzer implements AnalyzerInterface
{
	use ExtractsPageContent;
	use DetectsKeywordPresence;

	/** Maximum keywords to include in the consistency matrix. */
	private const MAX_MATRIX_KEYWORDS = 5;

	public function moduleKey(): string
	{
		return "keywordConsistency";
	}

	public function label(): string
	{
		return "Keyword Consistency Matrix";
	}

	public function category(): string
	{
		return "Content Analysis";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.keywordConsistency", 0);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$keyword = $scanContext->targetKeywords[0] ?? null;

		if ($keyword === null || trim($keyword) === "") {
			$keyword = $this->autoDetectKeyword($scanContext);
		}

		if ($keyword === null || trim($keyword) === "") {
			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: array(
					array("type" => "info", "message" => "No target keyword available for consistency analysis."),
				),
				recommendations: array(
					"Set target keywords in your project settings to see the consistency matrix.",
				),
			);
		}

		$keyword = strtolower(trim($keyword));
		$findings = array();
		$recommendations = array();

		/** Pre-compute text extractions for all location checks */
		$h1Text = $this->extractH1Text($scanContext);
		$bodyText = $this->extractVisibleBodyText($scanContext->htmlContent);
		$urlPath = strtolower(parse_url($scanContext->effectiveUrl, PHP_URL_PATH) ?? "");
		$h2PlusText = $this->extractH2PlusText($scanContext);
		$imageAltText = $this->extractImageAltText($scanContext);

		/** Build the matrix for all target keywords */
		$matrixRows = $this->buildKeywordMatrix($scanContext, $keyword, $h1Text, $bodyText, $urlPath, $h2PlusText, $imageAltText);

		/** Summarize coverage for each keyword and track overall health */
		$totalPresent = 0;
		$totalPossible = 0;

		foreach ($matrixRows as $row) {
			$presentCount = count(array_filter($row["locations"]));
			$totalLocations = count($row["locations"]);
			$totalPresent += $presentCount;
			$totalPossible += $totalLocations;
			$statusType = $presentCount >= 5 ? "ok" : ($presentCount >= 3 ? "info" : "warning");
			$findings[] = array("type" => $statusType, "message" => "\"{$row['keyword']}\" found in {$presentCount} of {$totalLocations} locations.");
		}

		/** Store full matrix as structured data for the visual grid */
		$findings[] = array("type" => "data", "key" => "keywordMatrix", "value" => $matrixRows);

		/** Generate recommendations when coverage is weak */
		$coverageRatio = $totalPossible > 0 ? $totalPresent / $totalPossible : 0;

		if ($coverageRatio < 0.3) {
			$recommendations[] = "Your keywords have very low coverage across key page locations. Include target keywords naturally in your title, meta description, H1 heading, and body content.";
		} elseif ($coverageRatio < 0.5) {
			$recommendations[] = "Several keyword placements are missing. Focus on adding keywords to the title tag, H1 heading, and opening body text for the strongest SEO signals.";
		}

		/** Flag keywords with zero coverage */
		foreach ($matrixRows as $row) {
			$presentCount = count(array_filter($row["locations"]));
			if ($presentCount === 0) {
				$recommendations[] = "\"{$row['keyword']}\" was not found anywhere on this page. Consider whether this keyword is relevant to the page content, or add it to key locations.";
			}
		}

		return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Build the keyword consistency matrix for all target keywords.
	 * Each row maps a keyword to presence across 7 page locations.
	 *
	 * @return array<int, array{keyword: string, locations: array<string, bool>}>
	 */
	private function buildKeywordMatrix(
		ScanContext $scanContext,
		string $primaryKeyword,
		?string $h1Text,
		?string $bodyText,
		string $urlPath,
		?string $h2PlusText,
		?string $imageAltText,
	): array {
		$keywords = array();

		/** Include user-defined keywords (up to limit) */
		foreach ($scanContext->targetKeywords as $targetKeyword) {
			$normalized = strtolower(trim($targetKeyword));
			if ($normalized !== "" && !in_array($normalized, $keywords, true)) {
				$keywords[] = $normalized;
			}
			if (count($keywords) >= self::MAX_MATRIX_KEYWORDS) {
				break;
			}
		}

		/** If no user keywords, include the primary (possibly auto-detected) keyword */
		if (empty($keywords)) {
			$keywords[] = $primaryKeyword;
		}

		$matrixRows = array();

		foreach ($keywords as $kw) {
			$matrixRows[] = array(
				"keyword" => $kw,
				"locations" => array(
					"title" => $this->textContainsKeyword($scanContext->titleContent, $kw),
					"metaDescription" => $this->textContainsKeyword($scanContext->metaDescriptionContent, $kw),
					"h1" => $this->textContainsKeyword($h1Text, $kw),
					"h2Plus" => $this->textContainsKeyword($h2PlusText, $kw),
					"body" => $this->textContainsKeyword($bodyText, $kw),
					"url" => $this->urlContainsKeyword($urlPath, $kw),
					"imageAlt" => $this->textContainsKeyword($imageAltText, $kw),
				),
			);
		}

		return $matrixRows;
	}
}
