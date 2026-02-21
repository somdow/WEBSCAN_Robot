<?php

namespace App\Services\Scanning;

/**
 * Determines whether an HTTP response contains real HTML content
 * or a bot-protection challenge page (SiteGround, Cloudflare, Sucuri, etc.).
 *
 * Used by ScanOrchestrator to detect blocked responses before wasting
 * resources running 48 analyzers against a captcha redirect page.
 */
class ResponseValidator
{
	private const MINIMUM_REAL_PAGE_BYTES = 1024;

	/**
	 * Challenge markers mapped to their human-readable source name.
	 * Each key is a case-insensitive substring to search for in the HTML body.
	 */
	private const CHALLENGE_MARKERS = array(
		"/.well-known/sgcaptcha/" => "SiteGround bot protection",
		"/.well-known/captcha/" => "SiteGround bot protection",
		"cf-browser-verification" => "Cloudflare JS challenge",
		"challenge-platform" => "Cloudflare JS challenge",
		"_cf_chl_opt" => "Cloudflare Turnstile challenge",
		"cf-turnstile-response" => "Cloudflare Turnstile challenge",
		"sucuri-firewall" => "Sucuri firewall",
		"Access Denied - Sucuri" => "Sucuri firewall",
		"DDoS protection by" => "DDoS protection service",
		"Checking your browser before accessing" => "Browser verification challenge",
		"Attention Required! | Cloudflare" => "Cloudflare attention page",
	);

	/**
	 * Check whether the response body looks like a real HTML page
	 * rather than a bot-protection challenge or empty shell.
	 *
	 * When $skipChallengeMarkers is true, challenge string detection is bypassed.
	 * This is used for Zyte browser-rendered responses where the real page content
	 * may still contain Cloudflare JS artifacts in the DOM after solving the challenge.
	 */
	public function isRealHtmlPage(?string $content, ?int $httpStatusCode = null, bool $skipChallengeMarkers = false): bool
	{
		if ($content === null || $content === "") {
			return false;
		}

		if (strlen($content) < self::MINIMUM_REAL_PAGE_BYTES) {
			return false;
		}

		if (!$skipChallengeMarkers) {
			if ($this->containsChallengeMarker($content) !== null) {
				return false;
			}

			if ($this->isCaptchaMetaRefresh($content)) {
				return false;
			}
		}

		if (!$this->hasBasicHtmlStructure($content)) {
			return false;
		}

		return true;
	}

	/**
	 * Returns a human-readable reason string if the content appears to be
	 * a bot-protection challenge, or null if the page looks legitimate.
	 *
	 * When $skipChallengeMarkers is true, challenge string detection is bypassed.
	 * Used for logging and user-facing blocked scan messages.
	 */
	public function getBlockReason(?string $content, ?int $httpStatusCode = null, bool $skipChallengeMarkers = false): ?string
	{
		if ($content === null || $content === "") {
			return "Empty response body";
		}

		if (strlen($content) < self::MINIMUM_REAL_PAGE_BYTES) {
			if (!$skipChallengeMarkers) {
				$challengeMarker = $this->containsChallengeMarker($content);

				if ($challengeMarker !== null) {
					return $challengeMarker;
				}

				if ($this->isCaptchaMetaRefresh($content)) {
					return "Bot protection redirect (meta refresh to captcha)";
				}
			}

			return "Suspiciously small response (" . strlen($content) . " bytes)";
		}

		if (!$skipChallengeMarkers) {
			$challengeMarker = $this->containsChallengeMarker($content);
			if ($challengeMarker !== null) {
				return $challengeMarker;
			}

			if ($this->isCaptchaMetaRefresh($content)) {
				return "Bot protection redirect (meta refresh to captcha)";
			}
		}

		if (!$this->hasBasicHtmlStructure($content)) {
			return "Response lacks basic HTML structure (no title or links)";
		}

		return null;
	}

	/**
	 * Search for known challenge page markers in the response body.
	 * Returns the human-readable source name if found, null otherwise.
	 */
	private function containsChallengeMarker(string $content): ?string
	{
		foreach (self::CHALLENGE_MARKERS as $marker => $sourceName) {
			if (stripos($content, $marker) !== false) {
				return $sourceName;
			}
		}

		return null;
	}

	/**
	 * Detect <meta http-equiv="refresh"> redirects to captcha/challenge URLs.
	 * These are HTML-level redirects that Guzzle cannot follow.
	 */
	private function isCaptchaMetaRefresh(string $content): bool
	{
		$captchaPatterns = array(
			"sgcaptcha",
			"captcha",
			"challenge",
			"_cf_chl",
			"security-check",
		);

		if (preg_match('/<meta\s[^>]*http-equiv=["\']refresh["\'][^>]*content=["\'][^"\']*url=([^"\']+)/i', $content, $match)) {
			$redirectUrl = strtolower($match[1]);

			foreach ($captchaPatterns as $pattern) {
				if (strpos($redirectUrl, $pattern) !== false) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Verify the response has basic HTML structure: a <title> tag
	 * and at least one <a href> link. Real web pages always have both.
	 */
	private function hasBasicHtmlStructure(string $content): bool
	{
		$hasTitle = stripos($content, "<title") !== false;
		$hasLink = preg_match('/<a\s[^>]*href\s*=/i', $content) === 1;

		return $hasTitle && $hasLink;
	}
}
