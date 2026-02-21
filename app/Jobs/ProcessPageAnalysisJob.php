<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Models\ScanPage;
use App\Services\BillingService;
use App\Services\Scanning\HttpFetcher;
use App\Services\Scanning\ModuleRegistry;
use App\Services\Scanning\PageAnalysisService;
use App\Services\Scanning\ScoreCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes a single page (manually added, discovered, or rescanned) in the background.
 * Fetches HTML, extracts site-wide context from the project's latest completed scan,
 * then delegates to PageAnalysisService for per-page module execution.
 * Credits are claimed before dispatch and refunded if this job fails.
 */
class ProcessPageAnalysisJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $tries = 2;

	public int $timeout = 80;

	public function __construct(
		public readonly ScanPage $scanPage,
	) {}

	public function retryUntil(): \DateTime
	{
		return now()->addSeconds(90);
	}

	public function handle(HttpFetcher $httpFetcher, PageAnalysisService $pageAnalysisService, ScoreCalculator $scoreCalculator, ModuleRegistry $moduleRegistry): void
	{
		ini_set("memory_limit", "512M");

		$this->scanPage->update(array("analysis_status" => "running"));

		$project = $this->scanPage->project;
		$project->load("organization");

		$scan = $this->resolveContextScan($project);
		if (!$scan) {
			$this->markFailed("No completed homepage scan found. Run a homepage scan first.");
			return;
		}

		$fetchResult = $httpFetcher->fetchPage($this->scanPage->url);

		if (!$fetchResult->successful) {
			$this->markFailed("Could not fetch page: " . ($fetchResult->errorMessage ?? "Unknown error"));
			return;
		}

		$this->scanPage->update(array(
			"http_status_code" => $fetchResult->httpStatusCode,
			"content_type" => $fetchResult->headers["content-type"] ?? null,
		));

		if ($fetchResult->httpStatusCode >= 400) {
			$this->markFailed("HTTP {$fetchResult->httpStatusCode}: Page is not accessible.");
			return;
		}

		$siteContext = $this->extractSiteWideContext($scan, $project);

		$pageAnalysisService->analyzePage(
			$scan,
			$this->scanPage,
			$fetchResult->content,
			$siteContext["robotsTxtContent"],
			$siteContext["isWordPress"],
			$siteContext["detectionMethod"],
			$siteContext["targetKeywords"],
		);

		$this->scanPage->update(array(
			"scan_id" => $scan->id,
			"analysis_status" => "completed",
		));

		$this->recalculateScanScores($scan, $scoreCalculator, $moduleRegistry);

		Log::info("Page analysis completed", array(
			"scan_page_id" => $this->scanPage->id,
			"url" => $this->scanPage->url,
			"page_score" => $this->scanPage->fresh()->page_score,
		));
	}

	/**
	 * Find the latest completed scan for the project to use as site-wide context source.
	 */
	private function resolveContextScan(\App\Models\Project $project): ?Scan
	{
		return $project->ownScans()
			->where("status", \App\Enums\ScanStatus::Completed->value)
			->latest()
			->first();
	}

	/**
	 * Extract site-wide context from the context scan's existing module results.
	 * This data was produced by Phase 1 analyzers during the homepage scan.
	 */
	private function extractSiteWideContext(Scan $scan, \App\Models\Project $project): array
	{
		$robotsTxtContent = null;
		$isWordPress = $scan->is_wordpress ?? false;
		$detectionMethod = $scan->detection_method;
		$targetKeywords = $project->target_keywords ?? array();

		$robotsTxtResult = $scan->moduleResults()
			->where("module_key", "robotsTxt")
			->whereNull("scan_page_id")
			->first();

		if ($robotsTxtResult) {
			foreach ($robotsTxtResult->findings as $finding) {
				if (($finding["key"] ?? null) === "robotsTxtContent") {
					$robotsTxtContent = $finding["value"] ?? null;
					break;
				}
			}
		}

		return array(
			"robotsTxtContent" => $robotsTxtContent,
			"isWordPress" => $isWordPress,
			"detectionMethod" => $detectionMethod,
			"targetKeywords" => $targetKeywords,
		);
	}

	/**
	 * Recalculate the scan's overall, SEO, and health scores
	 * from the full set of module results (site-wide + all pages).
	 */
	private function recalculateScanScores(Scan $scan, ScoreCalculator $scoreCalculator, ModuleRegistry $moduleRegistry): void
	{
		$allModuleResults = $scan->moduleResults()->get();
		$isWordPress = $scan->is_wordpress ?? false;

		$overallScore = $scoreCalculator->calculateScore($allModuleResults, $isWordPress);
		$subScores = $scoreCalculator->calculateSubScores($allModuleResults, $moduleRegistry, $isWordPress);

		$scan->updateQuietly(array(
			"overall_score" => $overallScore,
			"seo_score" => $subScores["seo"],
			"health_score" => $subScores["health"],
		));

		Log::debug("Scan scores recalculated after page addition", array(
			"scan_id" => $scan->id,
			"overall_score" => $overallScore,
			"seo_score" => $subScores["seo"],
			"health_score" => $subScores["health"],
		));
	}

	/**
	 * Mark the page analysis as failed with an error message.
	 */
	private function markFailed(string $errorMessage): void
	{
		$this->scanPage->update(array(
			"analysis_status" => "failed",
			"error_message" => mb_substr($errorMessage, 0, 500),
		));

		app(BillingService::class)->releaseCreditForModel($this->scanPage);

		Log::warning("Page analysis failed", array(
			"scan_page_id" => $this->scanPage->id,
			"url" => $this->scanPage->url,
			"error" => $errorMessage,
		));
	}

	/**
	 * Handle permanent failure after all retries exhausted.
	 */
	public function failed(\Throwable $exception): void
	{
		Log::error("ProcessPageAnalysisJob permanently failed", array(
			"scan_page_id" => $this->scanPage->id,
			"url" => $this->scanPage->url,
			"exception" => $exception->getMessage(),
		));

		$this->scanPage->update(array(
			"analysis_status" => "failed",
			"error_message" => mb_substr("Analysis failed: " . $exception->getMessage(), 0, 500),
		));

		app(BillingService::class)->releaseCreditForModel($this->scanPage);
	}
}
