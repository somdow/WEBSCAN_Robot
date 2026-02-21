<?php

namespace App\Services\Scanning;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\FetchResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Enums\ScanStatus;
use App\Models\DiscoveredPage;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanModuleResult;
use App\Models\ScanPage;
use App\Notifications\ScanCompleteNotification;
use App\Services\Crawling\LinkExtractor;
use App\Services\Crawling\SameDomainHtmlProfile;
use App\Services\Crawling\ScanPageCollector;
use App\Services\Crawling\SiteCrawlerService;
use App\Services\Crawling\ZyteCrawlerService;
use App\Services\Analyzers\Seo\CoreWebVitalsBaseAnalyzer;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScanOrchestrator
{
	/** Whether the Zyte fallback was activated for this scan (homepage was blocked). */
	private bool $useZyteFallback = false;

	/** Block reason when response validation fails. */
	private ?string $blockReason = null;

	/** Connection-level error message (SSL, DNS, timeout) — distinct from bot protection blocks. */
	private ?string $connectionError = null;

	/** Whether any request used insecure TLS fallback. */
	private bool $insecureTransportUsed = false;

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
		private readonly HtmlParser $htmlParser,
		private readonly ModuleRegistry $moduleRegistry,
		private readonly ScoreCalculator $scoreCalculator,
		private readonly TrustPageCrawler $trustPageCrawler,
		private readonly PageAnalysisService $pageAnalysisService,
		private readonly SiteCrawlerService $siteCrawlerService,
		private readonly ZyteFetcher $zyteFetcher,
		private readonly ResponseValidator $responseValidator,
		private readonly ZyteCrawlerService $zyteCrawlerService,
		private readonly \App\Services\BillingService $billingService,
	) {}

	/**
	 * Execute a complete scan for the given Scan model.
	 * Branches on scan_type: "crawl" for multi-page, "single" for single-page.
	 */
	public function executeScan(Scan $scan): void
	{
		if ($scan->isCrawlScan()) {
			$this->executeCrawlScan($scan);
		} else {
			$this->executeSinglePageScan($scan);
		}
	}

	/* ──────────────────────────────────────────────────────────────────────
	 * Single-Page Scan (preserves existing behavior exactly)
	 * ──────────────────────────────────────────────────────────────────── */

	private function executeSinglePageScan(Scan $scan): void
	{
		$startTime = microtime(true);

		$scan->update(array("status" => ScanStatus::Running));
		$this->reportProgress($scan, 0, "Preparing scan...");

		try {
			$project = $scan->project;

			$this->reportProgress($scan, 5, "Fetching page content...");
			$targetUrl = $this->resolveTargetUrl($scan);
			$targetKeywords = $scan->isCompetitorScan() ? array() : ($project->target_keywords ?? array());
			$scanContext = $this->fetchAndParse($targetUrl, $scan, $targetKeywords);

			if ($scanContext === null) {
				$this->handleFetchFailure($scan, $startTime);
				return;
			}

			$this->reportProgress($scan, 12, "Analyzing robots.txt & detecting CMS...");
			$scanContext = $this->runPhaseOne($scan, $scanContext);

			$this->reportProgress($scan, 18, "Analyzing sitemap...");
			$this->runPhaseTwo($scan, $scanContext);

			$this->reportProgress($scan, 22, "Checking blacklist status...");
			$this->runBlacklistCheck($scan, $scanContext);

			$this->reportProgress($scan, 28, "Running security analysis...");
			$this->runSecurityChecks($scan, $scanContext);

			$this->reportProgress($scan, 32, "Verifying HTTPS configuration...");
			$this->runHttpsRedirectCheck($scan, $scanContext);

			$this->reportProgress($scan, 36, "Checking URL normalization...");
			$this->runDuplicateUrlCheck($scan, $scanContext);

			$this->reportProgress($scan, 40, "Detecting technology stack...");
			$this->runTechStackDetection($scan, $scanContext);

			$this->reportProgress($scan, 48, "Measuring Core Web Vitals (mobile)...");
			$this->runCoreWebVitalsMobile($scan, $scanContext);

			$this->reportProgress($scan, 56, "Measuring Core Web Vitals (desktop)...");
			$screenshotBase64 = $this->runCoreWebVitalsDesktop($scan, $scanContext);
			$this->captureHomepageScreenshot($scan, $scanContext->effectiveUrl, $screenshotBase64);

			$this->reportProgress($scan, 62, "Detecting analytics & tracking...");
			$this->runAnalyticsDetection($scan, $scanContext);

			$this->reportProgress($scan, 68, "Crawling trust pages...");
			$scanContext = $this->runTrustPageCrawl($scanContext);

			$this->reportProgress($scan, 78, "Running SEO analysis...");
			$this->runStandardSeoAnalyzers($scan, $scanContext);

			if ($scanContext->isWordPress) {
				$this->reportProgress($scan, 85, "Analyzing WordPress...");
				$this->runWordPressAnalyzers($scan, $scanContext);
			}

			$this->reportProgress($scan, 90, "Running final checks...");
			$this->runUtilityAnalyzers($scan, $scanContext);

			$this->reportProgress($scan, 96, "Calculating score...");
			$this->createSinglePageRecord($scan, $targetUrl, $scanContext->isWordPress);
			$this->finalizeScan($scan, $scanContext->isWordPress, $scanContext->detectionMethod, $startTime, $this->resolveCurrentFetcher());

			if (!$scan->isCompetitorScan()) {
				$this->autoDiscoverLinks($project, $scanContext);
			}
		} catch (\Throwable $exception) {
			Log::error("ScanOrchestrator single-page fatal error", array(
				"scan_id" => $scan->id,
				"error" => $exception->getMessage(),
				"trace" => $exception->getTraceAsString(),
			));
			$this->markFailed($scan, $startTime);
		}
	}

	/**
	 * Create a ScanPage record for backward compatibility.
	 * Single-page scans now also get a ScanPage so the data model is consistent.
	 */
	private function createSinglePageRecord(Scan $scan, string $url, bool $isWordPress): void
	{
		$moduleResults = $scan->moduleResults()->get();
		$pageScore = $this->scoreCalculator->calculateScore($moduleResults, $isWordPress);

		ScanPage::create(array(
			"project_id" => $scan->project_id,
			"scan_id" => $scan->id,
			"url" => $url,
			"page_score" => $pageScore,
			"http_status_code" => 200,
			"content_type" => "text/html",
			"is_homepage" => true,
			"crawl_depth" => 0,
		));

		$scan->update(array("pages_crawled" => 1));
	}

	/* ──────────────────────────────────────────────────────────────────────
	 * Multi-Page Crawl Scan
	 * ──────────────────────────────────────────────────────────────────── */

	private function executeCrawlScan(Scan $scan): void
	{
		$startTime = microtime(true);

		$scan->update(array("status" => ScanStatus::Running));
		$this->reportProgress($scan, 0, "Preparing crawl scan...");

		try {
			$project = $scan->project;
			$targetKeywords = $project->target_keywords ?? array();

			/* Step 1: Fetch homepage to build initial ScanContext for site-wide analyzers */
			$this->reportProgress($scan, 5, "Fetching homepage...");
			$scanContext = $this->fetchAndParse($project->url, $scan, $targetKeywords);

			if ($scanContext === null) {
				$this->handleFetchFailure($scan, $startTime);
				return;
			}

			/* Step 2: Run site-wide analyzers (robots.txt, WP detection, sitemap, HTTP headers) */
			$scanContext = $this->runSiteWideAnalyzers($scan, $scanContext);

			/* Step 3: Crawl the site to discover pages */
			$maxPages = $scan->max_pages_requested ?? 25;
			$maxDepth = $scan->crawl_depth_limit ?? (int) config("scanning.crawl.default_max_depth", 3);
			$fetcherUsed = "guzzle";

			if ($this->useZyteFallback) {
				$fetcherUsed = "zyte";
				$collector = $this->crawlViaZyte($scan, $project->url, $maxPages, $maxDepth);
			} else {
				$this->reportProgress($scan, 28, "Crawling site for pages...");
				$collector = $this->siteCrawlerService->crawl($scan, $project->url, $maxPages, $maxDepth);

				/* Post-crawl validation: check if collected pages are real or bot-protection challenge pages */
				$collector = $this->validateCrawlResults($scan, $collector, $project->url, $maxPages, $maxDepth, $fetcherUsed);
			}

			$collectedPages = $collector->getCollectedPages();
			$totalPages = count($collectedPages);

			Log::info("Crawl complete, analyzing pages", array(
				"scan_id" => $scan->id,
				"pages_collected" => $totalPages,
				"fetcher" => $fetcherUsed,
			));

			$this->reportProgress($scan, 45, "Crawl complete \u2014 {$totalPages} " . ($totalPages === 1 ? "page" : "pages") . " found");

			/* Step 4: Analyze each collected page with per-page analyzers */
			foreach ($collectedPages as $pageIndex => $pageData) {
				$pageNumber = $pageIndex + 1;
				$analyzePercent = 45 + (int) (45 * $pageIndex / max($totalPages, 1));
				$this->reportProgress($scan, $analyzePercent, "Analyzing page {$pageNumber} of {$totalPages}...");

				$this->pageAnalysisService->analyzePage(
					$scan,
					$pageData["scanPage"],
					$pageData["html"],
					$scanContext->robotsTxtContent,
					$scanContext->isWordPress,
					$scanContext->detectionMethod,
					$targetKeywords,
				);
			}

			/* Step 5: Release HTML from memory */
			$collector->releaseHtml();

			/* Step 5.5: Enrich sitemap module with crawl cross-reference data */
			$this->reportProgress($scan, 92, "Cross-referencing sitemap data...");
			$this->enrichSitemapCrossReference($scan);

			/* Step 6: Calculate aggregate score and finalize */
			$this->reportProgress($scan, 96, "Calculating aggregate score...");
			$this->finalizeCrawlScan($scan, $scanContext->isWordPress, $scanContext->detectionMethod, $startTime, $fetcherUsed);
		} catch (\Throwable $exception) {
			Log::error("ScanOrchestrator crawl scan fatal error", array(
				"scan_id" => $scan->id,
				"error" => $exception->getMessage(),
				"trace" => $exception->getTraceAsString(),
			));
			$this->markFailed($scan, $startTime);
		}
	}

	/**
	 * Run site-wide analyzers for crawl scans: Phase 1 (robots, WP detection),
	 * Phase 2 (sitemap), HTTP headers. Results stored with scan_page_id = NULL.
	 */
	private function runSiteWideAnalyzers(Scan $scan, ScanContext $scanContext): ScanContext
	{
		$this->reportProgress($scan, 8, "Analyzing robots.txt & detecting CMS...");
		$scanContext = $this->runPhaseOne($scan, $scanContext);

		$this->reportProgress($scan, 12, "Analyzing sitemap...");
		$this->runPhaseTwo($scan, $scanContext);

		/* Run httpHeaders as site-wide (it analyzes the homepage response headers) */
		$this->reportProgress($scan, 14, "Checking HTTP headers...");
		$httpHeadersAnalyzer = $this->moduleRegistry->getAnalyzer("httpHeaders");
		if ($httpHeadersAnalyzer !== null) {
			$this->executeAnalyzer($httpHeadersAnalyzer, $scanContext, $scan);
		}

		$this->reportProgress($scan, 16, "Checking blacklist status...");
		$this->runBlacklistCheck($scan, $scanContext);

		$this->reportProgress($scan, 18, "Running security analysis...");
		$this->runSecurityChecks($scan, $scanContext);

		$this->reportProgress($scan, 20, "Running site-wide checks...");
		$this->runHttpsRedirectCheck($scan, $scanContext);
		$this->runDuplicateUrlCheck($scan, $scanContext);
		$this->runTechStackDetection($scan, $scanContext);

		$this->reportProgress($scan, 24, "Measuring performance...");
		$this->runCoreWebVitalsMobile($scan, $scanContext);
		$screenshotBase64 = $this->runCoreWebVitalsDesktop($scan, $scanContext);
		$this->captureHomepageScreenshot($scan, $scanContext->effectiveUrl, $screenshotBase64);
		$this->runAnalyticsDetection($scan, $scanContext);

		/* Run WP-specific site-wide analyzers if WordPress detected */
		if ($scanContext->isWordPress) {
			$this->reportProgress($scan, 26, "Analyzing WordPress...");
			$this->runWordPressAnalyzers($scan, $scanContext);
		}

		return $scanContext;
	}

	/**
	 * Calculate aggregate score and finalize a crawl scan.
	 */
	private function finalizeCrawlScan(Scan $scan, bool $isWordPress, ?string $detectionMethod, float $startTime, string $fetcherUsed = "guzzle"): void
	{
		$durationMs = (int) ((microtime(true) - $startTime) * 1000);

		$scanPages = $scan->pages()->get();
		$siteWideResults = $scan->moduleResults()->whereNull("scan_page_id")->get();

		$overallScore = $this->scoreCalculator->calculateAggregateScore(
			$scanPages,
			$siteWideResults,
			$isWordPress,
		);

		$allModuleResults = $scan->moduleResults()->get();
		$subScores = $this->scoreCalculator->calculateSubScores($allModuleResults, $this->moduleRegistry, $isWordPress);

		$scan->update(array(
			"status" => ScanStatus::Completed,
			"overall_score" => $overallScore,
			"seo_score" => $subScores["seo"],
			"health_score" => $subScores["health"],
			"scan_duration_ms" => $durationMs,
			"is_wordpress" => $isWordPress,
			"detection_method" => $detectionMethod,
			"fetcher_used" => $fetcherUsed,
			"insecure_transport_used" => $this->insecureTransportUsed,
		));

		$this->updateCompetitorLatestScan($scan);

		if (!$scan->isCompetitorScan()) {
			try {
				$scan->triggeredBy?->notify(new ScanCompleteNotification($scan));
			} catch (\Throwable $exception) {
				Log::warning("Failed to send scan complete notification", array(
					"scan_id" => $scan->id,
					"error" => $exception->getMessage(),
				));
			}
		}
	}

	/**
	 * Compare sitemap URLs against crawled pages and store the cross-reference
	 * as enriched data on the sitemapAnalysis module result.
	 */
	private function enrichSitemapCrossReference(Scan $scan): void
	{
		try {
			$sitemapResult = $scan->moduleResults()
				->where("module_key", "sitemapAnalysis")
				->first();

			if ($sitemapResult === null) {
				return;
			}

			$findings = $sitemapResult->findings ?? array();
			$sitemapDetailsIndex = null;
			$sitemapDetails = null;

			foreach ($findings as $index => $finding) {
				if (($finding["type"] ?? null) === "data" && ($finding["key"] ?? null) === "sitemapDetails") {
					$sitemapDetailsIndex = $index;
					$sitemapDetails = $finding["value"] ?? null;
					break;
				}
			}

			if ($sitemapDetails === null || empty($sitemapDetails["parsed_urls"])) {
				return;
			}

			$sitemapUrls = $sitemapDetails["parsed_urls"];
			$crawledUrls = $scan->pages()->pluck("url")->toArray();

			$normalizedSitemap = array();
			foreach ($sitemapUrls as $url) {
				$normalizedSitemap[$this->normalizeUrlForComparison($url)] = $url;
			}

			$normalizedCrawled = array();
			foreach ($crawledUrls as $url) {
				$normalizedCrawled[$this->normalizeUrlForComparison($url)] = $url;
			}

			$sitemapNormalizedKeys = array_keys($normalizedSitemap);
			$crawledNormalizedKeys = array_keys($normalizedCrawled);

			$inSitemapOnlyKeys = array_diff($sitemapNormalizedKeys, $crawledNormalizedKeys);
			$crawledOnlyKeys = array_diff($crawledNormalizedKeys, $sitemapNormalizedKeys);
			$inBothCount = count(array_intersect($sitemapNormalizedKeys, $crawledNormalizedKeys));

			$inSitemapOnly = array_values(array_map(
				fn($key) => $normalizedSitemap[$key],
				array_slice(array_values($inSitemapOnlyKeys), 0, 50),
			));

			$crawledOnly = array_values(array_map(
				fn($key) => $normalizedCrawled[$key],
				array_slice(array_values($crawledOnlyKeys), 0, 50),
			));

			$crossReference = array(
				"crawled_count" => count($crawledUrls),
				"sitemap_count" => count($sitemapUrls),
				"in_both_count" => $inBothCount,
				"in_sitemap_only" => $inSitemapOnly,
				"in_sitemap_only_count" => count($inSitemapOnlyKeys),
				"crawled_only" => $crawledOnly,
				"crawled_only_count" => count($crawledOnlyKeys),
			);

			$sitemapDetails["cross_reference"] = $crossReference;
			unset($sitemapDetails["parsed_urls"]);

			$findings[$sitemapDetailsIndex]["value"] = $sitemapDetails;

			$sitemapResult->update(array("findings" => $findings));
		} catch (\Throwable $exception) {
			Log::warning("Sitemap cross-reference enrichment failed", array(
				"scan_id" => $scan->id,
				"error" => $exception->getMessage(),
			));
		}
	}

	/**
	 * Normalize a URL for set comparison: lowercase scheme+host, strip trailing slash,
	 * remove query string and fragment.
	 */
	private function normalizeUrlForComparison(string $url): string
	{
		return UrlNormalizer::forComparison($url);
	}

	/* ──────────────────────────────────────────────────────────────────────
	 * Shared Methods (used by both scan types)
	 * ──────────────────────────────────────────────────────────────────── */

	/**
	 * Fetch the target URL and parse its HTML into a ScanContext.
	 *
	 * Flow: Guzzle fetch (free) → validate response → if blocked and Zyte
	 * available → Zyte fetch ($0.001/page) → validate → if still blocked → null.
	 * Sets $this->useZyteFallback and $this->blockReason as side effects.
	 */
	private function fetchAndParse(string $url, Scan $scan, array $targetKeywords = array()): ?ScanContext
	{
		$this->useZyteFallback = false;
		$this->blockReason = null;
		$this->connectionError = null;
		$this->insecureTransportUsed = false;

		$fetchResult = $this->httpFetcher->fetchPage($url);
		$this->insecureTransportUsed = $this->insecureTransportUsed || $fetchResult->insecureTransportUsed;

		if (!$fetchResult->successful || $fetchResult->content === null) {
			Log::warning("ScanOrchestrator fetch failed", array(
				"url" => $url,
				"error" => $fetchResult->errorMessage,
			));

			$this->connectionError = $fetchResult->errorMessage;

			return $this->attemptZyteFallback($url, $scan, $targetKeywords);
		}

		/* Validate that the response is real HTML, not a bot-protection challenge */
		if (!$this->responseValidator->isRealHtmlPage($fetchResult->content, $fetchResult->httpStatusCode)) {
			$this->blockReason = $this->responseValidator->getBlockReason($fetchResult->content, $fetchResult->httpStatusCode);

			Log::warning("ScanOrchestrator homepage blocked by bot protection", array(
				"url" => $url,
				"reason" => $this->blockReason,
				"content_length" => strlen($fetchResult->content),
			));

			return $this->attemptZyteFallback($url, $scan, $targetKeywords);
		}

		return $this->buildScanContext($fetchResult, $url, $targetKeywords);
	}

	/**
	 * Attempt to fetch via Zyte API when the direct Guzzle request is blocked.
	 * Returns null if Zyte is unavailable or the page is still blocked.
	 */
	private function attemptZyteFallback(string $url, Scan $scan, array $targetKeywords): ?ScanContext
	{
		if (!$this->zyteFetcher->isAvailable()) {
			Log::info("ScanOrchestrator Zyte fallback not available", array("url" => $url));
			if ($this->connectionError === null) {
				$this->blockReason = $this->blockReason ?? "Site is unreachable";
			}
			return null;
		}

		$this->reportProgress($scan, 5, "Site blocked — retrying with browser rendering...");

		$fetchResult = $this->zyteFetcher->fetchPage($url);
		$this->insecureTransportUsed = $this->insecureTransportUsed || $fetchResult->insecureTransportUsed;

		if (!$fetchResult->successful || $fetchResult->content === null) {
			Log::warning("ScanOrchestrator Zyte fetch also failed", array(
				"url" => $url,
				"error" => $fetchResult->errorMessage,
			));
			if ($this->connectionError === null) {
				$this->blockReason = $this->blockReason ?? "Unable to fetch page";
			}
			return null;
		}

		/* Skip challenge marker checks for Zyte responses — browser rendering solves the
		   challenge, but Cloudflare JS artifacts (challenge-platform, cf-turnstile-response,
		   etc.) remain in the rendered DOM. Size + structure checks are sufficient here. */
		if (!$this->responseValidator->isRealHtmlPage($fetchResult->content, $fetchResult->httpStatusCode, skipChallengeMarkers: true)) {
			$this->blockReason = $this->responseValidator->getBlockReason($fetchResult->content, $fetchResult->httpStatusCode, skipChallengeMarkers: true)
				?? "Page blocked even with browser rendering";

			Log::warning("ScanOrchestrator page blocked even via Zyte", array(
				"url" => $url,
				"reason" => $this->blockReason,
				"content_length" => strlen($fetchResult->content),
			));
			return null;
		}

		$this->useZyteFallback = true;

		Log::info("ScanOrchestrator Zyte fallback succeeded", array(
			"url" => $url,
			"content_length" => strlen($fetchResult->content),
		));

		return $this->buildScanContext($fetchResult, $url, $targetKeywords);
	}

	/**
	 * Parse a successful FetchResult into a ScanContext.
	 */
	private function buildScanContext(FetchResult $fetchResult, string $url, array $targetKeywords): ScanContext
	{
		$parsed = $this->htmlParser->parseHtml($fetchResult->content);

		return new ScanContext(
			requestedUrl: $url,
			effectiveUrl: $fetchResult->effectiveUrl ?? $url,
			htmlContent: $fetchResult->content,
			domDocument: $parsed["domDocument"],
			xpath: $parsed["xpath"],
			responseHeaders: $fetchResult->headers,
			httpStatusCode: $fetchResult->httpStatusCode ?? 200,
			timeToFirstByte: $fetchResult->timeToFirstByte,
			totalTransferTime: $fetchResult->totalTransferTime,
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
			robotsTxtContent: null,
			isWordPress: false,
			targetKeywords: $targetKeywords,
		);
	}

	/**
	 * Phase 1: Run analyzers that produce shared state (robotsTxt, wpDetection).
	 */
	private function runPhaseOne(Scan $scan, ScanContext $scanContext): ScanContext
	{
		$robotsTxtContent = null;
		$isWordPress = false;
		$detectionMethod = null;
		$techStack = array();

		foreach ($this->moduleRegistry->phaseOneAnalyzers() as $analyzer) {
			$result = $this->executeAnalyzer($analyzer, $scanContext, $scan);

			if ($result !== null && $analyzer->moduleKey() === "robotsTxt") {
				$robotsTxtContent = $this->extractRobotsTxtContent($result);
			}

			if ($result !== null && $analyzer->moduleKey() === "wpDetection") {
				$isWordPress = $this->extractIsWordPress($result);
				$detectionMethod = $this->extractDetectionMethod($result);
				$techStack = $this->extractTechStack($result);
			}
		}

		return $scanContext->withPhaseOneResults($robotsTxtContent, $isWordPress, $detectionMethod, $techStack);
	}

	/**
	 * Phase 2: Run analyzers that depend on Phase 1 output (sitemapAnalysis).
	 */
	private function runPhaseTwo(Scan $scan, ScanContext $scanContext): void
	{
		foreach ($this->moduleRegistry->phaseTwoAnalyzers() as $analyzer) {
			$this->executeAnalyzer($analyzer, $scanContext, $scan);
		}
	}

	/**
	 * Phase 3: Crawl trust pages for E-E-A-T context (single-page scan only).
	 */
	private function runTrustPageCrawl(ScanContext $scanContext): ScanContext
	{
		try {
			$trustPages = $this->trustPageCrawler->crawl($scanContext);

			return $scanContext->withTrustPageResults($trustPages);
		} catch (\Throwable $exception) {
			Log::warning("TrustPageCrawler failed, continuing without trust page data", array(
				"error" => $exception->getMessage(),
			));

			return $scanContext;
		}
	}

	/**
	 * Phase 4: Run all remaining SEO, E-E-A-T, and Content analyzers.
	 */
	private function runStandardSeoAnalyzers(Scan $scan, ScanContext $scanContext): void
	{
		foreach ($this->moduleRegistry->standardSeoAnalyzers() as $analyzer) {
			$this->executeAnalyzer($analyzer, $scanContext, $scan);
		}
	}

	/**
	 * Phase 5: Run WordPress-specific analyzers (only if WP detected).
	 */
	private function runWordPressAnalyzers(Scan $scan, ScanContext $scanContext): void
	{
		foreach ($this->moduleRegistry->wordPressAnalyzers() as $analyzer) {
			$this->executeAnalyzer($analyzer, $scanContext, $scan);
		}
	}

	/**
	 * Run blacklist and malware check (site-wide — domain reputation applies to entire site).
	 */
	private function runBlacklistCheck(Scan $scan, ScanContext $scanContext): void
	{
		$blacklistAnalyzer = $this->moduleRegistry->getAnalyzer("blacklistCheck");

		if ($blacklistAnalyzer !== null) {
			$this->executeAnalyzer($blacklistAnalyzer, $scanContext, $scan);
		}
	}

	/**
	 * Run security header and exposed file checks (site-wide — server-level configuration).
	 */
	private function runSecurityChecks(Scan $scan, ScanContext $scanContext): void
	{
		$securityModuleKeys = array("securityHeaders", "exposedSensitiveFiles", "sslCertificate");

		foreach ($securityModuleKeys as $moduleKey) {
			$analyzer = $this->moduleRegistry->getAnalyzer($moduleKey);

			if ($analyzer !== null) {
				$this->executeAnalyzer($analyzer, $scanContext, $scan);
			}
		}
	}

	/**
	 * Run HTTPS redirect check (site-wide — domain-level HTTPS configuration).
	 */
	private function runHttpsRedirectCheck(Scan $scan, ScanContext $scanContext): void
	{
		$httpsRedirectAnalyzer = $this->moduleRegistry->getAnalyzer("httpsRedirect");

		if ($httpsRedirectAnalyzer !== null) {
			$this->executeAnalyzer($httpsRedirectAnalyzer, $scanContext, $scan);
		}
	}

	/**
	 * Run duplicate URL detection (site-wide — www/slash normalization is domain-level).
	 */
	private function runDuplicateUrlCheck(Scan $scan, ScanContext $scanContext): void
	{
		$duplicateUrlAnalyzer = $this->moduleRegistry->getAnalyzer("duplicateUrl");

		if ($duplicateUrlAnalyzer !== null) {
			$this->executeAnalyzer($duplicateUrlAnalyzer, $scanContext, $scan);
		}
	}

	/**
	 * Phase 6: Run utility analyzers (always last).
	 */
	private function runUtilityAnalyzers(Scan $scan, ScanContext $scanContext): void
	{
		foreach ($this->moduleRegistry->utilityAnalyzers() as $analyzer) {
			$this->executeAnalyzer($analyzer, $scanContext, $scan);
		}
	}

	/**
	 * Run a single analyzer with error isolation.
	 * Stores results with scan_page_id = NULL (site-wide context).
	 */
	private function executeAnalyzer(AnalyzerInterface $analyzer, ScanContext $scanContext, Scan $scan): ?AnalysisResult
	{
		try {
			$result = $analyzer->analyze($scanContext);

			ScanModuleResult::create(array(
				"scan_id" => $scan->id,
				"module_key" => $analyzer->moduleKey(),
				"status" => $result->status,
				"findings" => $result->findings,
				"recommendations" => $result->recommendations,
			));

			return $result;
		} catch (\Throwable $exception) {
			Log::error("Analyzer failed: {$analyzer->moduleKey()}", array(
				"scan_id" => $scan->id,
				"error" => $exception->getMessage(),
			));

			ScanModuleResult::create(array(
				"scan_id" => $scan->id,
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
	 * Extract internal links from the already-fetched homepage HTML and store
	 * them as DiscoveredPage records. This piggybacks on the homepage scan at
	 * zero extra cost — no additional HTTP requests are made.
	 *
	 * Clears previously unanalyzed discovered pages so a rescan refreshes the list.
	 * Already-analyzed pages are preserved.
	 */
	private function autoDiscoverLinks(Project $project, ScanContext $scanContext): void
	{
		try {
			$profile = new SameDomainHtmlProfile($scanContext->effectiveUrl);
			$homepageNormalized = UrlNormalizer::forComparison($scanContext->effectiveUrl);

			$discoveredLinks = LinkExtractor::extractSameDomainLinks(
				$scanContext->htmlContent,
				$scanContext->effectiveUrl,
				$profile,
			);

			/* Filter out the homepage itself */
			$newLinks = array();
			foreach ($discoveredLinks as $linkUrl) {
				$comparisonUrl = UrlNormalizer::forComparison($linkUrl);
				if ($comparisonUrl !== $homepageNormalized) {
					$newLinks[$comparisonUrl] = UrlNormalizer::forCrawl($linkUrl);
				}
			}

			/* Clear old unanalyzed discovered pages — rescan refreshes the list */
			$project->discoveredPages()
				->where("is_analyzed", false)
				->delete();

			/* Insert new discovered pages, skipping any that already exist (analyzed or not) */
			$existingUrls = $project->discoveredPages()
				->pluck("url")
				->mapWithKeys(fn(string $url) => array(UrlNormalizer::forComparison($url) => true))
				->toArray();

			$rowsToInsert = array();
			$now = now();

			foreach ($newLinks as $comparisonUrl => $normalizedUrl) {
				if (isset($existingUrls[$comparisonUrl])) {
					continue;
				}

				$rowsToInsert[] = array(
					"project_id" => $project->id,
					"url" => $normalizedUrl,
					"crawl_depth" => 1,
					"is_analyzed" => false,
					"created_at" => $now,
					"updated_at" => $now,
				);
			}

			if (!empty($rowsToInsert)) {
				DiscoveredPage::insert($rowsToInsert);
			}

			$insertedCount = count($rowsToInsert);

			$project->updateQuietly(array("discovery_status" => "completed"));

			Log::info("Auto-discovery completed during homepage scan", array(
				"project_id" => $project->id,
				"links_found" => count($newLinks),
				"pages_inserted" => $insertedCount,
			));
		} catch (\Throwable $exception) {
			Log::warning("Auto-discovery failed during homepage scan", array(
				"project_id" => $project->id,
				"error" => $exception->getMessage(),
			));
		}
	}

	/**
	 * Calculate score and finalize a single-page scan as completed.
	 */
	private function finalizeScan(Scan $scan, bool $isWordPress, ?string $detectionMethod, float $startTime, string $fetcherUsed = "guzzle"): void
	{
		$durationMs = (int) ((microtime(true) - $startTime) * 1000);
		$moduleResults = $scan->moduleResults()->get();
		$overallScore = $this->scoreCalculator->calculateScore($moduleResults, $isWordPress);
		$subScores = $this->scoreCalculator->calculateSubScores($moduleResults, $this->moduleRegistry, $isWordPress);

		$scan->update(array(
			"status" => ScanStatus::Completed,
			"overall_score" => $overallScore,
			"seo_score" => $subScores["seo"],
			"health_score" => $subScores["health"],
			"scan_duration_ms" => $durationMs,
			"is_wordpress" => $isWordPress,
			"detection_method" => $detectionMethod,
			"fetcher_used" => $fetcherUsed,
			"insecure_transport_used" => $this->insecureTransportUsed,
		));

		$this->updateCompetitorLatestScan($scan);

		if (!$scan->isCompetitorScan()) {
			try {
				$scan->triggeredBy?->notify(new ScanCompleteNotification($scan));
			} catch (\Throwable $exception) {
				Log::warning("Failed to send scan complete notification", array(
					"scan_id" => $scan->id,
					"error" => $exception->getMessage(),
				));
			}
		}
	}

	/**
	 * Mark a scan as failed with duration tracking.
	 * Optional error message stored in progress_label for user-facing display.
	 */
	private function markFailed(Scan $scan, float $startTime, ?string $errorMessage = null): void
	{
		$durationMs = (int) ((microtime(true) - $startTime) * 1000);
		$this->releaseScanCreditIfClaimed($scan);

		$updateData = array(
			"status" => ScanStatus::Failed,
			"scan_duration_ms" => $durationMs,
			"fetcher_used" => $this->resolveCurrentFetcher(),
			"insecure_transport_used" => $this->insecureTransportUsed,
		);

		if ($errorMessage !== null) {
			$updateData["progress_label"] = $errorMessage;
		}

		$scan->update($updateData);
	}

	/**
	 * Mark a scan as blocked by bot protection.
	 * The progress_label stores the human-readable block reason for display.
	 */
	private function markBlocked(Scan $scan, float $startTime, string $reason): void
	{
		$durationMs = (int) ((microtime(true) - $startTime) * 1000);
		$this->releaseScanCreditIfClaimed($scan);

		$scan->update(array(
			"status" => ScanStatus::Blocked,
			"scan_duration_ms" => $durationMs,
			"progress_label" => $reason,
			"fetcher_used" => $this->resolveCurrentFetcher(),
			"insecure_transport_used" => $this->insecureTransportUsed,
		));
	}

	/**
	 * Route a null ScanContext to the correct terminal status.
	 * Bot protection blocks → Blocked status. Connection errors (SSL, DNS, timeout) → Failed.
	 */
	private function handleFetchFailure(Scan $scan, float $startTime): void
	{
		if ($this->blockReason !== null) {
			$this->markBlocked($scan, $startTime, $this->blockReason);
			return;
		}

		if ($this->connectionError !== null) {
			$friendlyMessage = $this->classifyConnectionError($this->connectionError);
			$this->markFailed($scan, $startTime, $friendlyMessage);
			return;
		}

		$this->markFailed($scan, $startTime);
	}

	/**
	 * Convert raw connection error messages (cURL errors, etc.) into user-friendly descriptions.
	 */
	private function classifyConnectionError(string $rawError): string
	{
		$rawLower = strtolower($rawError);

		if (str_contains($rawLower, "ssl") || str_contains($rawLower, "certificate")) {
			return "SSL certificate error — the site's security certificate is invalid or expired. The site owner needs to renew or fix their SSL certificate.";
		}

		if (str_contains($rawLower, "could not resolve host") || str_contains($rawLower, "name or service not known")) {
			return "Domain not found — the hostname could not be resolved. Verify the URL is correct and the domain's DNS is properly configured.";
		}

		if (str_contains($rawLower, "timed out") || str_contains($rawLower, "timeout")) {
			return "Connection timed out — the site's server did not respond within the allowed time. The site may be down or experiencing high load.";
		}

		if (str_contains($rawLower, "connection refused")) {
			return "Connection refused — the site's server actively rejected the connection. The web server may be down or misconfigured.";
		}

		if (str_contains($rawLower, "empty body") || str_contains($rawLower, "empty response")) {
			return "The site returned an empty response. The web server may be misconfigured or experiencing issues.";
		}

		return "Unable to connect to the website — {$rawError}";
	}

	/**
	 * Validate crawl results: check if collected pages are real HTML or bot-protection
	 * challenge pages. SiteGround often lets the first request through (homepage) but
	 * blocks subsequent requests — the crawler ends up with 1 page of challenge HTML.
	 *
	 * If challenge pages are detected and Zyte is available, deletes the garbage pages
	 * and re-crawls the entire site through Zyte browser rendering.
	 */
	private function validateCrawlResults(
		Scan $scan,
		ScanPageCollector $collector,
		string $entryUrl,
		int $maxPages,
		int $maxDepth,
		string &$fetcherUsed,
	): ScanPageCollector {
		$collectedPages = $collector->getCollectedPages();
		$totalAnalyzable = count($collectedPages);
		$totalRaw = $collector->getPagesCollected();

		/*
		 * Crawl looks healthy if we got multiple analyzable pages.
		 * Skip validation — no bot protection issue.
		 */
		if ($totalAnalyzable > 1 && $maxPages > 1) {
			return $collector;
		}

		/*
		 * Suspicious crawl: 0 analyzable pages (all failed/errored) or only 1 page
		 * when we expected many. Check if the analyzable pages are challenge HTML,
		 * or if we got 0 analyzable pages from a crawl that did collect raw pages
		 * (meaning every page errored — likely bot protection blocking with HTTP errors).
		 */
		$isCrawlBlocked = false;
		$blockReason = null;

		if ($totalAnalyzable === 0 && $totalRaw > 0) {
			/* All pages errored — crawler was blocked at the HTTP level */
			$isCrawlBlocked = true;
			$blockReason = "Crawler blocked — all {$totalRaw} pages returned errors";
		} else {
			/* Check if the few analyzable pages are challenge/captcha HTML */
			$blockedCount = 0;

			foreach ($collectedPages as $pageData) {
				if (!$this->responseValidator->isRealHtmlPage($pageData["html"])) {
					$blockedCount++;
				}
			}

			if ($blockedCount > 0) {
				$isCrawlBlocked = true;
				$blockReason = $this->responseValidator->getBlockReason($collectedPages[0]["html"]);
			}
		}

		if (!$isCrawlBlocked) {
			return $collector;
		}

		Log::warning("ScanOrchestrator crawl blocked, attempting Zyte fallback", array(
			"scan_id" => $scan->id,
			"analyzable_pages" => $totalAnalyzable,
			"raw_pages" => $totalRaw,
			"reason" => $blockReason,
		));

		if (!$this->zyteFetcher->isAvailable()) {
			Log::info("ScanOrchestrator Zyte not available for crawler fallback", array(
				"scan_id" => $scan->id,
			));
			return $collector;
		}

		/* Delete the garbage ScanPage records from the failed Guzzle crawl */
		$scan->pages()->delete();
		$collector->releaseHtml();

		$this->reportProgress($scan, 30, "Crawler blocked — re-crawling via browser rendering...");

		$this->useZyteFallback = true;
		$fetcherUsed = "zyte";

		return $this->crawlViaZyte($scan, $entryUrl, $maxPages, $maxDepth);
	}

	/**
	 * Execute a crawl using the Zyte browser rendering API.
	 */
	private function crawlViaZyte(Scan $scan, string $entryUrl, int $maxPages, int $maxDepth): ScanPageCollector
	{
		$this->reportProgress($scan, 28, "Crawling site via browser rendering...");

		return $this->zyteCrawlerService->crawl(
			$scan, $entryUrl, $maxPages, $maxDepth,
			fn(int $collected, int $total) => $this->reportProgress(
				$scan,
				28 + (int) (17 * $collected / max($total, 1)),
				"Crawling via browser rendering — {$collected} of {$total} pages...",
			),
		);
	}

	/**
	 * Determine which fetcher is active for this scan based on the Zyte fallback flag.
	 */
	private function resolveCurrentFetcher(): string
	{
		return $this->useZyteFallback ? "zyte" : "guzzle";
	}

	/**
	 * Resolve the target URL for a scan.
	 * Competitor scans analyze the competitor's URL, not the project's URL.
	 */
	private function resolveTargetUrl(Scan $scan): string
	{
		if ($scan->isCompetitorScan()) {
			return $scan->competitor->url;
		}

		return $scan->project->url;
	}

	/**
	 * Update the competitor's latest_scan_id pointer after a successful scan.
	 */
	private function updateCompetitorLatestScan(Scan $scan): void
	{
		if (!$scan->isCompetitorScan()) {
			return;
		}

		try {
			$scan->competitor->updateQuietly(array("latest_scan_id" => $scan->id));
		} catch (\Throwable $exception) {
			Log::warning("Failed to update competitor latest_scan_id", array(
				"scan_id" => $scan->id,
				"competitor_id" => $scan->competitor_id,
				"error" => $exception->getMessage(),
			));
		}
	}

	private function releaseScanCreditIfClaimed(Scan $scan): void
	{
		$this->billingService->releaseCreditForModel($scan);
	}

	/**
	 * Write scan progress to the database so the frontend can poll for updates.
	 * Uses updateQuietly() to avoid triggering model events on every step.
	 */
	private function reportProgress(Scan $scan, int $percent, string $label): void
	{
		$scan->updateQuietly(array(
			"progress_percent" => $percent,
			"progress_label" => $label,
		));
	}

	/* ──────────────────────────────────────────────────────────────────────
	 * Finding Extraction Helpers
	 * ──────────────────────────────────────────────────────────────────── */

	private function extractRobotsTxtContent(AnalysisResult $result): ?string
	{
		foreach ($result->findings as $finding) {
			if (($finding["key"] ?? null) === "robotsTxtContent") {
				return $finding["value"] ?? null;
			}
		}

		return null;
	}

	private function extractIsWordPress(AnalysisResult $result): bool
	{
		foreach ($result->findings as $finding) {
			if (($finding["key"] ?? null) === "isWordPress") {
				return (bool) ($finding["value"] ?? false);
			}
		}

		return false;
	}

	private function extractDetectionMethod(AnalysisResult $result): ?string
	{
		foreach ($result->findings as $finding) {
			if (($finding["key"] ?? null) === "detectionMethod") {
				return $finding["value"] ?? null;
			}
		}

		return null;
	}

	/**
	 * Extract the full technology stack array from the wpDetection result.
	 */
	private function extractTechStack(AnalysisResult $result): array
	{
		foreach ($result->findings as $finding) {
			if (($finding["key"] ?? null) === "techStack") {
				return is_array($finding["value"] ?? null) ? $finding["value"] : array();
			}
		}

		return array();
	}

	/**
	 * Run Core Web Vitals (Mobile) via Google PageSpeed Insights API (site-wide).
	 * Also captures the homepage screenshot from the API response.
	 */
	private function runCoreWebVitalsMobile(Scan $scan, ScanContext $scanContext): void
	{
		$analyzer = $this->moduleRegistry->getAnalyzer("coreWebVitalsMobile");

		if ($analyzer !== null) {
			$this->executeAnalyzer($analyzer, $scanContext, $scan);
		}
	}

	/**
	 * Run Core Web Vitals (Desktop) via Google PageSpeed Insights API (site-wide).
	 * Also captures the homepage screenshot (desktop viewport).
	 */
	private function runCoreWebVitalsDesktop(Scan $scan, ScanContext $scanContext): ?string
	{
		$analyzer = $this->moduleRegistry->getAnalyzer("coreWebVitalsDesktop");

		if ($analyzer === null) {
			return null;
		}

		$this->executeAnalyzer($analyzer, $scanContext, $scan);

		if ($analyzer instanceof CoreWebVitalsBaseAnalyzer) {
			return $analyzer->getCapturedScreenshot();
		}

		return null;
	}

	/**
	 * Save the homepage screenshot to storage.
	 * Primary source: PageSpeed Insights API (piggybacked on CWV desktop call).
	 * Fallback: Zyte API screenshot endpoint (costs $0.002/request).
	 */
	private function captureHomepageScreenshot(Scan $scan, string $url, ?string $screenshotBase64): void
	{
		if ($screenshotBase64 === null && $this->zyteFetcher->isAvailable()) {
			Log::debug("ScanOrchestrator PageSpeed screenshot unavailable, trying Zyte fallback", array(
				"scan_id" => $scan->id,
			));
			$screenshotBase64 = $this->zyteFetcher->fetchScreenshot($url);
		}

		if ($screenshotBase64 === null) {
			return;
		}

		try {
			$imageData = base64_decode($screenshotBase64, true);
			if ($imageData === false || strlen($imageData) < 100) {
				Log::warning("ScanOrchestrator screenshot base64 decode failed", array("scan_id" => $scan->id));
				return;
			}

			$relativePath = "screenshots/{$scan->id}.jpg";
			Storage::disk("public")->put($relativePath, $imageData);

			$scan->updateQuietly(array("homepage_screenshot_path" => $relativePath));

			Log::debug("ScanOrchestrator homepage screenshot saved", array(
				"scan_id" => $scan->id,
				"path" => $relativePath,
				"size_bytes" => strlen($imageData),
			));
		} catch (\Throwable $exception) {
			Log::warning("ScanOrchestrator screenshot save failed", array(
				"scan_id" => $scan->id,
				"error" => $exception->getMessage(),
			));
		}
	}

	/**
	 * Run technology stack detection (site-wide — full tech inventory).
	 */
	private function runTechStackDetection(Scan $scan, ScanContext $scanContext): void
	{
		$techStackAnalyzer = $this->moduleRegistry->getAnalyzer("techStackDetection");

		if ($techStackAnalyzer !== null) {
			$this->executeAnalyzer($techStackAnalyzer, $scanContext, $scan);
		}
	}

	/**
	 * Run analytics & tracking detection (site-wide — tracking code is the same on every page).
	 */
	private function runAnalyticsDetection(Scan $scan, ScanContext $scanContext): void
	{
		$analyticsAnalyzer = $this->moduleRegistry->getAnalyzer("analyticsDetection");

		if ($analyticsAnalyzer !== null) {
			$this->executeAnalyzer($analyticsAnalyzer, $scanContext, $scan);
		}
	}
}
