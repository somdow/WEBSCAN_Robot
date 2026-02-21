<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class TitleTagAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "titleTag";
	}

	public function label(): string
	{
		return "Title Tag";
	}

	public function category(): string
	{
		return "On-Page SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.titleTag", 10);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$titleContent = $scanContext->titleContent;
		$titleTagCount = $scanContext->titleTagCount;
		$minLength = config("scanning.thresholds.titleTag.minLength", 30);
		$maxLength = config("scanning.thresholds.titleTag.maxLength", 65);
		$rangeString = "{$minLength}-{$maxLength}";

		$findings = array();
		$recommendations = array();

		if ($titleTagCount > 1) {
			$findings[] = array("type" => "warning", "message" => "Multiple title tags found ({$titleTagCount}). Only one <title> tag should exist per page. Browsers and search engines typically use the first one.");
			$recommendations[] = "Remove duplicate <title> tags. Ensure only one exists in the <head> section.";
		}

		if ($titleContent === null || $titleContent === "") {
			return new AnalysisResult(
				status: ModuleStatus::Bad,
				findings: array_merge($findings, array(
					array("type" => "bad", "message" => "Missing Title Tag: No <title> tag found. This is a critical SEO element."),
				)),
				recommendations: array_merge($recommendations, array(
					"Add a unique and descriptive <title> tag to the <head> section. This tag is crucial for search engines and users.",
					"Include primary keywords naturally, ideally near the beginning.",
					"Write for readability and consider adding your brand name at the end.",
				)),
			);
		}

		$length = mb_strlen($titleContent, "UTF-8");

		$findings[] = array(
			"type" => "info",
			"message" => "Current Title: \"{$titleContent}\" (Length: {$length} characters)",
		);

		if ($length < $minLength) {
			$status = ModuleStatus::Warning;
			$findings[] = array(
				"type" => "warning",
				"message" => "Title May Be Too Short: At {$length} characters, it's below the recommended range ({$rangeString} characters). This might limit its descriptive power and keyword inclusion.",
			);
			$recommendations[] = "Consider expanding the title to be more descriptive within the {$rangeString} character range. Incorporate relevant target keywords.";
		} elseif ($length > $maxLength) {
			$status = ModuleStatus::Warning;
			$findings[] = array(
				"type" => "warning",
				"message" => "Title May Be Too Long: At {$length} characters, it exceeds the recommended range ({$rangeString} characters) and is likely to be truncated in search results.",
			);
			$recommendations[] = "Consider shortening the title to fit within the {$rangeString} character guideline. Place the most crucial keywords near the beginning.";
		} else {
			$status = ModuleStatus::Ok;
			$findings[] = array(
				"type" => "ok",
				"message" => "Title Length Optimal: At {$length} characters, the title fits well within the recommended range ({$rangeString} characters).",
			);
		}

		if ($titleTagCount > 1 && $status === ModuleStatus::Ok) {
			$status = ModuleStatus::Warning;
		}

		return new AnalysisResult(
			status: $status,
			findings: $findings,
			recommendations: $recommendations,
		);
	}
}
