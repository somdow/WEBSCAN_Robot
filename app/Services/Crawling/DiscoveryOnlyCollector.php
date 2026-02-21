<?php

namespace App\Services\Crawling;

use App\Models\DiscoveredPage;
use App\Models\Project;
use App\Services\Utils\UrlNormalizer;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

/**
 * Lightweight crawl observer that only records discovered URLs
 * into the discovered_pages table — no HTML storage, no analysis.
 * Skips the homepage URL since it's already analyzed.
 * Pages are stored at project level, persisting across rescans.
 */
class DiscoveryOnlyCollector extends CrawlObserver
{
	/** @var array<string, true> Normalized URLs already seen during this crawl */
	private array $seenUrls = array();

	private int $pagesDiscovered = 0;

	public function __construct(
		private readonly Project $project,
		private readonly string $homepageUrl,
		private readonly int $maxPages,
	) {
		$this->seenUrls[UrlNormalizer::forComparison($homepageUrl)] = true;
	}

	public function crawled(
		UriInterface $url,
		ResponseInterface $response,
		?UriInterface $foundOnUrl = null,
		?string $linkText = null,
	): void {
		if ($this->pagesDiscovered >= $this->maxPages) {
			return;
		}

		$urlString = (string) $url;
		$normalizedUrl = UrlNormalizer::forCrawl($urlString);
		$comparisonUrl = UrlNormalizer::forComparison($urlString);

		if (isset($this->seenUrls[$comparisonUrl])) {
			return;
		}

		$this->seenUrls[$comparisonUrl] = true;

		$statusCode = $response->getStatusCode();
		if ($statusCode < 200 || $statusCode >= 300) {
			return;
		}

		$contentType = $response->getHeaderLine("Content-Type");
		if (!str_contains(strtolower($contentType), "text/html")) {
			return;
		}

		$depth = $this->estimateDepth($normalizedUrl);

		DiscoveredPage::create(array(
			"project_id" => $this->project->id,
			"url" => $normalizedUrl,
			"crawl_depth" => $depth,
		));

		$this->pagesDiscovered++;
	}

	public function crawlFailed(
		UriInterface $url,
		RequestException $requestException,
		?UriInterface $foundOnUrl = null,
		?string $linkText = null,
	): void {
		Log::debug("Discovery crawl failed for URL", array(
			"url" => (string) $url,
			"error" => $requestException->getMessage(),
		));
	}

	public function finishedCrawling(): void
	{
		Log::info("Page discovery completed", array(
			"project_id" => $this->project->id,
			"pages_discovered" => $this->pagesDiscovered,
		));
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
}
