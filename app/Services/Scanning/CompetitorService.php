<?php

namespace App\Services\Scanning;

use App\Enums\CreditState;
use App\Enums\ScanStatus;
use App\Jobs\ProcessScanJob;
use App\Models\Competitor;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use App\Services\BillingService;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Support\Facades\Log;

class CompetitorService
{
	public function __construct(
		private readonly BillingService $billingService,
		private readonly ModuleRegistry $moduleRegistry,
	) {}

	/**
	 * Validate and create a new competitor for a project.
	 *
	 * @throws \InvalidArgumentException When URL validation or plan limit fails.
	 */
	public function addCompetitor(Project $project, string $url, Organization $organization): Competitor
	{
		$normalizedUrl = UrlNormalizer::forCrawl($url);

		$this->validateDifferentDomain($project, $normalizedUrl);
		$this->validateNoDuplicate($project, $normalizedUrl);
		$this->validatePlanLimit($project, $organization);

		return Competitor::create(array(
			"project_id" => $project->id,
			"url" => $normalizedUrl,
			"name" => parse_url($normalizedUrl, PHP_URL_HOST),
		));
	}

	/**
	 * Trigger a scan for a competitor.
	 * Caller must have already claimed a scan credit.
	 */
	public function triggerScan(Competitor $competitor, User $triggeredBy): Scan
	{
		$scan = Scan::create(array(
			"project_id" => $competitor->project_id,
			"competitor_id" => $competitor->id,
			"triggered_by" => $triggeredBy->id,
			"status" => ScanStatus::Pending,
			"scan_type" => "single",
			"max_pages_requested" => 1,
			"crawl_depth_limit" => 0,
			"credit_state" => CreditState::Claimed->value,
		));

		ProcessScanJob::dispatch($scan);

		Log::info("Competitor scan triggered", array(
			"competitor_id" => $competitor->id,
			"scan_id" => $scan->id,
			"url" => $competitor->url,
		));

		return $scan;
	}

	/**
	 * Competitor URL must be a different domain than the project.
	 */
	private function validateDifferentDomain(Project $project, string $url): void
	{
		$projectHost = strtolower(parse_url($project->url, PHP_URL_HOST) ?? "");
		$competitorHost = strtolower(parse_url($url, PHP_URL_HOST) ?? "");

		if (!$competitorHost) {
			throw new \InvalidArgumentException("Invalid URL format.");
		}

		$projectRoot = $this->extractRootDomain($projectHost);
		$competitorRoot = $this->extractRootDomain($competitorHost);

		if ($projectRoot === $competitorRoot) {
			throw new \InvalidArgumentException("Competitor must be a different website than your project. Use 'Add Pages' for pages on the same domain.");
		}
	}

	/**
	 * No duplicate competitor URLs per project.
	 */
	private function validateNoDuplicate(Project $project, string $url): void
	{
		$normalizedForComparison = UrlNormalizer::forComparison($url);

		$existingUrls = $project->competitors()->pluck("url")->map(
			fn(string $existingUrl) => UrlNormalizer::forComparison($existingUrl)
		);

		if ($existingUrls->contains($normalizedForComparison)) {
			throw new \InvalidArgumentException("This competitor has already been added to this project.");
		}
	}

	/**
	 * Enforce plan limit on competitors per project.
	 */
	private function validatePlanLimit(Project $project, Organization $organization): void
	{
		if (!$this->billingService->canAddCompetitor($organization, $project)) {
			$maxCompetitors = $organization->plan?->max_competitors ?? 0;
			throw new \InvalidArgumentException(
				"You've reached your plan limit of {$maxCompetitors} competitors. Upgrade for more."
			);
		}
	}

	/**
	 * Build category score breakdown from a completed scan's module results.
	 */
	public function buildCategoryScores(Scan $scan): array
	{
		$categoryScores = array();

		if (!$scan->isComplete() || $scan->status !== ScanStatus::Completed) {
			return $categoryScores;
		}

		$moduleResults = $scan->moduleResults()->get();
		$grouped = $moduleResults->groupBy(
			fn($result) => $this->moduleRegistry->resolveCategory($result->module_key)
		);

		foreach ($grouped as $categoryName => $categoryModules) {
			$total = $categoryModules->count();
			$passed = $categoryModules->filter(fn($r) => $r->status->value === "ok")->count();
			$categoryScores[] = array(
				"name" => $categoryName,
				"passed" => $passed,
				"total" => $total,
			);
		}

		return $categoryScores;
	}

	/**
	 * Extract root domain (e.g. "example.com" from "www.example.com").
	 * Handles common multi-part TLDs (co.uk, com.au, etc).
	 */
	private function extractRootDomain(string $host): string
	{
		$parts = explode(".", $host);
		$partCount = count($parts);

		if ($partCount <= 2) {
			return implode(".", $parts);
		}

		$multiPartTlds = array(
			"co.uk", "org.uk", "ac.uk", "gov.uk",
			"com.au", "net.au", "org.au",
			"co.nz", "net.nz", "org.nz",
			"co.za", "org.za",
			"com.br", "org.br",
			"co.in", "org.in", "net.in",
			"co.jp", "or.jp",
			"co.kr", "or.kr",
			"com.mx", "org.mx",
			"com.sg", "org.sg",
			"com.hk", "org.hk",
			"co.il", "org.il",
			"com.tw", "org.tw",
			"co.th", "or.th",
			"com.tr", "org.tr",
		);

		$lastTwo = $parts[$partCount - 2] . "." . $parts[$partCount - 1];
		if (in_array($lastTwo, $multiPartTlds, true)) {
			return implode(".", array_slice($parts, -3));
		}

		return implode(".", array_slice($parts, -2));
	}
}
