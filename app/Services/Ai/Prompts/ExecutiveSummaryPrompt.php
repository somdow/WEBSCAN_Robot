<?php

namespace App\Services\Ai\Prompts;

use App\Contracts\AiPromptInterface;
use Illuminate\Support\Collection;

class ExecutiveSummaryPrompt implements AiPromptInterface
{
	private const MAX_MODULE_LINES = 35;

	/**
	 * Status-to-multiplier mapping, mirroring ScoreCalculator logic.
	 * ok = full credit, warning = quarter credit, bad = zero, info = excluded.
	 */
	private const STATUS_MULTIPLIERS = array(
		"ok" => 1.0,
		"warning" => 0.25,
		"bad" => 0.0,
	);

	/**
	 * @param Collection $moduleResults   All ScanModuleResult models from the scan
	 * @param int        $overallScore    The computed overall SEO score (0-100)
	 * @param string     $siteUrl         The scanned website URL
	 * @param array      $targetKeywords  Project target keywords (may be empty)
	 * @param bool       $isWordPress     Whether the scanned site is WordPress
	 */
	public function __construct(
		private readonly Collection $moduleResults,
		private readonly int $overallScore,
		private readonly string $siteUrl,
		private readonly array $targetKeywords = array(),
		private readonly bool $isWordPress = false,
	) {}

	public function buildSystemPrompt(): string
	{
		return <<<'PROMPT'
You are an expert SEO analyst generating a concise executive summary for a website audit report.

Your audience is a website owner or marketing manager who may not be technical. Write in plain English — no jargon without explanation.

You MUST respond with valid JSON in exactly this structure:
{
  "summary": "2-3 sentence overview of the site's SEO health",
  "topIssues": [
    {"module": "moduleKey", "issue": "one-line description", "impact": "high|medium|low"}
  ],
  "quickWins": [
    {"action": "specific actionable step", "estimatedPoints": 5}
  ]
}

Scoring system:
- Each module has a weight and a status (ok = full points, warning = half points, bad = zero).
- The overall score is a weighted average on a 0-100 scale.
- Each module line below includes its weight and the exact point gain if fixed from its current status to ok. Use these REAL numbers for estimatedPoints — do NOT invent your own.

Rules:
- topIssues: Include 3-5 issues, ordered by impact (highest first). Only include modules with "warning" or "bad" status. Use the point gain values to determine impact: high (6+ points), medium (3-5 points), low (1-2 points).
- quickWins: Include 2-4 easy fixes the owner can do today. Use the pre-calculated point gain as estimatedPoints. A "quick win" is a fix that requires minimal effort — simple text changes (title, meta description, H1), adding a missing tag, or enabling a setting. Do NOT list complex fixes (rewriting entire page content, building backlinks, restructuring site) as quick wins.
- If target keywords are provided, reference them where relevant (e.g., "Add 'running shoes' to your H1 heading").
- Be encouraging but honest. If the score is high, acknowledge strengths before issues.
- Keep the summary under 80 words.
- Do NOT wrap your JSON in markdown code fences. Return raw JSON only.
PROMPT;
	}

	public function buildUserPrompt(): string
	{
		$moduleSummaries = $this->formatModuleResults();
		$keywordsLine = !empty($this->targetKeywords)
			? "Target Keywords: " . implode(", ", $this->targetKeywords) . "\n"
			: "";

		return <<<PROMPT
Website: {$this->siteUrl}
Overall SEO Score: {$this->overallScore}/100
{$keywordsLine}
Module Results:
{$moduleSummaries}

Generate the executive summary JSON.
PROMPT;
	}

