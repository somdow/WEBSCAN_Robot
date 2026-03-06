<?php

namespace App\Services\Scanning;

use App\DataTransferObjects\ScanContext;
use Illuminate\Support\Facades\Log;

/**
 * Mini-crawl service that identifies and fetches trust pages (About, Contact, Privacy, Terms)
 * from the homepage DOM. Extracts nav and footer links, matches against known URL patterns,
 * fetches each page, and returns structured data about content quality.
 */
class TrustPageCrawler
{
	/**
	 * URL path patterns for each trust page type.
	 * Matched against the path component of each discovered link.
	 */
	private const TRUST_PAGE_PATTERNS = array(
		"about" => array("/about", "/about-us", "/about-me", "/company", "/who-we-are", "/our-story", "/our-team"),
		"contact" => array("/contact", "/contact-us", "/get-in-touch", "/reach-us"),
		"privacy" => array("/privacy", "/privacy-policy", "/data-privacy", "/privacypolicy"),
		"terms" => array("/terms", "/terms-of-service", "/terms-and-conditions", "/tos", "/terms-of-use"),
		"cookie" => array("/cookie-policy", "/cookies", "/cookie"),
	);

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	/**
	 * Crawl the homepage to identify and fetch trust pages concurrently.
	 * Returns an array of trust page data structures.
	 */
	public function crawl(ScanContext $scanContext): array
	{
		$maxPages = (int) config("scanning.thresholds.trustPages.maxPagesToFetch", 6);
		$timeout = (int) config("scanning.thresholds.trustPages.fetchTimeoutSeconds", 5);
		$domainRoot = $scanContext->domainRoot();
		$discoveredLinks = $this->extractAllLinks($scanContext, $domainRoot);
		$identifiedPages = $this->matchTrustPageLinks($discoveredLinks, $domainRoot);

		$pagesToFetch = array_slice($identifiedPages, 0, $maxPages, true);

		if (empty($pagesToFetch)) {
			return array();
		}

		$fetchResults = $this->httpFetcher->fetchResourcesConcurrent($pagesToFetch, $timeout, $maxPages);

		$trustPages = array();

		foreach ($pagesToFetch as $pageType => $pageUrl) {
			$fetchResult = $fetchResults[$pageType] ?? null;
			$trustPages[] = $this->analyzeTrustPageResult($pageType, $pageUrl, $fetchResult);
		}

		Log::info("TrustPageCrawler completed", array(
			"domain" => $domainRoot,
			"links_discovered" => count($discoveredLinks),
			"trust_pages_found" => count($trustPages),
		));

		return $trustPages;
	}

	/**
	 * Extract all internal links from the homepage DOM, prioritizing nav and footer regions.
	 */
	private function extractAllLinks(ScanContext $scanContext, string $domainRoot): array
	{
		$xpath = $scanContext->xpath;
		$links = array();

		$anchorNodes = $xpath->query("//a[@href]");
		if ($anchorNodes === false) {
			return $links;
		}

		for ($i = 0; $i < $anchorNodes->length; $i++) {
			$href = trim($anchorNodes->item($i)->getAttribute("href"));
			$resolvedUrl = $this->resolveUrl($href, $domainRoot);

			if ($resolvedUrl === null) {
				continue;
			}

			$resolvedHost = parse_url($resolvedUrl, PHP_URL_HOST);
			$domainHost = parse_url($domainRoot, PHP_URL_HOST);

			if ($resolvedHost !== $domainHost) {
				continue;
			}

			$links[] = $resolvedUrl;
		}

		return array_unique($links);
	}

	/**
	 * Match discovered links against trust page URL patterns.
	 * Returns associative array: pageType => URL (one URL per type, first match wins).
	 */
	private function matchTrustPageLinks(array $links, string $domainRoot): array
	{
		$matched = array();

		foreach ($links as $linkUrl) {
			$path = strtolower(parse_url($linkUrl, PHP_URL_PATH) ?? "");
			$cleanPath = rtrim($path, "/");

			if ($cleanPath === "" || $cleanPath === "/") {
				continue;
			}

			foreach (self::TRUST_PAGE_PATTERNS as $pageType => $patterns) {
				if (isset($matched[$pageType])) {
					continue;
				}

				foreach ($patterns as $pattern) {
					if ($cleanPath === $pattern || str_starts_with($cleanPath, $pattern . "/")) {
						$matched[$pageType] = $linkUrl;
						break 2;
					}
				}
			}
		}

		return $matched;
	}

