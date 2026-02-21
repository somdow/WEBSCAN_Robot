<?php

namespace App\Jobs;

use App\Models\DiscoveredPage;
use App\Models\Organization;
use App\Models\Project;
use App\Services\BillingService;
use App\Services\Crawling\DiscoveryOnlyCollector;
use App\Services\Crawling\LinkExtractor;
use App\Services\Crawling\SameDomainHtmlProfile;
use App\Services\Scanning\ZyteFetcher;
use App\Services\Utils\UrlNormalizer;
use GuzzleHttp\RequestOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Crawler\Crawler;

/**
 * Lightweight URL discovery job. Crawls the project's site to find page URLs
 * without analyzing them. Results stored in discovered_pages table at project level.
 *
 * Falls back to Zyte API when Spatie/Guzzle crawl discovers zero pages
 * (typically caused by bot protection blocking direct HTTP requests).
 */
class DiscoverPagesJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $tries = 1;

	public int $timeout = 270;

	public function __construct(
		public readonly Project $project,
		public readonly int $maxPages,
		public readonly ?Organization $organization = null,
	) {}

	public function retryUntil(): \DateTime
	{
		return now()->addMinutes(5);
	}

	public function handle(ZyteFetcher $zyteFetcher): void
	{
		$entryUrl = $this->project->url;

		Log::info("Starting page discovery", array(
			"project_id" => $this->project->id,
			"entry_url" => $entryUrl,
			"max_pages" => $this->maxPages,
		));

		try {
			$pagesDiscovered = $this->discoverViaSpatie($entryUrl);

			if ($pagesDiscovered === 0 && $zyteFetcher->isAvailable()) {
				Log::info("Spatie discovery found 0 pages, falling back to Zyte", array(
					"project_id" => $this->project->id,
				));

				$pagesDiscovered = $this->discoverViaZyte($entryUrl, $zyteFetcher);
			}

			Log::info("Page discovery completed", array(
				"project_id" => $this->project->id,
				"pages_discovered" => $pagesDiscovered,
			));

			$this->project->updateQuietly(array("discovery_status" => "completed"));
		} catch (\Throwable $exception) {
			Log::error("Page discovery failed", array(
				"project_id" => $this->project->id,
				"error" => $exception->getMessage(),
			));

			$this->project->updateQuietly(array("discovery_status" => "failed"));
			$this->releaseScanCredit();
		}
	}

	/**
	 * Standard discovery via Spatie Crawler (Guzzle-based HTTP).
	 * Returns the number of pages discovered.
	 */
	private function discoverViaSpatie(string $entryUrl): int
	{
		$collector = new DiscoveryOnlyCollector($this->project, $entryUrl, $this->maxPages);
		$profile = new SameDomainHtmlProfile($entryUrl);

		$concurrency = (int) config("scanning.crawl.concurrency", 3);
		$delayMs = (int) config("scanning.crawl.delay_ms", 200);
		$userAgent = config("scanning.user_agent");
		$maxDepth = (int) config("scanning.crawl.default_max_depth", 3);

		$crawler = Crawler::create(array(
			RequestOptions::COOKIES => true,
			RequestOptions::CONNECT_TIMEOUT => 10,
			RequestOptions::TIMEOUT => 15,
			RequestOptions::ALLOW_REDIRECTS => array("max" => 5),
			RequestOptions::HEADERS => array("User-Agent" => $userAgent),
			RequestOptions::VERIFY => config("scanning.crawl.verify_ssl", true),
		));

		$crawler
			->setCrawlObserver($collector)
			->setCrawlProfile($profile)
			->setMaximumDepth($maxDepth)
			->setTotalCrawlLimit($this->maxPages + 1)
			->setConcurrency($concurrency)
			->setDelayBetweenRequests($delayMs)
			->setMaximumResponseSize(1 * 1024 * 1024)
			->setParseableMimeTypes(array("text/html", "text/plain"))
			->ignoreRobots()
			->acceptNofollowLinks();

		$crawler->startCrawling($entryUrl);

		return $this->project->discoveredPages()->count();
	}

	/**
	 * Fallback discovery via Zyte API (browser rendering).
	 * Fetches the homepage, extracts same-domain links, and saves as DiscoveredPage records.
	 * Returns the number of pages discovered.
	 */
	private function discoverViaZyte(string $entryUrl, ZyteFetcher $zyteFetcher): int
	{
		$profile = new SameDomainHtmlProfile($entryUrl);
		$homepageNormalized = UrlNormalizer::forComparison($entryUrl);
		$seenUrls = array($homepageNormalized => true);
		$pagesDiscovered = 0;

		$delayMs = (int) config("scanning.crawl.delay_ms", 200);

		/** @var array<array{url: string, depth: int}> BFS queue */
		$queue = array(array("url" => $entryUrl, "depth" => 0));

		while (!empty($queue) && $pagesDiscovered < $this->maxPages) {
			$current = array_shift($queue);
			$currentUrl = $current["url"];
			$currentDepth = $current["depth"];

			$fetchResult = $zyteFetcher->fetchPage($currentUrl);

			if (!$fetchResult->successful || empty($fetchResult->content)) {
				Log::debug("Zyte discovery fetch failed", array(
					"project_id" => $this->project->id,
					"url" => $currentUrl,
					"error" => $fetchResult->errorMessage,
				));
				usleep($delayMs * 1000);
				continue;
			}

			$effectiveUrl = $fetchResult->effectiveUrl ?? $currentUrl;

			$discoveredLinks = LinkExtractor::extractSameDomainLinks($fetchResult->content, $effectiveUrl, $profile);

			foreach ($discoveredLinks as $linkUrl) {
				if ($pagesDiscovered >= $this->maxPages) {
					break;
				}

				$normalizedUrl = UrlNormalizer::forCrawl($linkUrl);
				$comparisonUrl = UrlNormalizer::forComparison($linkUrl);

				if (isset($seenUrls[$comparisonUrl])) {
					continue;
				}

				$seenUrls[$comparisonUrl] = true;

				$depth = $this->estimateDepth($normalizedUrl);

				DiscoveredPage::create(array(
					"project_id" => $this->project->id,
					"url" => $normalizedUrl,
					"crawl_depth" => $depth,
				));

				$pagesDiscovered++;

				if ($currentDepth < 2) {
					$queue[] = array("url" => $normalizedUrl, "depth" => $currentDepth + 1);
				}
			}

			usleep($delayMs * 1000);
		}

		Log::info("Zyte discovery completed", array(
			"project_id" => $this->project->id,
			"pages_discovered" => $pagesDiscovered,
		));

		return $pagesDiscovered;
	}

	/**
	 * Estimate crawl depth from URL path segments.
	 */
	private function estimateDepth(string $url): int
	{
		$path = parse_url($url, PHP_URL_PATH) ?? "/";
		$segments = array_filter(explode("/", trim($path, "/")));

		return count($segments);
	}

	public function failed(\Throwable $exception): void
	{
		Log::error("DiscoverPagesJob permanently failed", array(
			"project_id" => $this->project->id,
			"exception" => $exception->getMessage(),
		));

		$this->project->updateQuietly(array("discovery_status" => "failed"));
		$this->releaseScanCredit();
	}

	/**
	 * Release the scan credit claimed for this discovery job.
	 * Safe to call even if no organization was provided (backward compat).
	 */
	private function releaseScanCredit(): void
	{
		if ($this->organization === null) {
			return;
		}

		try {
			app(BillingService::class)->releaseScanCredit($this->organization);
		} catch (\Throwable $exception) {
			Log::warning("Failed to release discovery scan credit", array(
				"project_id" => $this->project->id,
				"error" => $exception->getMessage(),
			));
		}
	}
}
