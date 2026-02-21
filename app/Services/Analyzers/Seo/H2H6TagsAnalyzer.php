<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class H2H6TagsAnalyzer implements AnalyzerInterface
{
	private const MAX_REASONABLE_HEADINGS = 30;
	private const FLAT_HIERARCHY_THRESHOLD = 10;
	private const DUPLICATE_RATIO_THRESHOLD = 0.3;
	private const HEADING_PER_100_WORDS_LIMIT = 3;
	private const MIN_WORDS_FOR_RATIO_CHECK = 100;
	private const MIN_HEADING_LENGTH = 3;
	private const MAX_HEADING_LENGTH = 100;

	public function moduleKey(): string
	{
		return "h2h6Tags";
	}

	public function label(): string
	{
		return "H2-H6 Headings";
	}

	public function category(): string
	{
		return "On-Page SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.h2h6Tags", 4);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$headings = $scanContext->allHeadingsData;
		$subHeadings = array_filter($headings, fn($h) => $h["tag"] !== "h1");
		$findings = array();
		$recommendations = array();
		$issues = array();

		$counts = array("h2" => 0, "h3" => 0, "h4" => 0, "h5" => 0, "h6" => 0);
		foreach ($subHeadings as $heading) {
			$tag = $heading["tag"];
			if (isset($counts[$tag])) {
				$counts[$tag]++;
			}
		}

		$totalSubHeadings = array_sum($counts);

		$findings[] = array(
			"type" => "info",
			"message" => "Subheading Structure: H2({$counts["h2"]}) H3({$counts["h3"]}) H4({$counts["h4"]}) H5({$counts["h5"]}) H6({$counts["h6"]})",
		);

		$visibleCount = count(array_filter($subHeadings, fn($h) => !($h["hidden"] ?? false)));
		$hiddenCount = $totalSubHeadings - $visibleCount;

		if ($hiddenCount > 0) {
			$findings[] = array(
				"type" => $hiddenCount > $visibleCount ? "warning" : "info",
				"message" => "{$visibleCount} visible and {$hiddenCount} hidden subheading(s) detected. Hidden headings (via aria-hidden or display:none) are still present in the HTML source and may be evaluated by search engines.",
			);
		}

		$findings[] = array(
			"type" => "data",
			"key" => "headingsList",
			"value" => array_map(fn($h) => array("tag" => $h["tag"], "text" => $h["text"], "hidden" => $h["hidden"] ?? false), $subHeadings),
		);

		if ($totalSubHeadings === 0) {
			$findings[] = array("type" => "warning", "message" => "No subheadings (H2-H6) found. Subheadings help organize content for users and search engines.");
			$recommendations[] = "Add H2 subheadings to break up content into logical sections.";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$bodyWordCount = $this->estimateBodyWordCount($scanContext);

		$issues = array_merge(
			$issues,
			$this->checkExcessiveHeadings($totalSubHeadings, $findings, $recommendations),
			$this->checkHeadingToContentRatio($totalSubHeadings, $bodyWordCount, $findings, $recommendations),
			$this->checkDuplicateHeadings($subHeadings, $findings, $recommendations),
			$this->checkSequentialJumps($headings, $findings, $recommendations),
			$this->checkEmptyHeadings($subHeadings, $findings, $recommendations),
			$this->checkImageOnlyHeadings($subHeadings, $findings, $recommendations),
			$this->checkHeadingLengths($subHeadings, $findings, $recommendations),
			$this->checkFlatHierarchy($counts, $totalSubHeadings, $findings, $recommendations),
		);

		if (empty($issues)) {
			$findings[] = array("type" => "ok", "message" => "Good heading structure with {$totalSubHeadings} subheading(s) and proper hierarchy.");

			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		$worstSeverity = in_array("bad", $issues, true) ? ModuleStatus::Bad : ModuleStatus::Warning;

		return new AnalysisResult(status: $worstSeverity, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Flag when the total subheading count exceeds a reasonable threshold.
	 */
	private function checkExcessiveHeadings(int $totalSubHeadings, array &$findings, array &$recommendations): array
	{
		if ($totalSubHeadings <= self::MAX_REASONABLE_HEADINGS) {
			return array();
		}

		$findings[] = array(
			"type" => "bad",
			"message" => "Excessive number of subheadings detected ({$totalSubHeadings}). Well-structured pages typically have fewer than " . self::MAX_REASONABLE_HEADINGS . ". Review your HTML to ensure heading tags are used for content structure only.",
		);
		$recommendations[] = "Reduce the number of heading tags. Reserve H2-H6 for content sections — not for styling, layout, or non-content elements.";

		return array("bad");
	}

	/**
	 * Flag when the heading-to-content ratio is disproportionately high.
	 * A page with very little content but many headings signals misuse.
	 */
	private function checkHeadingToContentRatio(int $totalSubHeadings, int $bodyWordCount, array &$findings, array &$recommendations): array
	{
		if ($bodyWordCount < self::MIN_WORDS_FOR_RATIO_CHECK) {
			return array();
		}

		$headingsPer100Words = ($totalSubHeadings / $bodyWordCount) * 100;

		if ($headingsPer100Words <= self::HEADING_PER_100_WORDS_LIMIT) {
			return array();
		}

		$ratio = round($headingsPer100Words, 1);
		$findings[] = array(
			"type" => "warning",
			"message" => "High heading-to-content ratio: {$ratio} subheadings per 100 words ({$totalSubHeadings} headings across ~{$bodyWordCount} words). This suggests headings may be used for non-content purposes.",
		);
		$recommendations[] = "Ensure the number of headings is proportional to the amount of content. Each heading should introduce a meaningful section of text.";

		return array("warning");
	}

	/**
	 * Detect duplicate heading text. Reports the count and the most repeated entries.
	 */
	private function checkDuplicateHeadings(array $subHeadings, array &$findings, array &$recommendations): array
	{
		$texts = array();
		foreach ($subHeadings as $heading) {
			$normalized = strtolower(trim($heading["text"]));
			if ($normalized !== "") {
				$texts[] = $normalized;
			}
		}

		if (count($texts) < 4) {
			return array();
		}

		$textCounts = array_count_values($texts);
		$duplicates = array_filter($textCounts, fn($count) => $count > 1);

		if (empty($duplicates)) {
			return array();
		}

		$duplicateCount = 0;
		foreach ($duplicates as $count) {
			$duplicateCount += $count;
		}

		$duplicateRatio = $duplicateCount / count($texts);

		if ($duplicateRatio <= self::DUPLICATE_RATIO_THRESHOLD) {
			return array();
		}

		arsort($duplicates);
		$topDuplicates = array_slice($duplicates, 0, 3, true);
		$examples = array();
		foreach ($topDuplicates as $text => $count) {
			$examples[] = "\"{$text}\" ({$count}x)";
		}

		$uniqueCount = count($textCounts);
		$findings[] = array(
			"type" => "warning",
			"message" => "Only {$uniqueCount} unique subheadings out of " . count($texts) . " total. Most repeated: " . implode(", ", $examples) . ".",
		);
		$recommendations[] = "Each heading should be unique and describe the content of its section. Remove or consolidate duplicate headings.";

		return array("warning");
	}

	/**
	 * Walk headings in document order and flag any jump greater than +1 level.
	 * This is more precise than a global "which levels exist" check because
	 * it catches order-based jumps (e.g., H2 -> H4 even if H3 exists elsewhere).
	 */
	private function checkSequentialJumps(array $headings, array &$findings, array &$recommendations): array
	{
		$jumps = array();
		$previousLevel = 0;

		foreach ($headings as $heading) {
			$currentLevel = (int) substr($heading["tag"], 1);

			if ($previousLevel > 0 && $currentLevel > $previousLevel + 1) {
				$jumpDescription = "H{$previousLevel} -> H{$currentLevel}";
				if (!in_array($jumpDescription, $jumps, true)) {
					$jumps[] = $jumpDescription;
				}
			}

			$previousLevel = $currentLevel;
		}

		if (empty($jumps)) {
			return array();
		}

		$findings[] = array(
			"type" => "warning",
			"message" => "Heading level jumps detected: " . implode(", ", $jumps) . ". Each heading should be at most one level deeper than the previous (e.g., H2 then H3, not H2 then H4).",
		);
		$recommendations[] = "Fix heading hierarchy so no level is skipped. Proper nesting helps crawlers understand sub-topic relationships.";

		return array("warning");
	}

	/**
	 * Detect empty headings with no visible text content.
	 */
	private function checkEmptyHeadings(array $subHeadings, array &$findings, array &$recommendations): array
	{
		$emptyHeadings = array_filter($subHeadings, fn($h) => empty(trim($h["text"])));

		if (empty($emptyHeadings)) {
			return array();
		}

		$findings[] = array("type" => "warning", "message" => count($emptyHeadings) . " empty subheading(s) found. All headings should contain descriptive text.");
		$recommendations[] = "Add meaningful text to empty headings or remove them.";

		return array("warning");
	}

	/**
	 * Detect headings that contain only an image with no text.
	 * If the image has alt text it is a minor issue; no alt text is worse.
	 */
	private function checkImageOnlyHeadings(array $subHeadings, array &$findings, array &$recommendations): array
	{
		$noAltCount = 0;
		$withAltCount = 0;

		foreach ($subHeadings as $heading) {
			if (!($heading["imageOnly"] ?? false)) {
				continue;
			}

			$alt = $heading["imageAlt"] ?? null;

			if ($alt === null || $alt === "") {
				$noAltCount++;
			} else {
				$withAltCount++;
			}
		}

		if ($noAltCount === 0 && $withAltCount === 0) {
			return array();
		}

		$issues = array();

		if ($noAltCount > 0) {
			$findings[] = array(
				"type" => "bad",
				"message" => "{$noAltCount} image-only subheading(s) with no alt text. These headings are invisible to search engines and screen readers.",
			);
			$recommendations[] = "Add descriptive alt text to images inside heading tags, or replace image-only headings with text-based headings.";
			$issues[] = "bad";
		}

		if ($withAltCount > 0) {
			$findings[] = array(
				"type" => "warning",
				"message" => "{$withAltCount} image-only subheading(s) rely on alt text instead of visible text. Text-based headings are preferred for clarity.",
			);
			if ($noAltCount === 0) {
				$recommendations[] = "Replace image-only headings with text-based headings for better SEO and accessibility.";
			}
			$issues[] = "warning";
		}

		return $issues;
	}

	/**
	 * Flag headings that are too short (likely UI elements) or too long (likely paragraphs).
	 */
	private function checkHeadingLengths(array $subHeadings, array &$findings, array &$recommendations): array
	{
		$tooShortCount = 0;
		$tooLongCount = 0;

		foreach ($subHeadings as $heading) {
			$text = trim($heading["text"]);

			if ($text === "") {
				continue;
			}

			$length = mb_strlen($text);

			if ($length < self::MIN_HEADING_LENGTH) {
				$tooShortCount++;
			} elseif ($length > self::MAX_HEADING_LENGTH) {
				$tooLongCount++;
			}
		}

		$issues = array();

		if ($tooShortCount > 0) {
			$findings[] = array(
				"type" => "info",
				"message" => "{$tooShortCount} subheading(s) are under " . self::MIN_HEADING_LENGTH . " characters. Very short headings may be UI elements rather than content headings.",
			);
		}

		if ($tooLongCount > 0) {
			$findings[] = array(
				"type" => "warning",
				"message" => "{$tooLongCount} subheading(s) exceed " . self::MAX_HEADING_LENGTH . " characters. Excessively long headings may indicate paragraphs incorrectly styled as headings.",
			);
			$recommendations[] = "Shorten headings over " . self::MAX_HEADING_LENGTH . " characters. Headings should be concise topic labels, not full sentences or paragraphs.";
			$issues[] = "warning";
		}

		return $issues;
	}

	/**
	 * Flag when all headings sit at a single level with no sub-levels,
	 * indicating a flat document structure with no content depth.
	 */
	private function checkFlatHierarchy(array $counts, int $totalSubHeadings, array &$findings, array &$recommendations): array
	{
		if ($totalSubHeadings < self::FLAT_HIERARCHY_THRESHOLD) {
			return array();
		}

		$usedLevelCount = count(array_filter($counts, fn($count) => $count > 0));

		if ($usedLevelCount > 1) {
			return array();
		}

		$dominantTag = strtoupper(array_key_first(array_filter($counts, fn($count) => $count > 0)));
		$findings[] = array(
			"type" => "warning",
			"message" => "Flat document outline: all {$totalSubHeadings} subheadings are {$dominantTag} with no deeper levels. A well-structured page uses multiple heading levels to convey content depth.",
		);
		$recommendations[] = "Organize content into sections and subsections using multiple heading levels (e.g., H3 under H2) to create a clear document outline.";

		return array("warning");
	}

	/**
	 * Estimate the word count of the page's body text for ratio calculations.
	 */
	private function estimateBodyWordCount(ScanContext $scanContext): int
	{
		$bodyNodes = $scanContext->xpath->query("//body");
		if ($bodyNodes === false || $bodyNodes->length === 0) {
			return 0;
		}

		$bodyText = $bodyNodes->item(0)->textContent;

		return str_word_count($bodyText);
	}
}
