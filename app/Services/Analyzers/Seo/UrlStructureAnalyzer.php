<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Analyzes the scanned URL for SEO best practices: clean structure,
 * reasonable depth, proper use of hyphens, no query parameters in
 * primary content URLs, and reasonable length.
 */
class UrlStructureAnalyzer implements AnalyzerInterface
{

	public function moduleKey(): string
	{
		return "urlStructure";
	}

	public function label(): string
	{
		return "URL Structure";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.urlStructure", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$url = $scanContext->effectiveUrl;
		$parsed = parse_url($url);
		$findings = array();
		$recommendations = array();
		$issues = 0;
		$thresholds = config("scanning.thresholds.urlStructure", array());

		$findings[] = array("type" => "data", "message" => "Analyzed URL: {$url}");

		$issues += $this->checkProtocol($parsed, $findings, $recommendations);
		$issues += $this->checkPathStructure($parsed, $findings, $recommendations, $thresholds);
		$issues += $this->checkQueryParameters($parsed, $findings, $recommendations);
		$issues += $this->checkUrlLength($url, $findings, $recommendations, $thresholds);
		$issues += $this->checkCharacters($parsed, $findings, $recommendations);
		$issues += $this->checkFragment($parsed, $findings);

		$status = $this->determineStatus($issues);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	private function checkProtocol(array $parsed, array &$findings, array &$recommendations): int
	{
		$scheme = strtolower($parsed["scheme"] ?? "");

		if ($scheme === "https") {
			$findings[] = array("type" => "ok", "message" => "URL uses HTTPS protocol.");
			return 0;
		}

		$findings[] = array("type" => "warning", "message" => "URL uses HTTP instead of HTTPS. HTTPS is a confirmed ranking signal.");
		$recommendations[] = "Migrate to HTTPS. Google uses HTTPS as a ranking factor, and browsers flag HTTP pages as \"Not Secure\".";
		return 1;
	}

	private function checkPathStructure(array $parsed, array &$findings, array &$recommendations, array $thresholds): int
	{
		$path = $parsed["path"] ?? "/";
		$issues = 0;
		$maxDepth = $thresholds["maxRecommendedDepth"] ?? 4;
		$warnDepth = $thresholds["warnDepth"] ?? 6;

		$segments = array_filter(explode("/", trim($path, "/")), fn(string $segment) => $segment !== "");
		$depth = count($segments);

		if ($depth === 0) {
			$findings[] = array("type" => "ok", "message" => "Root URL (homepage). No path depth concerns.");
			return 0;
		}

		$findings[] = array("type" => "data", "message" => "URL depth: {$depth} level(s) (/{$path})");

		if ($depth > $warnDepth) {
			$findings[] = array("type" => "warning", "message" => "URL depth is {$depth} levels — excessively deep. Deep URLs receive less crawl priority and are harder for users to remember.");
			$recommendations[] = "Flatten your URL structure to 3-4 levels maximum. Deep nesting dilutes link equity and signals lower content importance to crawlers.";
			$issues++;
		} elseif ($depth > $maxDepth) {
			$findings[] = array("type" => "info", "message" => "URL depth is {$depth} levels — slightly deep. Ideally keep URLs within 3-4 levels.");
		} else {
			$findings[] = array("type" => "ok", "message" => "URL depth is within the recommended 1-{$maxDepth} levels.");
		}

		foreach ($segments as $segment) {
			if (preg_match("/[A-Z]/", $segment)) {
				$findings[] = array("type" => "warning", "message" => "URL contains uppercase characters in \"{$segment}\". URLs are case-sensitive on most servers, which can cause duplicate content.");
				$recommendations[] = "Use lowercase-only URLs. Mixed case creates duplicate content risk (e.g., /Page and /page may serve the same content with different URLs).";
				$issues++;
				break;
			}
		}

		$hasUnderscore = false;
		foreach ($segments as $segment) {
			if (str_contains($segment, "_")) {
				$hasUnderscore = true;
				break;
			}
		}

		if ($hasUnderscore) {
			$findings[] = array("type" => "warning", "message" => "URL uses underscores (_) as word separators. Google treats hyphens as word separators but not underscores.");
			$recommendations[] = "Replace underscores with hyphens in URLs. Google's John Mueller has confirmed hyphens are preferred for word separation in URLs.";
			$issues++;
		} else {
			$hasHyphen = false;
			foreach ($segments as $segment) {
				if (str_contains($segment, "-")) {
					$hasHyphen = true;
					break;
				}
			}

			if ($hasHyphen) {
				$findings[] = array("type" => "ok", "message" => "URL uses hyphens (-) as word separators — the recommended convention.");
			}
		}

		if (preg_match("/\.\w{2,5}$/", $path) && !preg_match("/\.(html|htm|php|asp|aspx)$/i", $path)) {
			$findings[] = array("type" => "info", "message" => "URL has a file extension. Clean URLs without extensions are preferred for flexibility.");
		} elseif (preg_match("/\.(html|htm|php|asp|aspx)$/i", $path)) {
			$findings[] = array("type" => "info", "message" => "URL includes a file extension. While not harmful, extension-free URLs are more flexible and modern.");
		}

		return $issues;
	}

	private function checkQueryParameters(array $parsed, array &$findings, array &$recommendations): int
	{
		$query = $parsed["query"] ?? null;

		if ($query === null || $query === "") {
			$findings[] = array("type" => "ok", "message" => "URL has no query parameters — clean and crawlable.");
			return 0;
		}

		parse_str($query, $params);
		$paramCount = count($params);
		$paramNames = implode(", ", array_keys($params));

		$findings[] = array("type" => "warning", "message" => "URL contains {$paramCount} query parameter(s): {$paramNames}. Query strings can cause duplicate content and crawl budget waste.");
		$recommendations[] = "Use clean URL paths instead of query parameters for primary content pages. If parameters are necessary (filters, pagination), ensure canonical tags point to the clean version.";

		return 1;
	}

	private function checkUrlLength(string $url, array &$findings, array &$recommendations, array $thresholds): int
	{
		$pathOnly = parse_url($url, PHP_URL_PATH) ?? "/";
		$length = strlen($pathOnly);
		$maxLength = $thresholds["maxRecommendedLength"] ?? 75;
		$warnLength = $thresholds["warnLength"] ?? 100;

		if ($length <= $maxLength) {
			$findings[] = array("type" => "ok", "message" => "URL path length ({$length} chars) is within the recommended limit of {$maxLength} characters.");
			return 0;
		}

		if ($length <= $warnLength) {
			$findings[] = array("type" => "info", "message" => "URL path is {$length} characters — slightly long. Shorter URLs tend to perform better in search results.");
			return 0;
		}

		$findings[] = array("type" => "warning", "message" => "URL path is {$length} characters — too long. Long URLs are truncated in SERPs and harder for users to share.");
		$recommendations[] = "Shorten the URL path to under {$maxLength} characters. Remove filler words and keep only meaningful, keyword-rich segments.";
		return 1;
	}

	private function checkCharacters(array $parsed, array &$findings, array &$recommendations): int
	{
		$path = $parsed["path"] ?? "/";

		if (preg_match("/%[0-9A-Fa-f]{2}/", $path)) {
			$findings[] = array("type" => "warning", "message" => "URL contains encoded special characters. These reduce readability and shareability.");
			$recommendations[] = "Avoid special characters in URLs. Use only lowercase letters, numbers, and hyphens for maximum compatibility.";
			return 1;
		}

		if (preg_match("/[^a-zA-Z0-9\/\-._~]/", $path)) {
			$findings[] = array("type" => "info", "message" => "URL path contains non-standard characters. Stick to alphanumeric characters, hyphens, and forward slashes.");
			return 0;
		}

		return 0;
	}

	private function checkFragment(array $parsed, array &$findings): int
	{
		$fragment = $parsed["fragment"] ?? null;

		if ($fragment !== null && $fragment !== "") {
			$findings[] = array("type" => "info", "message" => "URL contains a fragment (#{$fragment}). Fragments are ignored by search engines for indexing purposes.");
		}

		return 0;
	}

	private function determineStatus(int $issues): ModuleStatus
	{
		if ($issues === 0) {
			return ModuleStatus::Ok;
		}

		if ($issues <= 2) {
			return ModuleStatus::Warning;
		}

		return ModuleStatus::Bad;
	}
}
