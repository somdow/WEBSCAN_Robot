<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Checks for proper use of HTML5 semantic landmark elements.
 * Semantic elements improve accessibility, screen reader navigation,
 * and help search engines understand page structure.
 */
class SemanticHtmlAnalyzer implements AnalyzerInterface
{
	/**
	 * Landmark elements and their SEO/accessibility significance.
	 */
	private const LANDMARKS = array(
		"header" => "Identifies site/section headers for crawlers and screen readers",
		"nav" => "Marks navigation regions, helps search engines identify site structure",
		"main" => "Signals the primary content area, critical for content extraction",
		"article" => "Defines self-contained content blocks, supports article-level indexing",
		"section" => "Groups thematically related content with implicit ARIA landmark",
		"aside" => "Identifies supplementary content (sidebars, related links)",
		"footer" => "Marks footer regions, often contains trust signals (copyright, links)",
	);

	/**
	 * Elements that are critical for a well-structured page.
	 */
	private const CRITICAL_ELEMENTS = array("main", "header", "nav");

	public function moduleKey(): string
	{
		return "semanticHtml";
	}

	public function label(): string
	{
		return "Semantic HTML";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.semanticHtml", 4);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$findings = array();
		$recommendations = array();
		$foundCount = 0;
		$missingCritical = array();

		foreach (self::LANDMARKS as $element => $purpose) {
			$nodes = $xpath->query("//{$element}");
			$count = $nodes ? $nodes->length : 0;

			if ($count > 0) {
				$foundCount++;
				$findings[] = array(
					"type" => "ok",
					"message" => "<{$element}> found ({$count}). {$purpose}.",
				);
			} else {
				$isCritical = in_array($element, self::CRITICAL_ELEMENTS, true);
				$findings[] = array(
					"type" => $isCritical ? "warning" : "info",
					"message" => "No <{$element}> element found. {$purpose}.",
				);

				if ($isCritical) {
					$missingCritical[] = $element;
				}
			}
		}

		$totalLandmarks = count(self::LANDMARKS);
		$findings[] = array(
			"type" => "data",
			"message" => "Semantic elements found: {$foundCount}/{$totalLandmarks}",
		);

		$status = $this->determineStatus($foundCount, $missingCritical);

		if (count($missingCritical) > 0) {
			$missing = implode(">, <", $missingCritical);
			$recommendations[] = "Add <{$missing}> landmark element(s). These are critical for accessibility and help search engines understand your page layout.";
		}

		if ($foundCount < 4) {
			$recommendations[] = "Use more HTML5 semantic elements to improve page structure. Wrap content sections in <article> or <section>, add <aside> for sidebars, and use <footer> for site footer content.";
		}

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	private function determineStatus(int $foundCount, array $missingCritical): ModuleStatus
	{
		if (count($missingCritical) > 0) {
			return ModuleStatus::Warning;
		}

		if ($foundCount >= 5) {
			return ModuleStatus::Ok;
		}

		return ModuleStatus::Warning;
	}
}
