<?php

namespace App\Services\Crawling;

use App\Models\Scan;
use App\Models\ScanPage;
use App\Services\Scanning\ResponseValidator;
use App\Services\Scanning\ZyteFetcher;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Link-following crawler that fetches pages through the Zyte API.
 * Used as a fallback when the normal spatie/crawler (Guzzle-based)
 * is blocked by bot protection on the target site.
 *
 * Produces the same ScanPageCollector output as SiteCrawlerService
 * so the downstream analysis pipeline works identically.
 */
class ZyteCrawlerService
{
	public function __construct(
		private readonly ZyteFetcher $zyteFetcher,
		private readonly ResponseValidator $responseValidator,
	) {}

	/**
	 * Crawl the target site via Zyte API and collect pages for analysis.
	 *
	 * @param callable|null $onProgress Optional callback: fn(int $percent, string $label)
	 */
	public function crawl(
		Scan $scan,
		string $entryUrl,
		int $maxPages,
		int $maxDepth,
		?callable $onProgress = null,
	): ScanPageCollector {
		$collector = new ScanPageCollector($scan, $entryUrl, $maxPages);
		$profile = new SameDomainHtmlProfile($entryUrl);

		$delayMs = (int) config("scanning.crawl.delay_ms", 200);

		/** @var array<string, true> URLs already seen or queued */
		$seenUrls = array();

		/** @var array<array{url: string, depth: int, foundOn: ?string}> BFS queue */
		$queue = array();

		$normalizedEntry = UrlNormalizer::forCrawl($entryUrl);
		$seenUrls[$normalizedEntry] = true;
		$queue[] = array("url" => $entryUrl, "depth" => 0, "foundOn" => null);

		$pagesCollected = 0;

		Log::info("ZyteCrawlerService starting", array(
			"scan_id" => $scan->id,
			"entry_url" => $entryUrl,
			"max_pages" => $maxPages,
			"max_depth" => $maxDepth,
		));

		while (!empty($queue) && $pagesCollected < $maxPages) {
			$current = array_shift($queue);
			$currentUrl = $current["url"];
			$currentDepth = $current["depth"];

			$fetchResult = $this->zyteFetcher->fetchPage($currentUrl);

			if (!$fetchResult->successful || empty($fetchResult->content)) {
				Log::warning("ZyteCrawlerService fetch failed", array(
					"scan_id" => $scan->id,
					"url" => $currentUrl,
					"error" => $fetchResult->errorMessage,
				));

				$this->createFailedScanPage($scan, $currentUrl, $currentDepth, $current["foundOn"], $entryUrl, $fetchResult->errorMessage);
				$pagesCollected++;

				usleep($delayMs * 1000);
				continue;
			}

			if (!$this->responseValidator->isRealHtmlPage($fetchResult->content, skipChallengeMarkers: true)) {
				$blockReason = $this->responseValidator->getBlockReason($fetchResult->content, skipChallengeMarkers: true);

				Log::warning("ZyteCrawlerService page blocked even via Zyte", array(
					"scan_id" => $scan->id,
					"url" => $currentUrl,
					"reason" => $blockReason,
				));

				$this->createFailedScanPage($scan, $currentUrl, $currentDepth, $current["foundOn"], $entryUrl, $blockReason);
				$pagesCollected++;

				usleep($delayMs * 1000);
				continue;
			}

			$normalizedUrl = UrlNormalizer::forCrawl($currentUrl);
			$isHomepage = $this->isHomepageUrl($normalizedUrl, $entryUrl);

			$scanPage = ScanPage::create(array(
				"project_id" => $scan->project_id,
				"scan_id" => $scan->id,
				"url" => $normalizedUrl,
				"http_status_code" => $fetchResult->httpStatusCode ?? 200,
				"content_type" => "text/html",
				"is_homepage" => $isHomepage,
				"crawl_depth" => $currentDepth,
			));

			$collector->injectPage($scanPage, $fetchResult->content);
			$pagesCollected++;

			Log::debug("ZyteCrawlerService page collected", array(
				"scan_id" => $scan->id,
				"url" => $currentUrl,
				"page_number" => $pagesCollected,
				"depth" => $currentDepth,
			));

			if ($onProgress !== null) {
				$onProgress($pagesCollected, $maxPages);
			}

			if ($currentDepth < $maxDepth) {
				$discoveredLinks = LinkExtractor::extractSameDomainLinks($fetchResult->content, $currentUrl, $profile);

				foreach ($discoveredLinks as $discoveredUrl) {
					$normalizedDiscovered = UrlNormalizer::forCrawl($discoveredUrl);

					if (isset($seenUrls[$normalizedDiscovered])) {
						continue;
					}

					$seenUrls[$normalizedDiscovered] = true;
					$queue[] = array(
						"url" => $discoveredUrl,
						"depth" => $currentDepth + 1,
						"foundOn" => $currentUrl,
					);
				}
			}

			usleep($delayMs * 1000);
		}

		$scan->update(array("pages_crawled" => $pagesCollected));

		Log::info("ZyteCrawlerService finished", array(
			"scan_id" => $scan->id,
			"pages_collected" => $pagesCollected,
			"queue_remaining" => count($queue),
		));

		return $collector;
	}

	private function isHomepageUrl(string $normalizedUrl, string $entryUrl): bool
	{
		$entryPath = rtrim(parse_url($entryUrl, PHP_URL_PATH) ?? "/", "/");
		$urlPath = rtrim(parse_url($normalizedUrl, PHP_URL_PATH) ?? "/", "/");

		return $entryPath === $urlPath || ($entryPath === "" && $urlPath === "");
	}

	private function createFailedScanPage(Scan $scan, string $url, int $depth, ?string $foundOnUrl, string $entryUrl, ?string $errorMessage): void
	{
		$normalizedUrl = UrlNormalizer::forCrawl($url);

		ScanPage::create(array(
			"project_id" => $scan->project_id,
			"scan_id" => $scan->id,
			"url" => $normalizedUrl,
			"is_homepage" => $this->isHomepageUrl($normalizedUrl, $entryUrl),
			"crawl_depth" => $depth,
			"error_message" => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
		));
	}
}
