<?php

namespace App\Services\Crawling;

use App\Models\Scan;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\Crawler;

/**
 * High-level service that configures and executes the Spatie crawler
 * to discover pages on a target site for SEO analysis.
 */
class SiteCrawlerService
{
	/**
	 * Crawl the target site and collect pages for analysis.
	 *
	 * Returns the collector containing ScanPage records and HTML content.
	 */
	public function crawl(Scan $scan, string $entryUrl, int $maxPages, int $maxDepth): ScanPageCollector
	{
		$collector = new ScanPageCollector($scan, $entryUrl, $maxPages);
		$profile = new SameDomainHtmlProfile($entryUrl);

		$concurrency = (int) config("scanning.crawl.concurrency", 3);
		$delayMs = (int) config("scanning.crawl.delay_ms", 200);
		$userAgent = config("scanning.user_agent");
		$pageTimeout = (int) config("scanning.page_timeout", 15);

		Log::info("Starting site crawl", array(
			"scan_id" => $scan->id,
			"entry_url" => $entryUrl,
			"max_pages" => $maxPages,
			"max_depth" => $maxDepth,
			"concurrency" => $concurrency,
		));

		try {
			$handlerStack = $this->buildHandlerStack();

			$crawler = Crawler::create(array(
				"handler" => $handlerStack,
				RequestOptions::COOKIES => true,
				RequestOptions::CONNECT_TIMEOUT => 10,
				RequestOptions::TIMEOUT => $pageTimeout,
				RequestOptions::ALLOW_REDIRECTS => array(
					"max" => 5,
					"track_redirects" => true,
				),
				RequestOptions::HEADERS => array(
					"User-Agent" => $userAgent,
					"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
					"Accept-Language" => "en-US,en;q=0.9",
					"Accept-Encoding" => "gzip, deflate, br",
					"Cache-Control" => "no-cache",
					"Sec-Fetch-Dest" => "document",
					"Sec-Fetch-Mode" => "navigate",
					"Sec-Fetch-Site" => "none",
					"Sec-Fetch-User" => "?1",
					"Upgrade-Insecure-Requests" => "1",
				),
				RequestOptions::VERIFY => config("scanning.crawl.verify_ssl", true),
			));

			$crawler
				->setCrawlObserver($collector)
				->setCrawlProfile($profile)
				->setMaximumDepth($maxDepth)
				->setTotalCrawlLimit($maxPages)
				->setConcurrency($concurrency)
				->setDelayBetweenRequests($delayMs)
				->setMaximumResponseSize(5 * 1024 * 1024)
				->setParseableMimeTypes(array("text/html", "text/plain"))
				->ignoreRobots()
				->acceptNofollowLinks();

			$crawler->startCrawling($entryUrl);
		} catch (\Throwable $exception) {
			Log::error("Site crawl failed", array(
				"scan_id" => $scan->id,
				"entry_url" => $entryUrl,
				"error" => $exception->getMessage(),
			));

			$collector->finishedCrawling();
		}

		return $collector;
	}

	/**
	 * Build a Guzzle handler stack with retry middleware for HTTP 202 responses.
	 *
	 * CDNs like Cloudflare return 202 with an empty challenge page on the first
	 * request, then serve real content on retry. Without this, the crawler sees
	 * no links on the homepage and stops at 1 page.
	 */
	private function buildHandlerStack(): HandlerStack
	{
		$stack = HandlerStack::create();

		if (!config("scanning.crawl.retry_on_202", true)) {
			return $stack;
		}

		$maxRetries = (int) config("scanning.crawl.retry_max_attempts", 3);
		$retryDelayMs = (int) config("scanning.crawl.retry_delay_ms", 1500);

		$decider = function (
			int $retries,
			RequestInterface $request,
			?ResponseInterface $response = null,
		) use ($maxRetries): bool {
			if ($retries >= $maxRetries) {
				return false;
			}

			if ($response === null) {
				return false;
			}

			$statusCode = $response->getStatusCode();

			if ($statusCode === 202) {
				Log::debug("HTTP 202 received, retrying", array(
					"url" => (string) $request->getUri(),
					"attempt" => $retries + 1,
					"max_retries" => $maxRetries,
				));

				return true;
			}

			return false;
		};

		$delay = function (int $retries) use ($retryDelayMs): int {
			return $retryDelayMs * (int) pow(2, $retries - 1);
		};

		$stack->push(Middleware::retry($decider, $delay), "retry_on_202");

		return $stack;
	}
}
