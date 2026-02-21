<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class CanonicalTagAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "canonicalTag";
	}

	public function label(): string
	{
		return "Canonical Tag";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.canonicalTag", 7);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$canonicalHrefs = $scanContext->canonicalHrefs;
		$tagCount = $scanContext->canonicalTagCount;
		$effectiveUrl = $scanContext->effectiveUrl;
		$findings = array();
		$recommendations = array();

		if ($tagCount === 0) {
			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: array(array("type" => "warning", "message" => "No canonical tag found. A canonical tag helps prevent duplicate content issues.")),
				recommendations: array("Add a <link rel=\"canonical\"> tag pointing to the preferred version of this page."),
			);
		}

		if ($tagCount > 1) {
			$findings[] = array("type" => "bad", "message" => "Multiple canonical tags found ({$tagCount}). Only one canonical tag should exist per page.");
			$recommendations[] = "Remove duplicate canonical tags. Keep only one that points to the preferred URL.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		$href = $canonicalHrefs[0] ?? "";
		$findings[] = array("type" => "info", "message" => "Canonical URL: {$href}");

		if (!preg_match("/^https?:\/\//i", $href)) {
			$findings[] = array("type" => "warning", "message" => "Canonical URL is relative. It should be an absolute URL including the protocol and domain.");
			$recommendations[] = "Change the canonical tag to use an absolute URL (e.g., https://example.com/page).";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$isSelfReferencing = $this->urlsMatch($href, $effectiveUrl);
		if ($isSelfReferencing) {
			$findings[] = array("type" => "ok", "message" => "Self-referencing canonical tag correctly points to this page.");
		} else {
			$findings[] = array("type" => "info", "message" => "Canonical points to a different URL. This page will be treated as a duplicate of: {$href}");
		}

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Compare two URLs accounting for trailing slashes and case differences
	 */
	private function urlsMatch(string $urlA, string $urlB): bool
	{
		$normalizeUrl = function (string $url): string {
			return rtrim(strtolower(trim($url)), "/");
		};

		return $normalizeUrl($urlA) === $normalizeUrl($urlB);
	}
}