	/**
	 * Analyze a pre-fetched trust page result.
	 */
	private function analyzeTrustPageResult(string $pageType, string $url, ?\App\DataTransferObjects\FetchResult $fetchResult): array
	{
		$pageData = array(
			"type" => $pageType,
			"url" => $url,
			"exists" => false,
			"httpStatus" => $fetchResult?->httpStatusCode,
			"wordCount" => 0,
			"hasAddress" => false,
			"hasPhone" => false,
			"hasEmail" => false,
			"hasForm" => false,
			"contentSnippet" => "",
		);

		if ($fetchResult === null || !$fetchResult->successful || $fetchResult->content === null) {
			return $pageData;
		}

		$pageData["exists"] = true;
		$bodyText = $this->extractBodyText($fetchResult->content);
		$pageData["wordCount"] = str_word_count($bodyText);
		$pageData["contentSnippet"] = mb_substr(trim($bodyText), 0, 200);

		if ($pageType === "contact") {
			$pageData["hasAddress"] = $this->detectPhysicalAddress($bodyText);
			$pageData["hasPhone"] = $this->detectPhoneNumber($bodyText);
			$pageData["hasEmail"] = $this->detectEmailAddress($fetchResult->content);
			$pageData["hasForm"] = $this->detectContactForm($fetchResult->content);
		}

		return $pageData;
	}

	/**
	 * Extract visible body text from HTML, stripping nav, header, footer, script, style elements.
	 */
	private function extractBodyText(string $html): string
	{
		$cleaned = preg_replace("/<(script|style|nav|header|footer|noscript|aside)[^>]*>.*?<\\/\\1>/si", " ", $html);
		$cleaned = preg_replace("/<[^>]+>/", " ", $cleaned);
		$cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, "UTF-8");
		$cleaned = preg_replace("/\s+/", " ", $cleaned);

		return trim($cleaned);
	}

	/**
	 * Detect a physical address pattern in text (street numbers, common address keywords).
	 */
	private function detectPhysicalAddress(string $text): bool
	{
		$addressPatterns = array(
			"/\d{1,5}\s+[A-Z][a-z]+\s+(Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Drive|Dr|Lane|Ln|Way|Court|Ct|Place|Pl)/i",
			"/\b(Suite|Ste|Floor|Unit|Apt)\s*#?\s*\d+/i",
			"/\b[A-Z][a-z]+,\s*[A-Z]{2}\s+\d{5}/",
			"/\b\d{5}(-\d{4})?\b/",
		);

		foreach ($addressPatterns as $pattern) {
			if (preg_match($pattern, $text)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect phone number patterns in text.
	 */
	private function detectPhoneNumber(string $text): bool
	{
		$phonePatterns = array(
			"/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/",
			"/\b(tel|phone|call|fax)\s*[:.]?\s*[\d\s\-\+\(\)\.]{7,}/i",
		);

		foreach ($phonePatterns as $pattern) {
			if (preg_match($pattern, $text)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect email address in raw HTML (including mailto: links).
	 */
	private function detectEmailAddress(string $html): bool
	{
		return (bool) preg_match("/mailto:[^\s\"']+|[\w.+-]+@[\w-]+\.[\w.]+/i", $html);
	}

	/**
	 * Detect a contact form by looking for <form> tags in HTML.
	 */
	private function detectContactForm(string $html): bool
	{
		return (bool) preg_match("/<form\b[^>]*>/i", $html);
	}

	/**
	 * Resolve a potentially relative URL against the domain root.
	 * Returns null for non-HTTP URLs (javascript:, mailto:, tel:, #anchors).
	 */
	private function resolveUrl(string $href, string $domainRoot): ?string
	{
		if ($href === "" || $href === "#") {
			return null;
		}

		if (preg_match("/^(javascript|mailto|tel|data|ftp):/i", $href)) {
			return null;
		}

		if (str_starts_with($href, "#")) {
			return null;
		}

		if (str_starts_with($href, "//")) {
			return "https:" . $href;
		}

		if (str_starts_with($href, "http://") || str_starts_with($href, "https://")) {
			return $href;
		}

		if (str_starts_with($href, "/")) {
			return rtrim($domainRoot, "/") . $href;
		}

		return rtrim($domainRoot, "/") . "/" . $href;
	}
}
