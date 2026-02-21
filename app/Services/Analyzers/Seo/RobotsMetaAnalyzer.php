<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class RobotsMetaAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "robotsMeta";
	}

	public function label(): string
	{
		return "Robots Meta Tag";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.robotsMeta", 7);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$robotsContent = $scanContext->robotsMetaContent;
		$tagCount = $scanContext->robotsMetaTagCount;
		$findings = array();
		$recommendations = array();

		if ($tagCount === 0) {
			return new AnalysisResult(
				status: ModuleStatus::Ok,
				findings: array(array("type" => "ok", "message" => "No robots meta tag found. Default behavior (index, follow) applies.")),
				recommendations: array(),
			);
		}

		if ($tagCount > 1) {
			$findings[] = array("type" => "warning", "message" => "Multiple robots meta tags found ({$tagCount}). This can cause unpredictable behavior.");
			$recommendations[] = "Consolidate into a single robots meta tag with all desired directives.";
		}

		$findings[] = array("type" => "info", "message" => "Robots Meta Content: {$robotsContent}");

		$directives = array_map("trim", explode(",", strtolower($robotsContent)));
		$findings[] = array("type" => "info", "message" => "Directives: " . implode(", ", $directives));

		$hasNoindex = in_array("noindex", $directives, true);
		$hasNofollow = in_array("nofollow", $directives, true);

		if ($hasNoindex) {
			$findings[] = array("type" => "bad", "message" => "NOINDEX directive found. This page will NOT appear in search engine results.");
			$recommendations[] = "Remove the noindex directive if this page should be indexed by search engines.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		if ($hasNofollow) {
			$findings[] = array("type" => "warning", "message" => "NOFOLLOW directive found. Search engines will not follow links on this page.");
			$recommendations[] = "Consider removing nofollow unless you intentionally want to prevent link equity from passing to linked pages.";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array("type" => "ok", "message" => "Robots meta directives allow indexing and link following.");

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}
}
