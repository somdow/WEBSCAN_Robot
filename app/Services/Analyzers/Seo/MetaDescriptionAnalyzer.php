<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class MetaDescriptionAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "metaDescription";
	}

	public function label(): string
	{
		return "Meta Description";
	}

	public function category(): string
	{
		return "On-Page SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.metaDescription", 8);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$description = $scanContext->metaDescriptionContent;
		$tagCount = $scanContext->metaDescriptionTagCount;
		$minLength = config("scanning.thresholds.metaDescription.minLength", 70);
		$maxLength = config("scanning.thresholds.metaDescription.maxLength", 160);
		$rangeString = "{$minLength}-{$maxLength}";

		$findings = array();
		$recommendations = array();

		if ($tagCount > 1) {
			$findings[] = array("type" => "warning", "message" => "Multiple meta description tags found ({$tagCount}). Only the first will typically be used by search engines.");
			$recommendations[] = "Remove duplicate meta description tags. Keep only one unique description per page.";
		}

		if ($description === null || $description === "") {
			return new AnalysisResult(
				status: ModuleStatus::Bad,
				findings: array_merge($findings, array(
					array("type" => "bad", "message" => "Missing Meta Description: No meta description tag found. This is important for search result click-through rates."),
				)),
				recommendations: array_merge($recommendations, array(
					"Add a compelling meta description between {$rangeString} characters that accurately summarizes the page content.",
					"Include relevant keywords naturally and a call to action to improve click-through rates.",
				)),
			);
		}

		$length = mb_strlen($description, "UTF-8");

		$findings[] = array(
			"type" => "info",
			"message" => "Current Description: \"{$description}\" (Length: {$length} characters)",
		);

		if ($length < $minLength) {
			$status = ModuleStatus::Warning;
			$findings[] = array("type" => "warning", "message" => "Description May Be Too Short: At {$length} characters, it's below the recommended range ({$rangeString} characters).");
			$recommendations[] = "Expand the meta description to {$rangeString} characters. Use the space to clearly describe the page and entice users to click.";
		} elseif ($length > $maxLength) {
			$status = ModuleStatus::Warning;
			$findings[] = array("type" => "warning", "message" => "Description May Be Too Long: At {$length} characters, it exceeds the recommended range ({$rangeString} characters) and may be truncated in search results.");
			$recommendations[] = "Shorten the meta description to {$rangeString} characters. Place the most important information at the beginning.";
		} else {
			$status = ModuleStatus::Ok;
			$findings[] = array("type" => "ok", "message" => "Description Length Optimal: At {$length} characters, it fits well within the recommended range ({$rangeString} characters).");
		}

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}
}
