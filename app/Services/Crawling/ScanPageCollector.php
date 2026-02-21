<?php

namespace App\Services\Crawling;

use App\Models\Scan;
use App\Models\ScanPage;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use App\Services\Utils\UrlNormalizer;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

/**
 * Collects crawled pages into ScanPage records and stores HTML content
 * temporarily in memory for the analysis phase.
 */
class ScanPageCollector extends CrawlObserver
{
	/** @var array<int, string> ScanPage ID => HTML body */
	private array $collectedHtml = array();

	/** @var array<string, true> Normalized URLs already collected for this scan */
	private array $seenUrls = array();

	private int $pagesCollected = 0;

	public function __construct(
		private readonly Scan $scan,
		private readonly string $entryUrl,
		private readonly int $maxPages,
	) {}

	public function crawled(
		UriInterface $url,
		ResponseInterface $response,
		?UriInterface $foundOnUrl = null,
		?string $linkText = null,
	): void {
		if ($this->pagesCollected >= $this->maxPages) {
			return;
		}

		$normalizedUrl = $this->normalizeUrl((string) $url);

		if (isset($this->seenUrls[$normalizedUrl])) {
			return;
		}

		$statusCode = $response->getStatusCode();
		$contentType = $this->extractContentType($response);

		$htmlBody = (string) $response->getBody();
		$isHomepage = $this->isHomepageUrl($normalizedUrl);
		$crawlDepth = $this->estimateCrawlDepth($url, $foundOnUrl);
		$startTime = microtime(true);

		$scanPage = ScanPage::create(array(
			"project_id" => $this->scan->project_id,
			"scan_id" => $this->scan->id,
			"url" => $normalizedUrl,
			"http_status_code" => $statusCode,
			"content_type" => $contentType,
			"is_homepage" => $isHomepage,
			"crawl_depth" => $crawlDepth,
			"scan_duration_ms" => (int) ((microtime(true) - $startTime) * 1000),
		));

		$this->seenUrls[$normalizedUrl] = true;

		$this->collectedHtml[$scanPage->id] = $htmlBody;
		$this->pagesCollected++;

		Log::debug("Crawled page", array(
			"scan_id" => $this->scan->id,
			"url" => (string) $url,
			"status" => $statusCode,
			"page_number" => $this->pagesCollected,
		));
	}

	public function crawlFailed(
		UriInterface $url,
		RequestException $requestException,
		?UriInterface $foundOnUrl = null,
		?string $linkText = null,
	): void {
		if ($this->pagesCollected >= $this->maxPages) {
			return;
		}

		$normalizedUrl = $this->normalizeUrl((string) $url);

		if (isset($this->seenUrls[$normalizedUrl])) {
			return;
		}

		$statusCode = null;
		if ($requestException->hasResponse()) {
			$statusCode = $requestException->getResponse()->getStatusCode();
		}

		ScanPage::create(array(
			"project_id" => $this->scan->project_id,
			"scan_id" => $this->scan->id,
			"url" => $normalizedUrl,
			"http_status_code" => $statusCode,
			"is_homepage" => $this->isHomepageUrl($normalizedUrl),
			"crawl_depth" => $this->estimateCrawlDepth($url, $foundOnUrl),
			"error_message" => mb_substr($requestException->getMessage(), 0, 500),
		));

		$this->seenUrls[$normalizedUrl] = true;

		$this->pagesCollected++;

		Log::warning("Crawl failed for page", array(
			"scan_id" => $this->scan->id,
			"url" => (string) $url,
			"error" => $requestException->getMessage(),
		));
	}

	public function finishedCrawling(): void
	{
		$this->scan->update(array(
			"pages_crawled" => $this->pagesCollected,
		));

		Log::info("Crawl finished", array(
			"scan_id" => $this->scan->id,
			"pages_collected" => $this->pagesCollected,
		));
	}

	/**
	 * Returns collected pages with their HTML content for analysis.
	 *
	 * @return array<int, array{scanPage: ScanPage, html: string}>
	 */
	public function getCollectedPages(): array
	{
		$pages = array();

		foreach ($this->collectedHtml as $scanPageId => $html) {
			$scanPage = ScanPage::find($scanPageId);

			if ($scanPage === null || $scanPage->error_message !== null) {
				continue;
			}

			$pages[] = array(
				"scanPage" => $scanPage,
				"html" => $html,
			);
		}

		return $pages;
	}

	/**
	 * Inject a pre-fetched page directly into the collector.
	 * Used by ZyteCrawlerService which fetches pages externally
	 * rather than through spatie/crawler's CrawlObserver interface.
	 */
	public function injectPage(ScanPage $scanPage, string $html): void
	{
		$this->collectedHtml[$scanPage->id] = $html;
		$this->seenUrls[UrlNormalizer::forCrawl($scanPage->url)] = true;
	}

	/**
	 * Release HTML from memory after analysis is complete.
	 */
	public function releaseHtml(): void
	{
		$this->collectedHtml = array();
	}

	public function getPagesCollected(): int
	{
		return $this->pagesCollected;
	}

	private function extractContentType(ResponseInterface $response): ?string
	{
		$header = $response->getHeaderLine("Content-Type");

		if ($header === "") {
			return null;
		}

		$parts = explode(";", $header);

		return trim($parts[0]);
	}

	/**
	 * Check if the URL is the homepage (matches the entry URL's path).
	 * Expects a pre-normalized URL.
	 */
	private function isHomepageUrl(string $normalizedUrl): bool
	{
		$entryPath = rtrim(parse_url($this->entryUrl, PHP_URL_PATH) ?? "/", "/");
		$urlPath = rtrim(parse_url($normalizedUrl, PHP_URL_PATH) ?? "/", "/");

		return $entryPath === $urlPath || ($entryPath === "" && $urlPath === "");
	}

	/**
	 * Determine actual crawl depth by looking up the parent page's depth.
	 * If foundOnUrl is null (entry URL), depth is 0.
	 * Otherwise, parent depth + 1.
	 */
	private function estimateCrawlDepth(UriInterface $url, ?UriInterface $foundOnUrl): int
	{
		if ($foundOnUrl === null) {
			return 0;
		}

		$normalizedParentUrl = $this->normalizeUrl((string) $foundOnUrl);

		$parentPage = ScanPage::where("scan_id", $this->scan->id)
			->where("url", $normalizedParentUrl)
			->first();

		if ($parentPage === null) {
			return 1;
		}

		return $parentPage->crawl_depth + 1;
	}

	/**
	 * Normalize a URL to prevent duplicates caused by trailing slashes,
	 * fragment identifiers, or scheme/host casing differences.
	 */
	private function normalizeUrl(string $url): string
	{
		return UrlNormalizer::forCrawl($url);
	}
}
