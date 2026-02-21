<?php

namespace App\Services\Scanning;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Models\Scan;
use App\Models\ScanModuleResult;
use App\Models\ScanPage;
use Illuminate\Support\Facades\Log;

/**
 * Handles per-page analysis: parsing HTML into a ScanContext, running per-page
 * analyzers, storing results with scan_page_id, and calculating per-page score.
 */
class PageAnalysisService
{
	public function __construct(
		private readonly HtmlParser $htmlParser,
		private readonly ModuleRegistry $moduleRegistry,
		private readonly ScoreCalculator $scoreCalculator,
		private readonly TrustPageCrawler $trustPageCrawler,
	) {}

	/**
	 * Analyze a single page: parse its HTML, run per-page analyzers,
	 * store results, and calculate the page score.
	 *
	 * Shared state (robotsTxtContent, isWordPress, detectionMethod, targetKeywords)
	 * is passed in from the orchestrator so site-wide results are available.
	 */
	public function analyzePage(
		Scan $scan,
		ScanPage $scanPage,
		string $html,
		?string $robotsTxtContent,
		bool $isWordPress,
		?string $detectionMethod,
		array $targetKeywords,
	): void {
		$startTime = microtime(true);

		try {
			$scanContext = $this->buildScanContext(
				$scanPage->url,
				$html,
				$robotsTxtContent,
				$isWordPress,
				$detectionMethod,
				$targetKeywords,
			);

			if ($scanContext === null) {
				$scanPage->update(array("error_message" => "Failed to parse HTML"));
				return;
			}

			if ($scanPage->is_homepage) {
				$scanContext = $this->runTrustPageCrawl($scanContext);
			}

			$this->runPerPageAnalyzers($scan, $scanPage, $scanContext);

			$pageScore = $this->calculatePageScore($scanPage, $isWordPress);
			$durationMs = (int) ((microtime(true) - $startTime) * 1000);

			$scanPage->update(array(
				"page_score" => $pageScore,
				"scan_duration_ms" => $durationMs,
			));
		} catch (\Throwable $exception) {
			Log::error("Page analysis failed", array(
				"scan_id" => $scan->id,
				"scan_page_id" => $scanPage->id,
				"url" => $scanPage->url,
				"error" => $exception->getMessage(),
			));

			$scanPage->update(array(
				"error_message" => mb_substr($exception->getMessage(), 0, 500),
				"scan_duration_ms" => (int) ((microtime(true) - $startTime) * 1000),
			));
		}
	}

	/**
	 * Build a ScanContext from raw HTML for a specific page URL.
	 */
	private function buildScanContext(
		string $url,
		string $html,
		?string $robotsTxtContent,
		bool $isWordPress,
		?string $detectionMethod,
		array $targetKeywords,
	): ?ScanContext {
		try {
			$parsed = $this->htmlParser->parseHtml($html);

			return new ScanContext(
				requestedUrl: $url,
				effectiveUrl: $url,
				htmlContent: $html,
				domDocument: $parsed["domDocument"],
				xpath: $parsed["xpath"],
				responseHeaders: array(),
				httpStatusCode: 200,
				timeToFirstByte: null,
				totalTransferTime: null,
				titleContent: $parsed["titleContent"],
				titleTagCount: $parsed["titleTagCount"],
				metaDescriptionContent: $parsed["metaDescriptionContent"],
				metaDescriptionTagCount: $parsed["metaDescriptionTagCount"],
				langAttribute: $parsed["langAttribute"],
				canonicalHrefs: $parsed["canonicalHrefs"],
				canonicalTagCount: $parsed["canonicalTagCount"],
				allHeadingsData: $parsed["allHeadingsData"],
				viewportContents: $parsed["viewportContents"],
				viewportTagCount: $parsed["viewportTagCount"],
				robotsMetaContent: $parsed["robotsMetaContent"],
				robotsMetaTagCount: $parsed["robotsMetaTagCount"],
				robotsTxtContent: $robotsTxtContent,
				isWordPress: $isWordPress,
				detectionMethod: $detectionMethod,
				targetKeywords: $targetKeywords,
			);
		} catch (\Throwable $exception) {
			Log::warning("Failed to build ScanContext for page", array(
				"url" => $url,
				"error" => $exception->getMessage(),
			));

			return null;
		}
	}

	/**
	 * Run trust page crawl for the homepage to provide E-E-A-T context.
	 */
	private function runTrustPageCrawl(ScanContext $scanContext): ScanContext
	{
		try {
			$trustPages = $this->trustPageCrawler->crawl($scanContext);

			return $scanContext->withTrustPageResults($trustPages);
		} catch (\Throwable $exception) {
			Log::warning("TrustPageCrawler failed during page analysis", array(
				"error" => $exception->getMessage(),
			));

			return $scanContext;
		}
	}

	/**
	 * Run all per-page analyzers and store results linked to the ScanPage.
	 */
	private function runPerPageAnalyzers(Scan $scan, ScanPage $scanPage, ScanContext $scanContext): void
	{
		foreach ($this->moduleRegistry->perPageAnalyzers() as $analyzer) {
			$this->executeAnalyzer($analyzer, $scanContext, $scan, $scanPage);
		}
	}

	/**
	 * Run a single analyzer with error isolation, storing results with scan_page_id.
	 */
	private function executeAnalyzer(
		AnalyzerInterface $analyzer,
		ScanContext $scanContext,
		Scan $scan,
		ScanPage $scanPage,
	): ?AnalysisResult {
		try {
			$result = $analyzer->analyze($scanContext);

			ScanModuleResult::create(array(
				"scan_id" => $scan->id,
				"scan_page_id" => $scanPage->id,
				"module_key" => $analyzer->moduleKey(),
				"status" => $result->status,
				"findings" => $result->findings,
				"recommendations" => $result->recommendations,
			));

			return $result;
		} catch (\Throwable $exception) {
			Log::error("Page analyzer failed: {$analyzer->moduleKey()}", array(
				"scan_id" => $scan->id,
				"scan_page_id" => $scanPage->id,
				"error" => $exception->getMessage(),
			));

			ScanModuleResult::create(array(
				"scan_id" => $scan->id,
				"scan_page_id" => $scanPage->id,
				"module_key" => $analyzer->moduleKey(),
				"status" => ModuleStatus::Info,
				"findings" => array(
					array("type" => "info", "message" => "This module encountered an error during analysis."),
				),
				"recommendations" => array(),
			));

			return null;
		}
	}

	/**
	 * Calculate the score for a single page from its module results.
	 */
	private function calculatePageScore(ScanPage $scanPage, bool $isWordPress): int
	{
		$moduleResults = $scanPage->moduleResults()->get();

		return $this->scoreCalculator->calculateScore($moduleResults, $isWordPress);
	}
}
