<?php

namespace App\Services\Analyzers\Content;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Analyzers\Concerns\ExtractsPageContent;

/**
 * Detects on-page duplicate content signals:
 * - Title tag vs H1 heading overlap
 * - Meta description copied from the first paragraph of body text
 */
class ContentDuplicateAnalyzer implements AnalyzerInterface
{
	use ExtractsPageContent;
	public function moduleKey(): string
	{
		return "contentDuplicate";
	}

	public function label(): string
	{
		return "Duplicate Content Signals";
	}

	public function category(): string
	{
		return "Content Analysis";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.contentDuplicate", 4);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();
		$issueCount = 0;

		$titleH1Issue = $this->checkTitleH1Overlap($scanContext, $findings, $recommendations);
		if ($titleH1Issue) {
			$issueCount++;
		}

		$metaBodyIssue = $this->checkMetaDescriptionBodyOverlap($scanContext, $findings, $recommendations);
		if ($metaBodyIssue) {
			$issueCount++;
		}

		if ($issueCount === 0) {
			$findings[] = array("type" => "ok", "message" => "Title, H1, and meta description are distinct. This signals unique, intentional content.");
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		if ($issueCount >= 2) {
			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Check if the title tag and H1 heading are overly similar.
	 * Returns true if a duplication issue was found.
	 */
	private function checkTitleH1Overlap(ScanContext $scanContext, array &$findings, array &$recommendations): bool
	{
		$title = $scanContext->titleContent;
		$h1Text = $this->extractH1Text($scanContext);

		if ($title === null || $title === "" || $h1Text === null || $h1Text === "") {
			return false;
		}

		$normalizedTitle = $this->normalizeText($title);
		$normalizedH1 = $this->normalizeText($h1Text);

		if ($normalizedTitle === $normalizedH1) {
			$findings[] = array("type" => "warning", "message" => "Title tag and H1 heading are identical: \"{$title}\". While not harmful, differentiation provides more keyword opportunities.");
			$recommendations[] = "Consider making the title and H1 slightly different. Use the title for search intent + branding, and the H1 for a user-facing headline.";
			return true;
		}

		$similarity = $this->calculateSimilarity($normalizedTitle, $normalizedH1);
		if ($similarity > 80.0) {
			$findings[] = array("type" => "info", "message" => "Title tag and H1 heading are very similar (" . round($similarity) . "% overlap). Consider differentiating them for broader keyword coverage.");
		}

		return false;
	}

	/**
	 * Check if the meta description overlaps heavily with the first paragraph.
	 * Returns true if a duplication issue was found.
	 */
	private function checkMetaDescriptionBodyOverlap(ScanContext $scanContext, array &$findings, array &$recommendations): bool
	{
		$metaDescription = $scanContext->metaDescriptionContent;
		if ($metaDescription === null || $metaDescription === "") {
			return false;
		}

		$firstParagraph = $this->extractFirstParagraph($scanContext);
		if ($firstParagraph === null || $firstParagraph === "") {
			return false;
		}

		$normalizedMeta = $this->normalizeText($metaDescription);
		$normalizedFirstPara = $this->normalizeText($firstParagraph);

		if (str_starts_with($normalizedFirstPara, $normalizedMeta)) {
			$findings[] = array("type" => "warning", "message" => "Meta description appears to be copied from the beginning of the page content.");
			$recommendations[] = "Write a unique meta description that summarizes the page value proposition rather than copying the first paragraph.";
			return true;
		}

		$compareLength = mb_strlen($normalizedMeta) + 50;
		$similarity = $this->calculateSimilarity($normalizedMeta, mb_substr($normalizedFirstPara, 0, $compareLength));
		if ($similarity > 80.0) {
			$findings[] = array("type" => "warning", "message" => "Meta description closely matches the first paragraph (" . round($similarity) . "% similarity). This is a missed opportunity for unique messaging.");
			$recommendations[] = "Craft a distinct meta description that complements rather than duplicates your opening paragraph.";
			return true;
		}

		return false;
	}

	/**
	 * Normalize text for comparison: lowercase, collapse whitespace, strip punctuation.
	 */
	private function normalizeText(string $text): string
	{
		$text = strtolower(trim($text));
		$text = preg_replace("/\\s+/", " ", $text);
		$text = preg_replace("/[^a-z0-9\\s]/", "", $text);

		return trim($text);
	}

	/**
	 * Calculate percentage similarity between two strings using similar_text.
	 */
	private function calculateSimilarity(string $textA, string $textB): float
	{
		if ($textA === "" || $textB === "") {
			return 0.0;
		}

		similar_text($textA, $textB, $percentage);

		return $percentage;
	}
}
