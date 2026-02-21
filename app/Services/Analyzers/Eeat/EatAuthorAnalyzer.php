<?php

namespace App\Services\Analyzers\Eeat;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Detects author signals that contribute to E-E-A-T credibility:
 * JSON-LD author/Person schema, rel="author" links, byline patterns, and social profiles.
 */
class EatAuthorAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "eatAuthor";
	}

	public function label(): string
	{
		return "Author Signals";
	}

	public function category(): string
	{
		return "E-E-A-T Signals";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.eatAuthor", 7);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$findings = array();
		$recommendations = array();
		$signalCount = 0;

		$hasSchemaAuthor = $this->detectSchemaAuthor($scanContext);
		if ($hasSchemaAuthor) {
			$signalCount++;
			$findings[] = array("type" => "ok", "message" => "Author identified in structured data (JSON-LD).");
		}

		$hasRelAuthor = $this->detectRelAuthorLink($scanContext);
		if ($hasRelAuthor) {
			$signalCount++;
			$findings[] = array("type" => "ok", "message" => "Author link found with rel=\"author\" attribute.");
		}

		$bylineName = $this->detectBylinePattern($scanContext);
		if ($bylineName !== null) {
			$signalCount++;
			$findings[] = array("type" => "ok", "message" => "Author byline detected: \"{$bylineName}\".");
		}

		$hasSocialLinks = $this->detectAuthorSocialLinks($scanContext);
		if ($hasSocialLinks) {
			$signalCount++;
			$findings[] = array("type" => "ok", "message" => "Author social profile links found (LinkedIn, Twitter/X).");
		}

		if ($signalCount >= 2) {
			$findings[] = array("type" => "ok", "message" => "Strong author signals detected ({$signalCount} indicators). This supports E-E-A-T credibility.");
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		if ($signalCount === 1) {
			$findings[] = array("type" => "warning", "message" => "Author signal detected but lacks supporting indicators. Search engines value multiple author signals for E-E-A-T.");
			$recommendations[] = "Add structured data (JSON-LD) with an author field containing name and URL.";
			$recommendations[] = "Include an author bio section with links to the author's social profiles or personal website.";
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array("type" => "bad", "message" => "No author signals detected. Authorship is a key E-E-A-T factor for content credibility.");
		$recommendations[] = "Add a visible author byline (e.g., \"By [Author Name]\") near the top of your content.";
		$recommendations[] = "Include JSON-LD structured data with an author field containing name and URL.";
		$recommendations[] = "Link to author bio pages or professional profiles to establish expertise.";

		return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Check JSON-LD structured data for author or Person type.
	 */
	private function detectSchemaAuthor(ScanContext $scanContext): bool
	{
		$scriptNodes = $scanContext->xpath->query("//script[@type='application/ld+json']");
		if ($scriptNodes === false || $scriptNodes->length === 0) {
			return false;
		}

		for ($i = 0; $i < $scriptNodes->length; $i++) {
			$decoded = json_decode(trim($scriptNodes->item($i)->textContent), true);
			if (!is_array($decoded)) {
				continue;
			}

			if ($this->schemaContainsAuthor($decoded)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Recursively check decoded JSON-LD for author or Person type.
	 */
	private function schemaContainsAuthor(array $schemaData): bool
	{
		$type = $schemaData["@type"] ?? null;
		if ($type === "Person" || (is_array($type) && in_array("Person", $type, true))) {
			return true;
		}

		if (isset($schemaData["author"])) {
			return true;
		}

		if (isset($schemaData["@graph"]) && is_array($schemaData["@graph"])) {
			foreach ($schemaData["@graph"] as $item) {
				if (is_array($item) && $this->schemaContainsAuthor($item)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Detect <a rel="author"> links in the DOM.
	 */
	private function detectRelAuthorLink(ScanContext $scanContext): bool
	{
		$nodes = $scanContext->xpath->query("//a[@rel='author']");

		return $nodes !== false && $nodes->length > 0;
	}

	/**
	 * Detect author byline patterns via itemprop, class attributes, or text patterns.
	 */
	private function detectBylinePattern(ScanContext $scanContext): ?string
	{
		$authorPropNodes = $scanContext->xpath->query("//*[@itemprop='author']");
		if ($authorPropNodes !== false && $authorPropNodes->length > 0) {
			$text = trim($authorPropNodes->item(0)->textContent);
			if ($text !== "") {
				return mb_substr($text, 0, 80);
			}
		}

		$authorClassNodes = $scanContext->xpath->query("//*[contains(@class, 'author')]");
		if ($authorClassNodes !== false) {
			for ($i = 0; $i < $authorClassNodes->length; $i++) {
				$text = trim($authorClassNodes->item($i)->textContent);
				if ($text !== "" && mb_strlen($text) < 200) {
					return mb_substr($text, 0, 80);
				}
			}
		}

		$bodyText = strip_tags($scanContext->htmlContent);
		if (preg_match('/\b(?:by|written by|author[:\s]+)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,3})/i', $bodyText, $matches)) {
			return mb_substr(trim($matches[0]), 0, 80);
		}

		return null;
	}

	/**
	 * Detect social profile links (LinkedIn, Twitter/X) in the page.
	 */
	private function detectAuthorSocialLinks(ScanContext $scanContext): bool
	{
		$socialNodes = $scanContext->xpath->query(
			"//a[contains(@href, 'linkedin.com/in/') or contains(@href, 'twitter.com/') or contains(@href, 'x.com/')]"
		);

		return $socialNodes !== false && $socialNodes->length > 0;
	}
}
