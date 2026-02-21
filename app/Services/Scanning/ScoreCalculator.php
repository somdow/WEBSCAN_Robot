<?php

namespace App\Services\Scanning;

use App\Enums\ModuleStatus;
use App\Models\ScanModuleResult;
use Illuminate\Support\Collection;

class ScoreCalculator
{
	/**
	 * Status-to-multiplier mapping for score calculation.
	 * ok = full points, warning = half, bad = zero, info = excluded
	 */
	private const STATUS_MULTIPLIERS = array(
		"ok" => 1.0,
		"warning" => 0.5,
		"bad" => 0.0,
	);

	/**
	 * Calculate the overall score (0-100) from a collection of module results.
	 *
	 * @param Collection<ScanModuleResult> $moduleResults
	 */
	public function calculateScore(Collection $moduleResults, bool $isWordPress): int
	{
		$totalWeightedScore = 0.0;
		$totalWeight = 0;
		$weights = config("scanning.weights", array());
		$scoreCaps = config("scanning.score_caps", array());
		$appliedCap = 100;

		foreach ($moduleResults as $moduleResult) {
			$moduleKey = $moduleResult->module_key;
			$statusValue = $moduleResult->status instanceof ModuleStatus
				? $moduleResult->status->value
				: (string) $moduleResult->status;

			if ($statusValue === "info") {
				continue;
			}

			$weight = $this->resolveWeight($moduleKey, $weights, $isWordPress);

			if ($weight <= 0) {
				continue;
			}

			$multiplier = self::STATUS_MULTIPLIERS[$statusValue] ?? 0.0;
			$totalWeightedScore += $weight * $multiplier;
			$totalWeight += $weight;

			if ($statusValue === "bad" && isset($scoreCaps[$moduleKey])) {
				$appliedCap = min($appliedCap, $scoreCaps[$moduleKey]);
			}
		}

		if ($totalWeight === 0) {
			return 0;
		}

		$score = (int) round(($totalWeightedScore / $totalWeight) * 100);

		return min($score, $appliedCap);
	}

	/**
	 * Calculate an aggregate score for multi-page crawl scans.
	 *
	 * Formula: 70% weighted average of page scores + 30% site-wide module scores.
	 * Homepage is weighted at a configurable multiplier (default 2x).
	 *
	 * @param Collection<\App\Models\ScanPage> $scanPages
	 * @param Collection<ScanModuleResult> $siteWideResults Module results with scan_page_id = null
	 */
	public function calculateAggregateScore(
		Collection $scanPages,
		Collection $siteWideResults,
		bool $isWordPress,
	): int {
		$pageScoreComponent = $this->calculateWeightedPageScore($scanPages);
		$siteWideComponent = $this->calculateScore($siteWideResults, $isWordPress);

		if ($pageScoreComponent === null && $siteWideComponent === 0) {
			return 0;
		}

		if ($pageScoreComponent === null) {
			return $siteWideComponent;
		}

		return (int) round(($pageScoreComponent * 0.7) + ($siteWideComponent * 0.3));
	}

	/**
	 * Calculate the weighted average of page scores.
	 * Homepage gets a configurable weight multiplier (default 2x).
	 *
	 * @param Collection<\App\Models\ScanPage> $scanPages
	 */
	private function calculateWeightedPageScore(Collection $scanPages): ?float
	{
		$homepageWeight = (float) config("scanning.crawl.homepage_score_weight", 2.0);
		$totalWeight = 0.0;
		$weightedSum = 0.0;

		foreach ($scanPages as $page) {
			if ($page->page_score === null) {
				continue;
			}

			$weight = $page->is_homepage ? $homepageWeight : 1.0;
			$weightedSum += $page->page_score * $weight;
			$totalWeight += $weight;
		}

		if ($totalWeight === 0.0) {
			return null;
		}

		return $weightedSum / $totalWeight;
	}

	/**
	 * Calculate independent sub-scores for SEO and Site Health module groups.
	 * Partitions modules by category using the scan-ui config, then scores each subset.
	 *
	 * @param Collection<ScanModuleResult> $moduleResults
	 * @return array{seo: int, health: int}
	 */
	public function calculateSubScores(Collection $moduleResults, ModuleRegistry $moduleRegistry, bool $isWordPress): array
	{
		$categoryGroups = config("scan-ui.score_category_groups", array());
		$seoCategories = $categoryGroups["seo"] ?? array();
		$healthCategories = $categoryGroups["health"] ?? array();

		$seoResults = $moduleResults->filter(
			fn($result) => in_array($moduleRegistry->resolveCategory($result->module_key), $seoCategories, true)
		);

		$healthResults = $moduleResults->filter(
			fn($result) => in_array($moduleRegistry->resolveCategory($result->module_key), $healthCategories, true)
		);

		return array(
			"seo" => $this->calculateScore($seoResults, $isWordPress),
			"health" => $this->calculateScore($healthResults, $isWordPress),
		);
	}

	/**
	 * Resolve the weight for a module, applying WordPress-specific logic.
	 * WP modules only count when the site is actually WordPress.
	 */
	private function resolveWeight(string $moduleKey, array $weights, bool $isWordPress): int
	{
		$weight = $weights[$moduleKey] ?? 0;

		$wordPressOnlyModules = array("wpPlugins", "wpTheme");
		if (in_array($moduleKey, $wordPressOnlyModules, true) && !$isWordPress) {
			return 0;
		}

		return $weight;
	}
}
