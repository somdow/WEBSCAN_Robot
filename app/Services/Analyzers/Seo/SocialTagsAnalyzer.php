<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

class SocialTagsAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "socialTags";
	}

	public function label(): string
	{
		return "Social / Open Graph Tags";
	}

	public function category(): string
	{
		return "Graphs, Schema & Links";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.socialTags", 4);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$findings = array();
		$recommendations = array();

		$ogTags = $this->extractOpenGraphTags($xpath);
		$twitterTags = $this->extractTwitterTags($xpath);

		/* Store raw tag values as structured data for AI before/after display */
		$findings[] = array("type" => "data", "key" => "socialTagValues", "value" => array(
			"ogTitle" => $ogTags["og:title"] ?? "",
			"ogDescription" => $ogTags["og:description"] ?? "",
			"ogImage" => $ogTags["og:image"] ?? "",
			"ogUrl" => $ogTags["og:url"] ?? "",
		));

		$requiredOgTags = array("og:title", "og:type", "og:image", "og:url");
		$missingOgTags = array();

		foreach ($requiredOgTags as $tag) {
			if (!isset($ogTags[$tag]) || $ogTags[$tag] === "") {
				$missingOgTags[] = $tag;
			}
		}

		if (!empty($ogTags)) {
			$findings[] = array("type" => "info", "message" => "Open Graph tags found: " . implode(", ", array_keys($ogTags)));
		}

		if (!empty($twitterTags)) {
			$findings[] = array("type" => "info", "message" => "Twitter Card tags found: " . implode(", ", array_keys($twitterTags)));
		}

		if (empty($ogTags) && empty($twitterTags)) {
			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: array(array("type" => "warning", "message" => "No Open Graph or Twitter Card tags found. These control how your content appears when shared on social media.")),
				recommendations: array(
					"Add Open Graph tags (og:title, og:type, og:image, og:url) for Facebook, LinkedIn, and other platforms.",
					"Add Twitter Card tags (twitter:card, twitter:title, twitter:description, twitter:image) for Twitter/X.",
				),
			);
		}

		if (!empty($missingOgTags)) {
			$findings[] = array("type" => "warning", "message" => "Missing required Open Graph tags: " . implode(", ", $missingOgTags));
			$recommendations[] = "Add the missing Open Graph tags: " . implode(", ", $missingOgTags);

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array("type" => "ok", "message" => "Social sharing tags are properly configured.");

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}

	private function extractOpenGraphTags(\DOMXPath $xpath): array
	{
		$tags = array();
		$nodes = $xpath->query("//head/meta[starts-with(@property, 'og:')]");

		if ($nodes) {
			foreach ($nodes as $node) {
				if ($node instanceof DOMElement) {
					$property = $node->getAttribute("property");
					$content = $node->getAttribute("content");
					$tags[$property] = $content;
				}
			}
		}

		return $tags;
	}

	private function extractTwitterTags(\DOMXPath $xpath): array
	{
		$tags = array();
		$nodes = $xpath->query("//head/meta[starts-with(@name, 'twitter:')]");

		if ($nodes) {
			foreach ($nodes as $node) {
				if ($node instanceof DOMElement) {
					$name = $node->getAttribute("name");
					$content = $node->getAttribute("content");
					$tags[$name] = $content;
				}
			}
		}

		return $tags;
	}
}