	/**
	 * Format module results with weights and pre-calculated point gains.
	 * The AI receives real numbers so it doesn't have to guess.
	 */
	private function formatModuleResults(): string
	{
		$weights = config("scanning.weights", array());
		$totalWeight = $this->calculateTotalWeight($weights);
		$entriesByModule = array();

		foreach ($this->moduleResults as $result) {
			$status = $result->status instanceof \BackedEnum ? $result->status->value : $result->status;
			$moduleKey = $result->module_key;
			$weight = $this->resolveWeight($moduleKey, $weights);

			if ($status === "info" || $weight <= 0) {
				continue;
			}

			$firstFinding = $this->extractFirstFinding($result->findings);
			$recommendationCount = count($result->recommendations ?? array());
			$pointGain = $this->calculatePointGain($status, $weight, $totalWeight);

			$line = "- [{$status}] {$moduleKey} (weight: {$weight}";
			if ($pointGain > 0) {
				$line .= ", +{$pointGain}pts if fixed";
			}

			$line .= ")";

			if ($firstFinding !== "") {
				$line .= ": {$firstFinding}";
			}

			if ($recommendationCount > 0) {
				$line .= " ({$recommendationCount} recommendations)";
			}

			$statusPriority = match ($status) {
				"bad" => 300,
				"warning" => 200,
				default => 100,
			};

			$priority = ($pointGain * 10) + $statusPriority;
			$entry = array("line" => $line, "priority" => $priority);
			$current = $entriesByModule[$moduleKey] ?? null;

			if ($current === null || $priority > $current["priority"]) {
				$entriesByModule[$moduleKey] = $entry;
			}

		}

		$entries = array_values($entriesByModule);
		usort($entries, fn($a, $b) => $b["priority"] <=> $a["priority"]);
		$limitedEntries = array_slice($entries, 0, self::MAX_MODULE_LINES);
		$lines = array_map(fn($entry) => $entry["line"], $limitedEntries);

		$omittedCount = max(0, count($entries) - count($limitedEntries));
		if ($omittedCount > 0) {
			$lines[] = "- [info] {$omittedCount} additional module(s) omitted for brevity.";
		}

		return implode("\n", $lines);
	}

	/**
	 * Calculate the exact score point gain if a module is fixed from its current status to ok.
	 * Uses the same weighted-average formula as ScoreCalculator.
	 */
	private function calculatePointGain(string $status, int $weight, int $totalWeight): int
	{
		if ($totalWeight === 0 || $status === "ok") {
			return 0;
		}

		$currentMultiplier = self::STATUS_MULTIPLIERS[$status] ?? 0.0;
		$gainMultiplier = 1.0 - $currentMultiplier;

		return (int) round(($weight * $gainMultiplier / $totalWeight) * 100);
	}

	/**
	 * Sum the weights of all scoring-eligible modules in this scan.
	 */
	private function calculateTotalWeight(array $weights): int
	{
		$totalWeight = 0;

		foreach ($this->moduleResults as $result) {
			$status = $result->status instanceof \BackedEnum ? $result->status->value : $result->status;

			if ($status === "info") {
				continue;
			}

			$weight = $this->resolveWeight($result->module_key, $weights);
			$totalWeight += $weight;
		}

		return $totalWeight;
	}

	/**
	 * Resolve a module's weight, excluding WordPress-only modules on non-WP sites.
	 */
	private function resolveWeight(string $moduleKey, array $weights): int
	{
		$weight = $weights[$moduleKey] ?? 0;

		$wordPressOnlyModules = array("wpPlugins", "wpTheme");
		if (in_array($moduleKey, $wordPressOnlyModules, true) && !$this->isWordPress) {
			return 0;
		}

		return $weight;
	}

	private function extractFirstFinding(mixed $findings): string
	{
		if (!is_array($findings) || empty($findings)) {
			return "";
		}

		foreach ($findings as $finding) {
			$type = $finding["type"] ?? "info";

			if ($type === "data") {
				continue;
			}

			$message = $finding["message"] ?? "";

			if (mb_strlen($message) > 150) {
				return mb_substr($message, 0, 147) . "...";
			}

			return $message;
		}

		return "";
	}
}
