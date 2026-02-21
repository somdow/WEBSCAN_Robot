<?php

namespace App\Services\Crawling;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

/**
 * Restricts crawling to same-host HTML pages only.
 * Skips non-HTML resources, WordPress admin paths, and query-heavy URLs.
 */
class SameDomainHtmlProfile extends CrawlProfile
{
	private const SKIP_EXTENSIONS = array(
		"pdf", "jpg", "jpeg", "png", "gif", "svg", "webp", "ico", "bmp", "tiff",
		"css", "js", "json", "xml", "rss", "atom",
		"zip", "tar", "gz", "rar", "7z",
		"mp3", "mp4", "avi", "mov", "wmv", "flv", "webm",
		"woff", "woff2", "ttf", "eot", "otf",
		"doc", "docx", "xls", "xlsx", "ppt", "pptx",
	);

	private const SKIP_PATH_PATTERNS = array(
		"/wp-admin/",
		"/wp-login",
		"/wp-json/",
		"/feed/",
		"/xmlrpc.php",
		"/wp-cron.php",
		"/wp-trackback.php",
		"/wp-comments-post.php",
		"/trackback/",
		"/cgi-bin/",
		"/cdn-cgi/",
	);

	private const SKIP_QUERY_PARAMS = array(
		"s=",
		"replytocom=",
		"preview=",
		"customize_changeset_uuid=",
	);

	private string $baseHost;

	/** Base host without leading "www." for flexible matching */
	private string $baseHostNaked;

	public function __construct(string $baseUrl)
	{
		$this->baseHost = parse_url($baseUrl, PHP_URL_HOST) ?? "";
		$this->baseHostNaked = self::stripWww($this->baseHost);
	}

	public function shouldCrawl(UriInterface $url): bool
	{
		if (!$this->isSameHost($url)) {
			return false;
		}

		if (!$this->isHttpScheme($url)) {
			return false;
		}

		if ($this->hasSkippableExtension($url)) {
			return false;
		}

		if ($this->matchesSkipPathPattern($url)) {
			return false;
		}

		if ($this->hasSkippableQueryParam($url)) {
			return false;
		}

		return true;
	}

	private function isSameHost(UriInterface $url): bool
	{
		$urlHostNaked = self::stripWww($url->getHost());

		return strcasecmp($urlHostNaked, $this->baseHostNaked) === 0;
	}

	private static function stripWww(string $host): string
	{
		return preg_replace("/^www\./i", "", $host);
	}

	private function isHttpScheme(UriInterface $url): bool
	{
		$scheme = strtolower($url->getScheme());

		return $scheme === "http" || $scheme === "https";
	}

	private function hasSkippableExtension(UriInterface $url): bool
	{
		$path = strtolower($url->getPath());
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		return $extension !== "" && in_array($extension, self::SKIP_EXTENSIONS, true);
	}

	private function matchesSkipPathPattern(UriInterface $url): bool
	{
		$path = strtolower($url->getPath());

		foreach (self::SKIP_PATH_PATTERNS as $pattern) {
			if (str_contains($path, strtolower($pattern))) {
				return true;
			}
		}

		return false;
	}

	private function hasSkippableQueryParam(UriInterface $url): bool
	{
		$query = strtolower($url->getQuery());

		if ($query === "") {
			return false;
		}

		foreach (self::SKIP_QUERY_PARAMS as $param) {
			if (str_contains($query, strtolower($param))) {
				return true;
			}
		}

		return false;
	}
}
