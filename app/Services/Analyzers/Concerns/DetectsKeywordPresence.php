<?php

namespace App\Services\Analyzers\Concerns;

use App\DataTransferObjects\ScanContext;
use DOMElement;

/**
 * Shared keyword detection methods used by ContentKeywordsAnalyzer
 * and KeywordConsistencyAnalyzer. Extracts text from page elements
 * and checks for keyword presence across multiple locations.
 */
trait DetectsKeywordPresence
{
	/**
	 * Check if text contains the keyword (case-insensitive).
	 */
	protected function textContainsKeyword(?string $text, string $keyword): bool
	{
		if ($text === null || $text === "") {
			return false;
		}

		return str_contains(strtolower($text), $keyword);
	}

	/**
	 * Check if a URL path contains the keyword in slug, compact, or underscore form.
	 */
	protected function urlContainsKeyword(string $urlPath, string $keyword): bool
	{
		$keywordSlug = str_replace(" ", "-", $keyword);
		$keywordCompact = str_replace(" ", "", $keyword);
		$keywordUnderscore = str_replace(" ", "_", $keyword);

		return str_contains($urlPath, $keywordSlug)
			|| str_contains($urlPath, $keywordCompact)
			|| str_contains($urlPath, $keywordUnderscore);
	}

	/**
	 * Extract combined text from all H2-H6 headings on the page.
	 */
	protected function extractH2PlusText(ScanContext $scanContext): ?string
	{
		$headingTexts = array();

		foreach ($scanContext->allHeadingsData as $heading) {
			$level = $heading["level"] ?? 0;
			$text = $heading["text"] ?? "";
			if ($level >= 2 && $level <= 6 && trim($text) !== "") {
				$headingTexts[] = trim($text);
			}
		}

		return !empty($headingTexts) ? implode(" ", $headingTexts) : null;
	}

	/**
	 * Extract combined alt text from all images on the page.
	 */
	protected function extractImageAltText(ScanContext $scanContext): ?string
	{
		$imageNodes = $scanContext->xpath->query("//img[@alt]");
		if ($imageNodes === false || $imageNodes->length === 0) {
			return null;
		}

		$altTexts = array();

		for ($index = 0; $index < $imageNodes->length; $index++) {
			$node = $imageNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}
			$alt = trim($node->getAttribute("alt"));
			if ($alt !== "") {
				$altTexts[] = $alt;
			}
		}

		return !empty($altTexts) ? implode(" ", $altTexts) : null;
	}

	/**
	 * Auto-detect the most prominent keyword from the page content.
	 * Priority: H1 text > title tag > prominent body phrase.
	 */
	protected function autoDetectKeyword(ScanContext $scanContext): ?string
	{
		$h1Text = $this->extractH1Text($scanContext);
		if ($h1Text !== null && trim($h1Text) !== "") {
			return $this->extractKeyPhrase($h1Text);
		}

		if ($scanContext->titleContent !== null && trim($scanContext->titleContent) !== "") {
			return $this->extractKeyPhrase($scanContext->titleContent);
		}

		return $this->extractProminentPhrase($scanContext);
	}

	/**
	 * Extract a meaningful 2-3 word key phrase from text.
	 * Strips common filler/stop words and brand separators.
	 */
	protected function extractKeyPhrase(string $text): ?string
	{
		$text = preg_replace("/\\s*[|\\-\x{2013}\x{2014}:]\\s*/u", " ", $text);
		$text = strtolower(trim($text));

		$stopWords = array(
			"a", "an", "the", "and", "or", "but", "in", "on", "at", "to", "for",
			"of", "with", "by", "is", "are", "was", "were", "be", "been", "being",
			"have", "has", "had", "do", "does", "did", "will", "would", "could",
			"should", "may", "might", "can", "this", "that", "these", "those",
			"i", "we", "you", "he", "she", "it", "they", "my", "our", "your",
			"his", "her", "its", "their", "home", "welcome", "page", "official",
		);

		$words = preg_split("/\\s+/", $text, -1, PREG_SPLIT_NO_EMPTY);
		$filteredWords = array_values(array_filter(
			$words,
			fn(string $word) => !in_array($word, $stopWords, true) && strlen($word) > 1,
		));

		if (count($filteredWords) === 0) {
			return null;
		}

		$phraseWords = array_slice($filteredWords, 0, 3);

		return implode(" ", $phraseWords);
	}

	/**
	 * Find the most frequent 2-word phrase in body text as a fallback keyword.
	 */
	protected function extractProminentPhrase(ScanContext $scanContext): ?string
	{
		$bodyText = strip_tags($scanContext->htmlContent);
		$bodyText = html_entity_decode($bodyText, ENT_QUOTES | ENT_HTML5, "UTF-8");
		$bodyText = preg_replace("/\\s+/", " ", $bodyText);
		$bodyText = strtolower(trim(mb_substr($bodyText, 0, 2000)));

		$words = preg_split("/\\s+/", $bodyText, -1, PREG_SPLIT_NO_EMPTY);
		$words = array_values(array_filter(
			$words,
			fn(string $word) => strlen($word) > 2 && preg_match("/^[a-z]+$/", $word),
		));

		if (count($words) < 4) {
			return null;
		}

		$bigramCounts = array();
		for ($index = 0; $index < count($words) - 1; $index++) {
			$bigram = $words[$index] . " " . $words[$index + 1];
			$bigramCounts[$bigram] = ($bigramCounts[$bigram] ?? 0) + 1;
		}

		arsort($bigramCounts);
		$topBigram = array_key_first($bigramCounts);

		if ($topBigram !== null && $bigramCounts[$topBigram] >= 2) {
			return $topBigram;
		}

		return null;
	}
}
