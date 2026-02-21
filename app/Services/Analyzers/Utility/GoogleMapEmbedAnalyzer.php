<?php

namespace App\Services\Analyzers\Utility;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class GoogleMapEmbedAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "googleMapEmbed";
	}

	public function label(): string
	{
		return "Google Map Embed";
	}

	public function category(): string
	{
		return "Extras";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.googleMapEmbed", 2);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$iframeNodes = $xpath->query("//iframe[contains(@src, 'google.com/maps')]");
		$mapEmbedCount = $iframeNodes ? $iframeNodes->length : 0;

		if ($mapEmbedCount > 0) {
			return new AnalysisResult(
				status: ModuleStatus::Ok,
				findings: array(
					array("type" => "ok", "message" => "Google Map embed detected ({$mapEmbedCount} iframe(s))."),
				),
				recommendations: array(),
			);
		}

		return new AnalysisResult(
			status: ModuleStatus::Warning,
			findings: array(
				array("type" => "warning", "message" => "No Google Map embed found on this page."),
			),
			recommendations: array(
				"Consider embedding a Google Map to boost local trust signals and help visitors find your location.",
			),
		);
	}
}
