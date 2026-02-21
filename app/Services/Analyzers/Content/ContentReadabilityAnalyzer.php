<?php

namespace App\Services\Analyzers\Content;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Analyzers\Concerns\ExtractsPageContent;

/**
 * Analyzes content readability using word count, sentence metrics,
 * and the Flesch-Kincaid Reading Ease score. Flags thin content
 * and poor readability for general web audiences.
 */
class ContentReadabilityAnalyzer implements AnalyzerInterface
{
	use ExtractsPageContent;
	public function moduleKey(): string
	{
		return "contentReadability";
	}

	public function label(): string
	{
		return "Content Readability";
	}

	public function category(): string
	{
		return "Content Analysis";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.contentReadability", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$bodyText = $this->extractVisibleBodyText($scanContext->htmlContent);
		$wordCount = str_word_count($bodyText);

		$minWords = (int) config("scanning.thresholds.contentReadability.minWords", 300);
		$thinContentWords = (int) config("scanning.thresholds.contentReadability.thinContentWords", 100);
		$idealFleschMin = (int) config("scanning.thresholds.contentReadability.idealFleschKincaidMin", 50);
		$poorFleschMax = (int) config("scanning.thresholds.contentReadability.poorFleschKincaidMax", 30);
		$maxAvgSentenceLength = (int) config("scanning.thresholds.contentReadability.maxAvgSentenceLength", 25);

		$findings = array();
		$recommendations = array();
		$hasWordCountIssue = false;
		$hasReadabilityIssue = false;

		$findings[] = array("type" => "info", "message" => "Page contains {$wordCount} words of visible body text.");

		$bodyTextExcerpt = $this->truncateToWordLimit($bodyText, 500);
		$findings[] = array(
			"type" => "data",
			"key" => "bodyTextExcerpt",
			"value" => $bodyTextExcerpt,
		);

		if ($wordCount < $thinContentWords) {
			$findings[] = array("type" => "bad", "message" => "Very thin content detected ({$wordCount} words). Search engines typically expect substantial content for ranking.");
			$recommendations[] = "Significantly expand the page content to at least {$minWords} words with valuable, relevant information.";
			$hasWordCountIssue = true;
		} elseif ($wordCount < $minWords) {
			$findings[] = array("type" => "warning", "message" => "Content may be thin ({$wordCount} words). Aim for at least {$minWords} words for comprehensive coverage.");
			$recommendations[] = "Consider expanding the content with additional detail, examples, or supporting information.";
			$hasWordCountIssue = true;
		} else {
			$findings[] = array("type" => "ok", "message" => "Content length is adequate ({$wordCount} words).");
		}

		if ($wordCount < 20) {
			$findings[] = array("type" => "info", "message" => "Insufficient text for readability analysis.");
			$status = $wordCount < $thinContentWords ? ModuleStatus::Bad : ModuleStatus::Warning;
			return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
		}

		$sentenceCount = $this->countSentences($bodyText);
		$avgSentenceLength = $sentenceCount > 0 ? round($wordCount / $sentenceCount, 1) : 0;
		$fleschScore = $this->calculateFleschKincaid($bodyText, $wordCount, $sentenceCount);
		$readabilityLabel = $this->fleschScoreLabel($fleschScore);

		$findings[] = array(
			"type" => "info",
			"message" => "Readability: Flesch-Kincaid score {$fleschScore}/100 ({$readabilityLabel}). Average sentence length: {$avgSentenceLength} words across {$sentenceCount} sentences.",
		);

		if ($fleschScore < $poorFleschMax) {
			$findings[] = array("type" => "bad", "message" => "Content readability is very poor (FK score: {$fleschScore}). Most readers will find this difficult to understand.");
			$recommendations[] = "Simplify sentence structure and use more common vocabulary. Aim for a Flesch-Kincaid score of {$idealFleschMin}+ for general audiences.";
			$hasReadabilityIssue = true;
		} elseif ($fleschScore < $idealFleschMin) {
			$findings[] = array("type" => "warning", "message" => "Content readability could be improved (FK score: {$fleschScore}). Consider simplifying for a broader audience.");
			$recommendations[] = "Break up long sentences and use simpler words where possible. A Flesch-Kincaid score of {$idealFleschMin}+ is ideal for web content.";
			$hasReadabilityIssue = true;
		} else {
			$findings[] = array("type" => "ok", "message" => "Content readability is good for a general web audience.");
		}

		if ($avgSentenceLength > $maxAvgSentenceLength) {
			$findings[] = array("type" => "warning", "message" => "Average sentence length ({$avgSentenceLength} words) exceeds the recommended maximum of {$maxAvgSentenceLength} words.");
			$recommendations[] = "Break up long sentences. Web readers prefer concise sentences averaging 15-20 words.";
		}

		if ($hasWordCountIssue && $hasReadabilityIssue) {
			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		if ($hasWordCountIssue || $hasReadabilityIssue) {
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Count sentences by detecting sentence-ending punctuation.
	 */
	private function countSentences(string $text): int
	{
		$count = preg_match_all("/[.!?]+/", $text);

		return max((int) $count, 1);
	}

	/**
	 * Calculate the Flesch-Kincaid Reading Ease score.
	 * Score ranges: 90-100 very easy, 60-70 standard, 0-30 very difficult.
	 */
	private function calculateFleschKincaid(string $text, int $wordCount, int $sentenceCount): float
	{
		if ($wordCount === 0 || $sentenceCount === 0) {
			return 0.0;
		}

		$syllableCount = $this->countTotalSyllables($text);
		$score = 206.835 - 1.015 * ($wordCount / $sentenceCount) - 84.6 * ($syllableCount / $wordCount);

		return round(max(0, min(100, $score)), 1);
	}

	/**
	 * Count total syllables across all words in the text.
	 */
	private function countTotalSyllables(string $text): int
	{
		$words = preg_split("/\\s+/", strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
		$totalSyllables = 0;

		foreach ($words as $word) {
			$totalSyllables += $this->countWordSyllables($word);
		}

		return $totalSyllables;
	}

	/**
	 * Estimate syllable count for a single word by counting vowel groups.
	 */
	private function countWordSyllables(string $word): int
	{
		$word = preg_replace("/[^a-z]/", "", $word);
		if (strlen($word) <= 3) {
			return 1;
		}

		$syllableCount = (int) preg_match_all("/[aeiouy]+/", $word);

		if (preg_match("/[^aeiouy]e$/", $word)) {
			$syllableCount--;
		}

		return max($syllableCount, 1);
	}

	/**
	 * Truncate text to a maximum number of words, preserving whole words.
	 */
	private function truncateToWordLimit(string $text, int $maxWords): string
	{
		$words = preg_split("/\\s+/", $text, $maxWords + 1, PREG_SPLIT_NO_EMPTY);

		if (count($words) <= $maxWords) {
			return implode(" ", $words);
		}

		return implode(" ", array_slice($words, 0, $maxWords)) . "...";
	}

	/**
	 * Map Flesch-Kincaid score to a human-readable label.
	 */
	private function fleschScoreLabel(float $score): string
	{
		if ($score >= 90) {
			return "Very Easy";
		}

		if ($score >= 80) {
			return "Easy";
		}

		if ($score >= 70) {
			return "Fairly Easy";
		}

		if ($score >= 60) {
			return "Standard";
		}

		if ($score >= 50) {
			return "Fairly Difficult";
		}

		if ($score >= 30) {
			return "Difficult";
		}

		return "Very Difficult";
	}
}
