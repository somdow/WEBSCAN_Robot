<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class H1TagAnalyzer implements AnalyzerInterface
{
	private const MIN_LENGTH = 3;
	private const MAX_LENGTH = 100;

	public function moduleKey(): string
	{
		return "h1Tag";
	}

	public function label(): string
	{
		return "H1 Heading";
	}

	public function category(): string
	{
		return "On-Page SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.h1Tag", 9);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$allH1Headings = array_filter($scanContext->allHeadingsData, fn($heading) => $heading["tag"] === "h1");
		$findings = array();
		$recommendations = array();
		$issues = array();

		$visibleH1s = array_filter($allH1Headings, fn($h) => !($h["hidden"] ?? false));
		$hiddenH1s = array_filter($allH1Headings, fn($h) => ($h["hidden"] ?? false));
		$visibleH1Count = count($visibleH1s);
		$hiddenH1Count = count($hiddenH1s);

		if ($hiddenH1Count > 0) {
			$findings[] = array(
				"type" => "info",
				"message" => "{$hiddenH1Count} hidden H1 tag(s) detected (via aria-hidden or display:none). Hidden H1s are not counted as valid page headings.",
			);
		}

		if ($visibleH1Count === 0) {
			$findings[] = array("type" => "bad", "message" => "Missing H1 Heading: No visible <h1> tag found on this page. The H1 is a critical on-page SEO signal.");
			$recommendations[] = "Add a single, descriptive <h1> heading that includes your primary keyword.";
			$recommendations[] = "The H1 should accurately reflect the main topic of the page.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		$firstH1 = array_values($visibleH1s)[0];
		$h1Text = $firstH1["text"];
		$isImageOnly = $firstH1["imageOnly"] ?? false;
		$imageAlt = $firstH1["imageAlt"] ?? null;

		/* Always emit a data finding with all visible H1 texts for UI display */
		$h1List = array();
		foreach (array_values($visibleH1s) as $h1Index => $h1Item) {
			$h1List[] = array(
				"index" => $h1Index + 1,
				"text" => trim($h1Item["text"]) !== "" ? $h1Item["text"] : ($h1Item["imageAlt"] ?? "(empty)"),
				"imageOnly" => $h1Item["imageOnly"] ?? false,
			);
		}
		$findings[] = array("type" => "data", "key" => "h1Tags", "value" => $h1List);

		if ($visibleH1Count > 1) {
			$findings[] = array("type" => "warning", "message" => "Multiple H1 Tags: Found {$visibleH1Count} visible H1 headings. Best practice is to use only one H1 per page to maintain clear topic focus.");
			$recommendations[] = "Reduce to a single H1 tag. Convert additional H1 tags to H2 or lower heading levels.";
			$issues[] = "warning";
		}

		if ($isImageOnly) {
			$issues = array_merge($issues, $this->checkImageOnlyH1($imageAlt, $findings, $recommendations));
		} elseif (empty(trim($h1Text))) {
			$findings[] = array("type" => "bad", "message" => "Empty H1 Tag: The H1 heading exists but contains no visible text.");
			$recommendations[] = "Add meaningful, keyword-rich text to your H1 heading.";
			$issues[] = "bad";
		} else {
			$findings[] = array("type" => "info", "message" => "H1 Content: \"{$h1Text}\"");
			$issues = array_merge($issues, $this->checkH1Length($h1Text, $findings, $recommendations));
		}

		if (empty($issues)) {
			$findings[] = array("type" => "ok", "message" => "Single H1 heading found with descriptive content.");
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		$worstSeverity = in_array("bad", $issues, true) ? ModuleStatus::Bad : ModuleStatus::Warning;

		return new AnalysisResult(status: $worstSeverity, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Check an image-only H1 for alt text quality.
	 */
	private function checkImageOnlyH1(?string $imageAlt, array &$findings, array &$recommendations): array
	{
		if ($imageAlt === null || $imageAlt === "") {
			$findings[] = array(
				"type" => "bad",
				"message" => "Image-only H1 with no alt text. The H1 contains an image but no descriptive text — search engines cannot determine the page topic.",
			);
			$recommendations[] = "Either add alt text to the image inside the H1, or replace the image-only H1 with a text-based heading.";

			return array("bad");
		}

		$findings[] = array(
			"type" => "warning",
			"message" => "Image-only H1 detected. The H1 text is derived from the image alt attribute: \"{$imageAlt}\". Text-based H1s are preferred for SEO clarity.",
		);
		$recommendations[] = "Replace the image-only H1 with a visible text heading. Image alt text is a fallback, not a substitute for proper heading content.";

		return array("warning");
	}

	/**
	 * Check H1 length for being too short or too long.
	 */
	private function checkH1Length(string $h1Text, array &$findings, array &$recommendations): array
	{
		$length = mb_strlen($h1Text);

		if ($length < self::MIN_LENGTH) {
			$findings[] = array(
				"type" => "warning",
				"message" => "H1 is very short ({$length} characters). Short headings like single words provide little context for search engines.",
			);
			$recommendations[] = "Expand the H1 to be more descriptive. Include your primary keyword in a clear, natural phrase.";

			return array("warning");
		}

		if ($length > self::MAX_LENGTH) {
			$findings[] = array(
				"type" => "warning",
				"message" => "H1 is excessively long ({$length} characters). This may indicate a paragraph styled as a heading rather than a concise topic summary.",
			);
			$recommendations[] = "Shorten the H1 to under " . self::MAX_LENGTH . " characters. A concise, descriptive heading performs better than a lengthy one.";

			return array("warning");
		}

		return array();
	}
}
