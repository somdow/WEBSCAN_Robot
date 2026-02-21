<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class HtmlLangAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "htmlLang";
	}

	public function label(): string
	{
		return "HTML Language";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.htmlLang", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$langAttribute = $scanContext->langAttribute;

		if ($langAttribute === null || $langAttribute === "") {
			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: array(array("type" => "warning", "message" => "Missing lang attribute on <html> tag. This helps search engines and screen readers understand the page language.")),
				recommendations: array("Add a lang attribute to the <html> tag (e.g., <html lang=\"en\">)."),
			);
		}

		$findings = array(array("type" => "info", "message" => "Language Attribute: {$langAttribute}"));

		if (!preg_match("/^[a-zA-Z]{2,3}(-[a-zA-Z0-9]{2,8})*$/", $langAttribute)) {
			$findings[] = array("type" => "warning", "message" => "The lang attribute value \"{$langAttribute}\" does not appear to follow the BCP 47 standard format (e.g., \"en\", \"en-US\").");

			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: $findings,
				recommendations: array("Use a valid BCP 47 language code such as \"en\", \"en-US\", \"fr\", \"de\", etc."),
			);
		}

		$findings[] = array("type" => "ok", "message" => "Valid language attribute found.");

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: array());
	}
}
