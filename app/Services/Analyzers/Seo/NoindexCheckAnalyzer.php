<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class NoindexCheckAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "noindexCheck";
	}

	public function label(): string
	{
		return "Noindex Check";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.noindexCheck", 10);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$robotsMetaContent = $scanContext->robotsMetaContent;
		$headers = $scanContext->responseHeaders;
		$findings = array();

		$metaNoindex = false;
		if ($robotsMetaContent !== null) {
			$directives = array_map("trim", explode(",", strtolower($robotsMetaContent)));
			$metaNoindex = in_array("noindex", $directives, true);
		}

		$headerNoindex = false;
		$xRobotsTag = $headers["x-robots-tag"] ?? null;
		if ($xRobotsTag !== null) {
			$headerValue = is_array($xRobotsTag) ? implode(", ", $xRobotsTag) : $xRobotsTag;
			$headerNoindex = stripos($headerValue, "noindex") !== false;
		}

		if ($metaNoindex) {
			$findings[] = array("type" => "bad", "message" => "NOINDEX found in robots meta tag. This page will NOT be indexed by search engines.");
		}

		if ($headerNoindex) {
			$findings[] = array("type" => "bad", "message" => "NOINDEX found in X-Robots-Tag HTTP header. This page will NOT be indexed by search engines.");
		}

		if ($metaNoindex || $headerNoindex) {
			return new AnalysisResult(
				status: ModuleStatus::Bad,
				findings: $findings,
				recommendations: array("Remove the noindex directive if this page should appear in search results."),
			);
		}

		return new AnalysisResult(
			status: ModuleStatus::Ok,
			findings: array(array("type" => "ok", "message" => "No noindex directives found. This page is allowed to be indexed.")),
			recommendations: array(),
		);
	}
}
