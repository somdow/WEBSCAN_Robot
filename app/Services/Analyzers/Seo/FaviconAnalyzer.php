<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

class FaviconAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "favicon";
	}

	public function label(): string
	{
		return "Favicon";
	}

	public function category(): string
	{
		return "Extras";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.favicon", 2);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$findings = array();

		$iconNodes = $xpath->query("//head/link[contains(@rel, 'icon')]");
		$declaredRels = array();

		if ($iconNodes && $iconNodes->length > 0) {
			for ($index = 0; $index < $iconNodes->length; $index++) {
				$node = $iconNodes->item($index);
				if ($node instanceof DOMElement) {
					$rel = $node->getAttribute("rel");
					$href = $node->getAttribute("href");
					$declaredRels[] = "{$rel}: {$href}";
				}
			}
		}

		if (empty($declaredRels)) {
			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: array(array("type" => "warning", "message" => "No favicon declaration found in HTML. Browsers will look for /favicon.ico by default.")),
				recommendations: array("Add a favicon link in the <head> section: <link rel=\"icon\" type=\"image/png\" href=\"/favicon.png\">"),
			);
		}

		$findings[] = array("type" => "info", "message" => "Favicon declarations: " . implode(", ", $declaredRels));
		$findings[] = array("type" => "ok", "message" => "Favicon is properly declared.");

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: array());
	}
}
