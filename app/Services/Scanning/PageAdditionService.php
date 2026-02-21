<?php

namespace App\Services\Scanning;

use App\Enums\CreditState;
use App\Jobs\ProcessPageAnalysisJob;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ScanPage;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Validates, deduplicates, and creates additional ScanPage records
 * for manually added pages, then dispatches background analysis.
 * Pages belong to the project and persist across rescans.
 */
class PageAdditionService
{
	/**
	 * Add a page URL to a project for analysis.
	 *
	 * @throws \InvalidArgumentException When URL validation fails.
	 * @throws \OverflowException When plan page limit is reached.
	 */
	public function addPage(Project $project, string $url, Plan $plan): ScanPage
	{
		$normalizedUrl = UrlNormalizer::forCrawl($url);

		$this->validateSameDomain($project, $normalizedUrl);
		$this->validateNoDuplicate($project, $normalizedUrl);
		$this->validatePlanLimit($project, $plan);

		$scanPage = ScanPage::create(array(
			"project_id" => $project->id,
			"url" => $normalizedUrl,
			"is_homepage" => false,
			"crawl_depth" => 0,
			"source" => "manual",
			"analysis_status" => "pending",
			"credit_state" => CreditState::Claimed->value,
		));

		ProcessPageAnalysisJob::dispatch($scanPage);

		Log::info("Page added for analysis", array(
			"project_id" => $project->id,
			"scan_page_id" => $scanPage->id,
			"url" => $normalizedUrl,
		));

		return $scanPage;
	}

	/**
	 * Ensure the URL belongs to the same domain as the project.
	 * Compares root domains to allow subdomains (www.example.com matches example.com).
	 */
	private function validateSameDomain(Project $project, string $url): void
	{
		$projectHost = parse_url($project->url, PHP_URL_HOST);
		$pageHost = parse_url($url, PHP_URL_HOST);

		if (!$projectHost || !$pageHost) {
			throw new \InvalidArgumentException("Invalid URL format.");
		}

		$projectRoot = $this->extractRegistrableDomain($projectHost);
		$pageRoot = $this->extractRegistrableDomain($pageHost);

		if ($projectRoot !== $pageRoot) {
			throw new \InvalidArgumentException("URL must belong to the same domain as the project ({$projectHost}).");
		}
	}

	/**
	 * Ensure this URL isn't already in the project's pages.
	 */
	private function validateNoDuplicate(Project $project, string $url): void
	{
		$normalizedForComparison = UrlNormalizer::forComparison($url);

		$existingUrls = $project->pages()->pluck("url")->map(
			fn(string $existingUrl) => UrlNormalizer::forComparison($existingUrl)
		);

		if ($existingUrls->contains($normalizedForComparison)) {
			throw new \InvalidArgumentException("This page has already been added to this project.");
		}
	}

	/**
	 * Ensure the plan allows more additional pages for this project.
	 */
	private function validatePlanLimit(Project $project, Plan $plan): void
	{
		$additionalPageCount = $project->additionalPages()->count();

		if ($additionalPageCount >= $plan->max_additional_pages) {
			throw new \OverflowException(
				"You've reached your plan limit of {$plan->max_additional_pages} additional pages. Upgrade for more."
			);
		}
	}

	/**
	 * Extract the registrable domain (e.g. "example.com" from "www.sub.example.com",
	 * "example.co.uk" from "www.example.co.uk"). Handles common multi-part TLDs.
	 */
	private function extractRegistrableDomain(string $host): string
	{
		$parts = explode(".", strtolower($host));
		$partCount = count($parts);

		if ($partCount <= 2) {
			return implode(".", $parts);
		}

		$multiPartTlds = array(
			"co.uk", "org.uk", "ac.uk", "gov.uk", "me.uk",
			"com.au", "net.au", "org.au", "edu.au",
			"co.nz", "net.nz", "org.nz",
			"co.za", "org.za", "web.za",
			"com.br", "org.br", "net.br",
			"co.in", "org.in", "net.in", "gov.in",
			"co.jp", "or.jp", "ne.jp",
			"co.kr", "or.kr",
			"com.mx", "org.mx",
			"com.sg", "org.sg",
			"com.hk", "org.hk",
			"co.il", "org.il",
			"com.tw", "org.tw",
			"co.th", "or.th",
			"com.ph", "org.ph",
			"com.my", "org.my",
			"com.tr", "org.tr",
			"co.id", "or.id",
		);

		if ($partCount >= 3) {
			$lastTwo = $parts[$partCount - 2] . "." . $parts[$partCount - 1];

			if (in_array($lastTwo, $multiPartTlds, true)) {
				return implode(".", array_slice($parts, -3));
			}
		}

		return implode(".", array_slice($parts, -2));
	}
}
