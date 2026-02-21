<?php

namespace App\Services\Analyzers\Security;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Detects mixed content — HTTP resources loaded on HTTPS pages.
 *
 * Per-page scope — different pages may embed different external resources.
 * Mixed content triggers browser warnings, breaks padlock indicators,
 * and undermines the HTTPS ranking signal.
 */
class MixedContentAnalyzer implements AnalyzerInterface
{
	/**
	 * DOM elements and their resource-bearing attributes to check.
	 * Each entry maps an XPath expression to a human-readable element label.
	 */
	private const RESOURCE_QUERIES = array(
		array("xpath" => "//script[@src]", "attribute" => "src", "label" => "Script"),
		array("xpath" => "//link[@href][@rel='stylesheet']", "attribute" => "href", "label" => "Stylesheet"),
		array("xpath" => "//img[@src]", "attribute" => "src", "label" => "Image"),
		array("xpath" => "//iframe[@src]", "attribute" => "src", "label" => "Iframe"),
		array("xpath" => "//source[@src]", "attribute" => "src", "label" => "Media source"),
		array("xpath" => "//video[@src]", "attribute" => "src", "label" => "Video"),
		array("xpath" => "//audio[@src]", "attribute" => "src", "label" => "Audio"),
		array("xpath" => "//object[@data]", "attribute" => "data", "label" => "Object"),
		array("xpath" => "//form[@action]", "attribute" => "action", "label" => "Form action"),
	);

	/** Maximum number of individual mixed content items to report in findings. */
	private const MAX_REPORTED_ITEMS = 20;

	public function moduleKey(): string
	{
		return "mixedContent";
	}

	public function label(): string
	{
		return "Mixed Content Detection";
	}

	public function category(): string
	{
		return "Security";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.mixedContent", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		/* Only meaningful on HTTPS pages */
		$parsedUrl = parse_url($scanContext->effectiveUrl);
		$scheme = strtolower($parsedUrl["scheme"] ?? "");

		if ($scheme !== "https") {
			return $this->buildHttpPageResult();
		}

		$mixedResources = $this->findMixedContent($scanContext);
		$mixedCount = count($mixedResources);

		$findings = $this->buildFindings($mixedResources);
		$recommendations = $this->buildRecommendations($mixedCount);

		$status = match (true) {
			$mixedCount === 0 => ModuleStatus::Ok,
			$mixedCount <= 3 => ModuleStatus::Warning,
			default => ModuleStatus::Bad,
		};

		return new AnalysisResult(
			status: $status,
			findings: $findings,
			recommendations: $recommendations,
		);
	}

	/**
	 * Return an Ok result for HTTP pages where mixed content is not applicable.
	 */
	private function buildHttpPageResult(): AnalysisResult
	{
		return new AnalysisResult(
			status: ModuleStatus::Ok,
			findings: array(
				array(
					"type" => "info",
					"message" => "This page is served over HTTP — mixed content checking only applies to HTTPS pages.",
				),
			),
			recommendations: array(),
		);
	}

	/**
	 * Build the findings array from detected mixed content resources.
	 *
	 * @param array<int, array{label: string, url: string}> $mixedResources
	 */
	private function buildFindings(array $mixedResources): array
	{
		$mixedCount = count($mixedResources);

		$findings = array(
			array("type" => "data", "key" => "mixedContentCount", "value" => $mixedCount),
		);

		if ($mixedCount === 0) {
			$findings[] = array(
				"type" => "ok",
				"message" => "No mixed content detected — all resources are loaded over HTTPS.",
			);

			return $findings;
		}

		$reportedResources = array_slice($mixedResources, 0, self::MAX_REPORTED_ITEMS);

		foreach ($reportedResources as $mixedResource) {
			$truncatedUrl = mb_strlen($mixedResource["url"]) > 100
				? mb_substr($mixedResource["url"], 0, 97) . "..."
				: $mixedResource["url"];

			$findings[] = array(
				"type" => "bad",
				"message" => "{$mixedResource['label']}: {$truncatedUrl}",
			);
		}

		if ($mixedCount > self::MAX_REPORTED_ITEMS) {
			$remaining = $mixedCount - self::MAX_REPORTED_ITEMS;
			$findings[] = array(
				"type" => "info",
				"message" => "...and {$remaining} more mixed content " . ($remaining === 1 ? "resource" : "resources") . " not shown.",
			);
		}

		return $findings;
	}

	/**
	 * Build recommendations based on the number of mixed content items found.
	 */
	private function buildRecommendations(int $mixedCount): array
	{
		if ($mixedCount === 0) {
			return array();
		}

		$recommendations = array(
			"Update all resource URLs to use HTTPS instead of HTTP. Most modern CDNs and services support HTTPS.",
			"If you control the resources, ensure your server supports HTTPS and update the references. For third-party resources, check if an HTTPS version is available.",
		);

		if ($mixedCount > 3) {
			$recommendations[] = "Consider adding a Content-Security-Policy header with upgrade-insecure-requests to automatically upgrade HTTP requests to HTTPS: Content-Security-Policy: upgrade-insecure-requests";
		}

		return $recommendations;
	}

	/**
	 * Query the DOM for all resources loaded over HTTP.
	 *
	 * @return array<int, array{label: string, url: string}>
	 */
	private function findMixedContent(ScanContext $scanContext): array
	{
		$mixedResources = array();

		foreach (self::RESOURCE_QUERIES as $query) {
			$nodes = $scanContext->xpath->query($query["xpath"]);

			if ($nodes === false) {
				continue;
			}

			foreach ($nodes as $node) {
				$attributeValue = trim($node->getAttribute($query["attribute"]));

				if ($attributeValue === "") {
					continue;
				}

				/* Only flag explicit http:// URLs — skip relative, protocol-relative, data:, javascript: */
				if (stripos($attributeValue, "http://") === 0) {
					$mixedResources[] = array(
						"label" => $query["label"],
						"url" => $attributeValue,
					);
				}
			}
		}

		return $mixedResources;
	}
}
