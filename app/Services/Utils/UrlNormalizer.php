<?php

namespace App\Services\Utils;

/**
 * Centralized URL normalization for consistent comparison and deduplication.
 *
 * Two normalization levels:
 * - forCrawl(): preserves query strings, strips fragments — for deduplicating crawled pages
 * - forComparison(): strips query strings AND fragments — for set-based URL matching
 */
class UrlNormalizer
{
	/**
	 * Normalize for crawl deduplication: lowercase scheme+host, strip trailing
	 * slash (except root "/"), strip fragments, PRESERVE query strings.
	 */
	public static function forCrawl(string $url): string
	{
		$parsed = parse_url($url);

		if ($parsed === false || empty($parsed["host"])) {
			return $url;
		}

		$scheme = strtolower($parsed["scheme"] ?? "https");
		$host = strtolower($parsed["host"]);
		$port = isset($parsed["port"]) ? ":{$parsed['port']}" : "";
		$path = $parsed["path"] ?? "/";
		$query = isset($parsed["query"]) ? "?{$parsed['query']}" : "";

		/* Strip trailing slash from path (except root "/") */
		if ($path !== "/" && str_ends_with($path, "/")) {
			$path = rtrim($path, "/");
		}

		/* Fragments are never sent to server — always strip */
		return "{$scheme}://{$host}{$port}{$path}{$query}";
	}

	/**
	 * Normalize for URL comparison: lowercase everything, strip trailing slash,
	 * strip query strings AND fragments. Used for set-based matching where
	 * two URLs that point to the "same page" should be considered equal.
	 */
	public static function forComparison(string $url): string
	{
		$parsed = parse_url(strtolower($url));

		if ($parsed === false || !isset($parsed["host"])) {
			return strtolower(rtrim($url, "/"));
		}

		$scheme = $parsed["scheme"] ?? "https";
		$host = $parsed["host"];
		$port = isset($parsed["port"]) ? ":{$parsed['port']}" : "";
		$path = rtrim($parsed["path"] ?? "/", "/");

		if ($path === "") {
			$path = "/";
		}

		return "{$scheme}://{$host}{$port}{$path}";
	}

	/**
	 * Auto-prepend "https://" to bare domains so visitors can submit
	 * "example.com" via UI forms without the scheme. Trims whitespace
	 * and returns the input unchanged when it already has an http:// or
	 * https:// prefix, or when it is an empty string.
	 */
	public static function prependScheme(string $rawUrl): string
	{
		$trimmed = trim($rawUrl);

		if ($trimmed === "") {
			return $trimmed;
		}

		if (preg_match("#^https?://#i", $trimmed) === 1) {
			return $trimmed;
		}

		return "https://" . $trimmed;
	}
}
