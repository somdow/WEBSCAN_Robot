<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class ViewportTagAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "viewportTag";
	}

	public function label(): string
	{
		return "Viewport Tag";
	}

	public function category(): string
	{
		return "Usability & Performance";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.viewportTag", 8);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$viewportContents = $scanContext->viewportContents;
		$tagCount = $scanContext->viewportTagCount;
		$findings = array();
		$recommendations = array();

		if ($tagCount === 0) {
			return new AnalysisResult(
				status: ModuleStatus::Bad,
				findings: array(array("type" => "bad", "message" => "Missing viewport meta tag. This is essential for mobile-responsive rendering and mobile-first indexing.")),
				recommendations: array("Add <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"> to the <head> section."),
			);
		}

		if ($tagCount > 1) {
			$findings[] = array("type" => "warning", "message" => "Multiple viewport tags found ({$tagCount}). Only one should exist.");
			$recommendations[] = "Remove duplicate viewport meta tags.";
		}

		$content = $viewportContents[0] ?? "";
		$findings[] = array("type" => "info", "message" => "Viewport Content: {$content}");

		$directives = $this->parseViewportDirectives($content);
		$status = ModuleStatus::Ok;

		if (!isset($directives["width"]) || $directives["width"] !== "device-width") {
			$findings[] = array("type" => "warning", "message" => "Viewport width is not set to \"device-width\". This may cause rendering issues on mobile devices.");
			$recommendations[] = "Set width=device-width in the viewport meta tag.";
			$status = ModuleStatus::Warning;
		}

		if (isset($directives["user-scalable"]) && strtolower($directives["user-scalable"]) === "no") {
			$findings[] = array("type" => "warning", "message" => "user-scalable=no prevents users from zooming. This hurts accessibility.");
			$recommendations[] = "Remove user-scalable=no to allow users to zoom the page for accessibility.";
			$status = ModuleStatus::Warning;
		}

		if (isset($directives["maximum-scale"]) && (float) $directives["maximum-scale"] < 2.0) {
			$findings[] = array("type" => "warning", "message" => "maximum-scale is set below 2.0, limiting zoom. This can hurt accessibility.");
			$recommendations[] = "Set maximum-scale to at least 2.0 or remove it entirely.";
			$status = ModuleStatus::Warning;
		}

		if ($status === ModuleStatus::Ok) {
			$findings[] = array("type" => "ok", "message" => "Viewport tag is properly configured for mobile responsiveness.");
		}

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Parse viewport content string into key=value pairs
	 */
	private function parseViewportDirectives(string $content): array
	{
		$directives = array();
		$parts = preg_split("/[,;]/", $content);

		foreach ($parts as $part) {
			$part = trim($part);
			if (str_contains($part, "=")) {
				list($key, $value) = explode("=", $part, 2);
				$directives[strtolower(trim($key))] = trim($value);
			}
		}

		return $directives;
	}
}
