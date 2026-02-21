<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

class LinkAnalysisAnalyzer implements AnalyzerInterface
{
	private const GENERIC_ANCHOR_PATTERNS = array(
		"click here",
		"here",
		"read more",
		"learn more",
		"more",
		"link",
		"this",
		"go",
		"see more",
		"find out more",
		"continue reading",
		"details",
		"more info",
		"view more",
	);

	public function moduleKey(): string
	{
		return "linkAnalysis";
	}

	public function label(): string
	{
		return "Link Analysis";
	}

	public function category(): string
	{
		return "Graphs, Schema & Links";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.linkAnalysis", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$baseHost = parse_url($scanContext->effectiveUrl, PHP_URL_HOST);
		$findings = array();
		$recommendations = array();

		$linkNodes = $xpath->query("//a[@href]");
		$totalLinks = 0;
		$internalLinks = 0;
		$externalLinks = 0;
		$nofollowLinks = 0;
		$genericAnchorCount = 0;
		$emptyAnchorCount = 0;

		if ($linkNodes) {
			foreach ($linkNodes as $node) {
				if (!($node instanceof DOMElement)) {
					continue;
				}

				$href = trim($node->getAttribute("href"));
				if ($href === "" || str_starts_with($href, "#") || str_starts_with($href, "javascript:") || str_starts_with($href, "mailto:") || str_starts_with($href, "tel:")) {
					continue;
				}

				$totalLinks++;
				$linkHost = parse_url($href, PHP_URL_HOST);

				if ($linkHost === null || $linkHost === false || $linkHost === $baseHost || str_ends_with($linkHost, ".{$baseHost}")) {
					$internalLinks++;
				} else {
					$externalLinks++;
				}

				$rel = strtolower($node->getAttribute("rel"));
				if (str_contains($rel, "nofollow")) {
					$nofollowLinks++;
				}

				$anchorText = strtolower(trim($node->textContent));

				if ($anchorText === "") {
					$hasImageWithAlt = $this->linkHasImageWithAlt($node);
					if (!$hasImageWithAlt) {
						$emptyAnchorCount++;
					}
				} elseif (in_array($anchorText, self::GENERIC_ANCHOR_PATTERNS, true)) {
					$genericAnchorCount++;
				}
			}
		}

		$findings[] = array("type" => "info", "message" => "Total Links: {$totalLinks} (Internal: {$internalLinks}, External: {$externalLinks}, Nofollow: {$nofollowLinks})");

		if ($totalLinks === 0) {
			$findings[] = array("type" => "warning", "message" => "No links found on this page. Internal and external links help search engines discover and understand your content.");
			$recommendations[] = "Add internal links to other relevant pages on your site.";
			$recommendations[] = "Consider adding links to authoritative external resources where appropriate.";

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		$issues = array();

		if ($internalLinks === 0) {
			$findings[] = array("type" => "warning", "message" => "No internal links found. Internal linking helps distribute page authority and aids navigation.");
			$recommendations[] = "Add internal links to related pages on your site to improve crawlability and user experience.";
			$issues[] = "warning";
		}

		if ($emptyAnchorCount > 0) {
			$findings[] = array(
				"type" => "warning",
				"message" => "{$emptyAnchorCount} link(s) have no anchor text and no image alt text. Search engines cannot determine the purpose of these links.",
			);
			$recommendations[] = "Add descriptive anchor text to links, or add alt text to images that serve as links.";
			$issues[] = "warning";
		}

		if ($genericAnchorCount > 0) {
			$findings[] = array(
				"type" => "warning",
				"message" => "{$genericAnchorCount} link(s) use generic anchor text (e.g., \"click here\", \"read more\"). Descriptive anchor text helps search engines understand the destination page's topic.",
			);
			$recommendations[] = "Replace generic anchor text with descriptive phrases that indicate the destination content. For example, use \"view our pricing plans\" instead of \"click here\".";
			$issues[] = "warning";
		}

		if (empty($issues)) {
			$findings[] = array("type" => "ok", "message" => "Link structure analyzed. {$internalLinks} internal and {$externalLinks} external links with descriptive anchor text.");
		}

		$status = empty($issues) ? ModuleStatus::Ok : ModuleStatus::Warning;

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Check if a link element contains an image child with alt text.
	 * Image links with alt text are acceptable (the alt serves as anchor text).
	 */
	private function linkHasImageWithAlt(DOMElement $linkNode): bool
	{
		$imgNodes = $linkNode->getElementsByTagName("img");

		if ($imgNodes->length === 0) {
			return false;
		}

		for ($index = 0; $index < $imgNodes->length; $index++) {
			$img = $imgNodes->item($index);

			if ($img instanceof DOMElement && trim($img->getAttribute("alt")) !== "") {
				return true;
			}
		}

		return false;
	}
}
