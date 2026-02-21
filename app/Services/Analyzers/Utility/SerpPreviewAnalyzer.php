<?php

namespace App\Services\Analyzers\Utility;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class SerpPreviewAnalyzer implements AnalyzerInterface
{
	private const TITLE_PIXEL_LIMIT = 580;
	private const DESCRIPTION_CHAR_LIMIT = 160;

	public function moduleKey(): string
	{
		return "serpPreview";
	}

	public function label(): string
	{
		return "SERP Preview";
	}

	public function category(): string
	{
		return "Extras";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.serpPreview", 0);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$effectiveUrl = $scanContext->effectiveUrl;
		$titleContent = $scanContext->titleContent;
		$metaDescription = $scanContext->metaDescriptionContent;
		$findings = array();

		$serpTitle = $titleContent ?? $effectiveUrl;
		$serpUrl = $this->formatDisplayUrl($effectiveUrl);
		$serpDescription = $metaDescription ?? "";

		if (mb_strlen($serpDescription) > self::DESCRIPTION_CHAR_LIMIT) {
			$serpDescription = mb_substr($serpDescription, 0, self::DESCRIPTION_CHAR_LIMIT - 3) . "...";
		}

		$findings[] = array("type" => "data", "key" => "serpPreview", "value" => array(
			"title" => $serpTitle,
			"url" => $serpUrl,
			"description" => $serpDescription,
		));

		$titleLength = mb_strlen($serpTitle);
		$isTitleTruncated = $titleLength > 60;

		if ($isTitleTruncated) {
			$findings[] = array("type" => "info", "message" => "The title tag ({$titleLength} chars) may be truncated in search results.");
		}

		if ($metaDescription === null || $metaDescription === "") {
			$findings[] = array("type" => "info", "message" => "No meta description set — search engines will auto-generate a snippet.");
		}

		return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: array());
	}

	/**
	 * Format a URL for SERP display (show breadcrumb-style path).
	 */
	private function formatDisplayUrl(string $effectiveUrl): string
	{
		$parsed = parse_url($effectiveUrl);
		$displayUrl = ($parsed["host"] ?? "") . ($parsed["path"] ?? "");

		return rtrim($displayUrl, "/");
	}
}
