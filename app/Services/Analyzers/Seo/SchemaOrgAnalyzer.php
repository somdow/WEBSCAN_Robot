<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class SchemaOrgAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "schemaOrg";
	}

	public function label(): string
	{
		return "Schema.org Structured Data";
	}

	public function category(): string
	{
		return "Graphs, Schema & Links";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.schemaOrg", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$findings = array();
		$recommendations = array();

		$scriptNodes = $xpath->query("//script[@type='application/ld+json']");
		$schemaCount = $scriptNodes ? $scriptNodes->length : 0;

		if ($schemaCount === 0) {
			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: array(array("type" => "warning", "message" => "No JSON-LD structured data found. Schema markup helps search engines understand your content and enables rich results.")),
				recommendations: array(
					"Add JSON-LD structured data relevant to your content type (Organization, LocalBusiness, Article, Product, FAQ, etc.).",
					"Use Google's Structured Data Markup Helper to generate the correct JSON-LD.",
				),
			);
		}

		$validSchemas = 0;
		$schemaTypes = array();

		for ($index = 0; $index < $schemaCount; $index++) {
			$scriptContent = trim($scriptNodes->item($index)->textContent);
			$decoded = json_decode($scriptContent, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				$findings[] = array("type" => "warning", "message" => "Schema block " . ($index + 1) . " contains invalid JSON: " . json_last_error_msg());
				continue;
			}

			$validSchemas++;
			$types = $this->extractSchemaTypes($decoded);

			foreach ($types as $type) {
				$schemaTypes[] = $type;
			}
		}

		$findings[] = array("type" => "info", "message" => "Found {$schemaCount} JSON-LD block(s), {$validSchemas} valid.");

		if (!empty($schemaTypes)) {
			$uniqueTypes = array_unique($schemaTypes);
			$findings[] = array("type" => "info", "message" => "Schema types detected: " . implode(", ", $uniqueTypes));
			$findings[] = array("type" => "data", "key" => "schemaTypes", "value" => array_values($uniqueTypes));
		}

		if ($validSchemas === 0) {
			$findings[] = array("type" => "bad", "message" => "All JSON-LD blocks contain invalid JSON.");
			$recommendations[] = "Fix the JSON syntax errors in your structured data. Use the Google Rich Results Test to validate.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		$findings[] = array("type" => "ok", "message" => "Valid structured data found with {$validSchemas} schema block(s).");

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Recursively extract @type values from decoded JSON-LD
	 */
	private function extractSchemaTypes(array $schemaData): array
	{
		$types = array();

		if (isset($schemaData["@type"])) {
			$type = $schemaData["@type"];
			$types = is_array($type) ? $type : array($type);
		}

		if (isset($schemaData["@graph"]) && is_array($schemaData["@graph"])) {
			foreach ($schemaData["@graph"] as $item) {
				if (is_array($item)) {
					$types = array_merge($types, $this->extractSchemaTypes($item));
				}
			}
		}

		return $types;
	}
}
