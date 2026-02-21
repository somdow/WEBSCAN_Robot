<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

class HreflangAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "hreflang";
	}

	public function label(): string
	{
		return "Hreflang Tags";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.hreflang", 3);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$effectiveUrl = $scanContext->effectiveUrl;
		$findings = array();
		$recommendations = array();

		$hreflangNodes = $xpath->query("//head/link[@rel='alternate'][@hreflang]");
		$tagCount = $hreflangNodes ? $hreflangNodes->length : 0;

		if ($tagCount === 0) {
			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: array(array("type" => "info", "message" => "No hreflang tags found. These are only needed for multilingual or multi-regional sites.")),
				recommendations: array(),
			);
		}

		$tags = array();
		$hasSelfReference = false;

		for ($index = 0; $index < $tagCount; $index++) {
			$node = $hreflangNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$hreflang = $node->getAttribute("hreflang");
			$href = $node->getAttribute("href");
			$tags[] = array("hreflang" => $hreflang, "href" => $href);

			if ($hreflang !== "x-default" && !preg_match("/^[a-zA-Z]{2,3}(-[a-zA-Z0-9]{2,8})*$/", $hreflang)) {
				$findings[] = array("type" => "warning", "message" => "Invalid hreflang value: \"{$hreflang}\". Must be a valid ISO 639-1 language code.");
				$recommendations[] = "Fix the hreflang value \"{$hreflang}\" to use a valid language code (e.g., \"en\", \"en-US\", \"fr\").";
			}

			if (!preg_match("/^https?:\/\//i", $href)) {
				$findings[] = array("type" => "warning", "message" => "Hreflang href \"{$href}\" is not an absolute URL.");
				$recommendations[] = "Use absolute URLs (including protocol and domain) in all hreflang href attributes.";
			}

			if (rtrim(strtolower($href), "/") === rtrim(strtolower($effectiveUrl), "/")) {
				$hasSelfReference = true;
			}
		}

		$findings[] = array("type" => "info", "message" => "Found {$tagCount} hreflang tag(s) for languages: " . implode(", ", array_column($tags, "hreflang")));

		if (!$hasSelfReference) {
			$findings[] = array("type" => "warning", "message" => "No self-referencing hreflang tag found. Each page should include a hreflang tag pointing to itself.");
			$recommendations[] = "Add a self-referencing hreflang tag for the current page's language.";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		if (empty($recommendations)) {
			$findings[] = array("type" => "ok", "message" => "Hreflang tags are properly configured with self-reference.");
		}

		$hasIssues = !empty($recommendations);

		return new AnalysisResult(
			status: $hasIssues ? ModuleStatus::Warning : ModuleStatus::Ok,
			findings: $findings,
			recommendations: $recommendations,
		);
	}
}
