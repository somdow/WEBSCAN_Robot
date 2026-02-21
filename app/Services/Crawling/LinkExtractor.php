<?php

namespace App\Services\Crawling;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

/**
 * Extracts same-domain links from HTML content.
 * Shared by ZyteCrawlerService (full scan crawl) and DiscoverPagesJob (Zyte discovery fallback).
 */
class LinkExtractor
{
	/**
	 * Parse HTML and return absolute same-domain URLs that pass the crawl profile filter.
	 *
	 * @return string[] Unique absolute URLs discovered on the page
	 */
	public static function extractSameDomainLinks(string $html, string $baseUrl, SameDomainHtmlProfile $profile): array
	{
		$links = array();

		try {
			$domCrawler = new DomCrawler($html, $baseUrl);

			$domCrawler->filterXpath("//a")->each(function (DomCrawler $node) use ($baseUrl, $profile, &$links) {
				try {
					$href = $node->attr("href");

					if ($href === null || $href === "" || str_starts_with($href, "#") || str_starts_with($href, "javascript:") || str_starts_with($href, "mailto:") || str_starts_with($href, "tel:")) {
						return;
					}

					$absoluteUrl = self::resolveUrl($href, $baseUrl);

					if ($absoluteUrl === null) {
						return;
					}

					$uri = new Uri($absoluteUrl);

					if (!$profile->shouldCrawl($uri)) {
						return;
					}

					$links[] = (string) $uri->withFragment("");
				} catch (\Throwable) {
					/* Skip malformed URLs */
				}
			});
		} catch (\Throwable $exception) {
			Log::debug("LinkExtractor extraction failed", array(
				"url" => $baseUrl,
				"error" => $exception->getMessage(),
			));
		}

		return array_values(array_unique($links));
	}

	/**
	 * Resolve a potentially relative URL against a base URL.
	 */
	public static function resolveUrl(string $href, string $baseUrl): ?string
	{
		if (str_starts_with($href, "http://") || str_starts_with($href, "https://")) {
			return $href;
		}

		if (str_starts_with($href, "//")) {
			$scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? "https";
			return $scheme . ":" . $href;
		}

		$parsed = parse_url($baseUrl);
		if ($parsed === false || !isset($parsed["host"])) {
			return null;
		}

		$scheme = $parsed["scheme"] ?? "https";
		$host = $parsed["host"];
		$port = isset($parsed["port"]) ? ":" . $parsed["port"] : "";

		if (str_starts_with($href, "/")) {
			return $scheme . "://" . $host . $port . $href;
		}

		$basePath = $parsed["path"] ?? "/";
		$baseDir = substr($basePath, 0, (int) strrpos($basePath, "/") + 1);

		return $scheme . "://" . $host . $port . $baseDir . $href;
	}
}
