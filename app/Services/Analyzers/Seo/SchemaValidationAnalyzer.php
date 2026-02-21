<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

/**
 * Validates structured data fields against Google's rich result requirements.
 *
 * Goes beyond SchemaOrgAnalyzer (which checks presence/syntax) to verify that
 * each detected schema type contains the required and recommended fields for
 * Google rich result eligibility.
 */
class SchemaValidationAnalyzer implements AnalyzerInterface
{
	/** Maximum schema entities to validate per page */
	private const MAX_SCHEMA_ENTITIES = 20;

	/**
	 * Types handled by other dedicated modules — skip to avoid overlap.
	 * Organization subtypes are covered by EatBusinessSchemaAnalyzer.
	 * BreadcrumbList is covered by BreadcrumbAnalyzer.
	 */
	private const SKIPPED_TYPES = array(
		"Organization", "LocalBusiness", "Corporation", "GovernmentOrganization",
		"NGO", "EducationalOrganization", "MedicalOrganization", "SportsOrganization",
		"Restaurant", "Hotel", "Store", "FinancialService", "LegalService",
		"RealEstateAgent", "TravelAgency", "AutoDealer", "HomeAndConstructionBusiness",
		"LodgingBusiness", "MedicalBusiness", "ProfessionalService",
		"BreadcrumbList",
	);

	/**
	 * Subtype-to-parent mapping for validation rule lookup.
	 * Subtypes inherit the validation rules of their parent type.
	 */
	private const SUBTYPE_MAP = array(
		"NewsArticle" => "Article",
		"BlogPosting" => "Article",
		"TechArticle" => "Article",
		"ScholarlyArticle" => "Article",
		"BusinessEvent" => "Event",
		"ChildrensEvent" => "Event",
		"Festival" => "Event",
		"MusicEvent" => "Event",
		"SportsEvent" => "Event",
		"TheaterEvent" => "Event",
		"EducationEvent" => "Event",
		"ExhibitionEvent" => "Event",
	);

	/**
	 * Google rich result field requirements per schema type.
	 *
	 * Based on https://developers.google.com/search/docs/appearance/structured-data
	 * Each entry defines required fields, recommended fields, alternate field groups
	 * (where any one satisfies the requirement), and nested entity validation rules.
	 */
	/**
	 * Sources verified against Google Search Central (Feb 2026):
	 * - Article: https://developers.google.com/search/docs/appearance/structured-data/article
	 * - Product: https://developers.google.com/search/docs/appearance/structured-data/product-snippet
	 * - FAQPage: https://developers.google.com/search/docs/appearance/structured-data/faqpage
	 * - Event: https://developers.google.com/search/docs/appearance/structured-data/event
	 * - Recipe: https://developers.google.com/search/docs/appearance/structured-data/recipe
	 * - Video: https://developers.google.com/search/docs/appearance/structured-data/video
	 * - Review: https://developers.google.com/search/docs/appearance/structured-data/review-snippet
	 *
	 * Removed types (no longer generate rich results):
	 * - HowTo: removed by Google Sept 2023
	 * - WebSite/Sitelinks Search Box: removed by Google Nov 2022
	 */
	private const VALIDATION_RULES = array(
		/* Article — Google lists NO required fields; all recommended
		   Source: https://developers.google.com/search/docs/appearance/structured-data/article */
		"Article" => array(
			"label" => "Article",
			"required" => array(),
			"recommended" => array("headline", "image", "author", "datePublished", "dateModified"),
		),
		/* Product snippet — name required + one of review/aggregateRating/offers
		   Source: https://developers.google.com/search/docs/appearance/structured-data/product-snippet */
		"Product" => array(
			"label" => "Product",
			"required" => array("name", "offers"),
			"recommended" => array("aggregateRating", "review"),
			"alternateFields" => array(
				"offers" => array("offers", "review", "aggregateRating"),
			),
			"nestedValidation" => array(
				"offers" => array(
					"label" => "Offer",
					"required" => array("price"),
				),
			),
		),
		/* FAQPage — mainEntity required, each Question needs name + acceptedAnswer.text
		   Source: https://developers.google.com/search/docs/appearance/structured-data/faqpage */
		"FAQPage" => array(
			"label" => "FAQ Page",
			"required" => array("mainEntity"),
			"recommended" => array(),
			"nestedValidation" => array(
				"mainEntity" => array(
					"label" => "Question",
					"required" => array("name", "acceptedAnswer"),
					"childField" => "acceptedAnswer",
					"childRequired" => array("text"),
				),
			),
		),
		/* Event — name, startDate, location required
		   Source: https://developers.google.com/search/docs/appearance/structured-data/event */
		"Event" => array(
			"label" => "Event",
			"required" => array("name", "startDate", "location"),
			"recommended" => array("endDate", "image", "description", "eventStatus", "offers", "performer", "organizer", "previousStartDate"),
		),
		/* Recipe — name and image required
		   Source: https://developers.google.com/search/docs/appearance/structured-data/recipe */
		"Recipe" => array(
			"label" => "Recipe",
			"required" => array("name", "image"),
			"recommended" => array("author", "datePublished", "description", "prepTime", "cookTime", "totalTime", "recipeIngredient", "recipeInstructions", "recipeYield", "aggregateRating", "keywords", "recipeCategory", "recipeCuisine"),
		),
		/* VideoObject — name, thumbnailUrl, uploadDate required
		   Source: https://developers.google.com/search/docs/appearance/structured-data/video */
		"VideoObject" => array(
			"label" => "Video",
			"required" => array("name", "thumbnailUrl", "uploadDate"),
			"recommended" => array("description", "duration", "contentUrl", "embedUrl"),
		),
		/* Review — author, itemReviewed, reviewRating + ratingValue required
		   Source: https://developers.google.com/search/docs/appearance/structured-data/review-snippet */
		"Review" => array(
			"label" => "Review",
			"required" => array("author", "itemReviewed", "reviewRating"),
			"recommended" => array("datePublished"),
			"nestedValidation" => array(
				"reviewRating" => array(
					"label" => "Rating",
					"required" => array("ratingValue"),
				),
			),
		),
		/* AggregateRating — ratingValue + itemReviewed required, ratingCount OR reviewCount required
		   Source: https://developers.google.com/search/docs/appearance/structured-data/review-snippet */
		"AggregateRating" => array(
			"label" => "Aggregate Rating",
			"required" => array("ratingValue", "itemReviewed", "ratingCount"),
			"recommended" => array("bestRating", "worstRating"),
			"alternateFields" => array(
				"ratingCount" => array("ratingCount", "reviewCount"),
			),
		),
	);

	public function moduleKey(): string
	{
		return "schemaValidation";
	}

	public function label(): string
	{
		return "Schema Field Validation";
	}

	public function category(): string
	{
		return "Graphs, Schema & Links";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.schemaValidation", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$entities = $this->extractAllSchemaEntities($scanContext);

		if (empty($entities)) {
			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: array(array("type" => "info", "message" => "No structured data detected on this page. Schema markup enables Google rich results.")),
				recommendations: array(
					"Add JSON-LD structured data relevant to your content type (Article, Product, FAQ, Recipe, etc.).",
				),
			);
		}

		$validationResults = $this->validateAllEntities($entities);
		$findings = $this->buildFindings($validationResults, count($entities));
		$recommendations = $this->buildRecommendations($validationResults);
		$status = $this->determineOverallStatus($validationResults);

		return new AnalysisResult(
			status: $status,
			findings: $findings,
			recommendations: $recommendations,
		);
	}

	/**
	 * Extract all schema entities from JSON-LD blocks and Microdata on the page.
	 *
	 * @return array{type: string, data: array, format: string}[]
	 */
	private function extractAllSchemaEntities(ScanContext $scanContext): array
	{
		$entities = array();

		$jsonLdEntities = $this->extractJsonLdEntities($scanContext->xpath);
		$microdataEntities = $this->extractMicrodataEntities($scanContext->xpath);

		$entities = array_merge($jsonLdEntities, $microdataEntities);

		if (count($entities) > self::MAX_SCHEMA_ENTITIES) {
			$entities = array_slice($entities, 0, self::MAX_SCHEMA_ENTITIES);
		}

		return $entities;
	}

	/**
	 * Parse all JSON-LD script blocks and flatten into individual entities.
	 *
	 * @return array{type: string, data: array, format: string}[]
	 */
	private function extractJsonLdEntities(\DOMXPath $xpath): array
	{
		$scriptNodes = $xpath->query("//script[@type='application/ld+json']");

		if (!$scriptNodes || $scriptNodes->length === 0) {
			return array();
		}

		$entities = array();

		for ($index = 0; $index < $scriptNodes->length; $index++) {
			$scriptContent = trim($scriptNodes->item($index)->textContent);
			$decoded = json_decode($scriptContent, true);

			if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
				continue;
			}

			$flattened = $this->flattenJsonLdEntities($decoded);

			foreach ($flattened as $entity) {
				$entities[] = array(
					"type" => $entity["type"],
					"data" => $entity["data"],
					"format" => "JSON-LD",
				);
			}
		}

		return $entities;
	}

	/**
	 * Recursively flatten a JSON-LD structure into individual typed entities.
	 * Handles top-level @type, @graph arrays, and array @type values.
	 *
	 * @return array{type: string, data: array}[]
	 */
	private function flattenJsonLdEntities(array $decoded): array
	{
		$entities = array();

		if (isset($decoded["@graph"]) && is_array($decoded["@graph"])) {
			foreach ($decoded["@graph"] as $graphItem) {
				if (is_array($graphItem)) {
					$entities = array_merge($entities, $this->flattenJsonLdEntities($graphItem));
				}
			}

			return $entities;
		}

		if (isset($decoded["@type"])) {
			$rawType = $decoded["@type"];
			$typeNames = is_array($rawType) ? $rawType : array($rawType);

			foreach ($typeNames as $typeName) {
				if (is_string($typeName)) {
					$entities[] = array(
						"type" => $this->resolveSchemaTypeName($typeName),
						"data" => $decoded,
					);
				}
			}
		}

		return $entities;
	}

	/**
	 * Extract Microdata entities by finding elements with itemtype attributes.
	 *
	 * @return array{type: string, data: array, format: string}[]
	 */
	private function extractMicrodataEntities(\DOMXPath $xpath): array
	{
		$itemNodes = $xpath->query("//*[@itemtype]");

		if (!$itemNodes || $itemNodes->length === 0) {
			return array();
		}

		$entities = array();

		foreach ($itemNodes as $node) {
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$itemtype = $node->getAttribute("itemtype");
			$typeName = $this->resolveSchemaTypeName($itemtype);

			if ($typeName === "") {
				continue;
			}

			$properties = $this->extractMicrodataProperties($node, $xpath);

			$entities[] = array(
				"type" => $typeName,
				"data" => $properties,
				"format" => "Microdata",
			);
		}

		return $entities;
	}

	/**
	 * Extract itemprop key-value pairs from a Microdata-scoped element.
	 */
	private function extractMicrodataProperties(DOMElement $scopeNode, \DOMXPath $xpath): array
	{
		$properties = array();
		$propNodes = $xpath->query(".//*[@itemprop]", $scopeNode);

		if (!$propNodes) {
			return $properties;
		}

		foreach ($propNodes as $propNode) {
			if (!($propNode instanceof DOMElement)) {
				continue;
			}

			$propName = $propNode->getAttribute("itemprop");

			if ($propName === "") {
				continue;
			}

			/* Nested itemscope — store as sub-array to support nested validation */
			if ($propNode->hasAttribute("itemscope")) {
				$properties[$propName] = $this->extractMicrodataProperties($propNode, $xpath);
			} else {
				$propValue = $propNode->getAttribute("content")
					?: $propNode->getAttribute("href")
					?: $propNode->getAttribute("src")
					?: trim($propNode->textContent);

				$properties[$propName] = $propValue;
			}
		}

		return $properties;
	}

	/**
	 * Strip the schema.org URL prefix from a type string.
	 */
	private function resolveSchemaTypeName(string $rawType): string
	{
		$rawType = trim($rawType);
		$rawType = preg_replace('#^https?://schema\.org/#i', "", $rawType);

		return $rawType;
	}

	/**
	 * Match a schema type name to its validation rule key.
	 * Returns null for unrecognized or skipped types.
	 */
	private function matchValidationType(string $typeName): ?string
	{
		if (in_array($typeName, self::SKIPPED_TYPES, true)) {
			return null;
		}

		/* Direct match against validation rules */
		if (isset(self::VALIDATION_RULES[$typeName])) {
			return $typeName;
		}

		/* Subtype mapping (NewsArticle → Article, MusicEvent → Event, etc.) */
		if (isset(self::SUBTYPE_MAP[$typeName])) {
			return self::SUBTYPE_MAP[$typeName];
		}

		return null;
	}

	/**
	 * Validate all extracted entities and collect results.
	 *
	 * @return array{validationType: string, originalType: string, format: string, missingRequired: string[], missingRecommended: string[], nestedIssues: string[]}[]
	 */
	private function validateAllEntities(array $entities): array
	{
		$results = array();
		$recognizedCount = 0;
		$skippedCount = 0;

		foreach ($entities as $entity) {
			$typeName = $entity["type"];

			if (in_array($typeName, self::SKIPPED_TYPES, true)) {
				$skippedCount++;
				continue;
			}

			$validationType = $this->matchValidationType($typeName);

			if ($validationType === null) {
				continue;
			}

			$recognizedCount++;
			$rules = self::VALIDATION_RULES[$validationType];
			$entityData = $entity["data"];

			$missingRequired = $this->checkFields($entityData, $rules["required"] ?? array(), $rules["alternateFields"] ?? array());
			$missingRecommended = $this->checkFields($entityData, $rules["recommended"] ?? array(), $rules["alternateFields"] ?? array());
			$nestedIssues = $this->validateNestedEntities($validationType, $entityData);

			$results[] = array(
				"validationType" => $validationType,
				"originalType" => $typeName,
				"format" => $entity["format"],
				"label" => $rules["label"],
				"requiredFields" => $rules["required"] ?? array(),
				"recommendedFields" => $rules["recommended"] ?? array(),
				"missingRequired" => $missingRequired,
				"missingRecommended" => $missingRecommended,
				"nestedIssues" => $nestedIssues,
				"requiredTotal" => count($rules["required"] ?? array()),
				"recommendedTotal" => count($rules["recommended"] ?? array()),
			);
		}

		return $results;
	}

	/**
	 * Check which fields from a list are missing in the entity data.
	 *
	 * @return string[] Missing field names
	 */
	private function checkFields(array $entityData, array $fieldList, array $alternateFields): array
	{
		$missing = array();

		foreach ($fieldList as $fieldName) {
			/* If this field has alternates, any one of them satisfies the requirement */
			if (isset($alternateFields[$fieldName])) {
				$anyPresent = false;

				foreach ($alternateFields[$fieldName] as $altField) {
					if ($this->isFieldPresent($entityData, $altField)) {
						$anyPresent = true;
						break;
					}
				}

				if (!$anyPresent) {
					$altList = implode("/", $alternateFields[$fieldName]);
					$missing[] = $altList;
				}

				continue;
			}

			if (!$this->isFieldPresent($entityData, $fieldName)) {
				$missing[] = $fieldName;
			}
		}

		return $missing;
	}

	/**
	 * Validate nested entities within a parent schema (e.g., Offer inside Product).
	 *
	 * @return string[] Human-readable issue descriptions
	 */
	private function validateNestedEntities(string $validationType, array $entityData): array
	{
		$rules = self::VALIDATION_RULES[$validationType];
		$issues = array();

		if (!isset($rules["nestedValidation"])) {
			return $issues;
		}

		foreach ($rules["nestedValidation"] as $parentField => $nestedRules) {
			if (!$this->isFieldPresent($entityData, $parentField)) {
				continue;
			}

			$nestedData = $entityData[$parentField] ?? null;

			if (!is_array($nestedData)) {
				continue;
			}

			$nestedLabel = $nestedRules["label"];
			$nestedRequired = $nestedRules["required"] ?? array();

			/* Handle array of nested items (e.g., FAQPage mainEntity is an array of Questions) */
			$items = $this->isSequentialArray($nestedData) ? $nestedData : array($nestedData);

			foreach ($items as $itemIndex => $item) {
				if (!is_array($item)) {
					continue;
				}

				foreach ($nestedRequired as $requiredField) {
					if (!$this->isFieldPresent($item, $requiredField)) {
						$position = count($items) > 1 ? " #" . ($itemIndex + 1) : "";
						$issues[] = "{$nestedLabel}{$position} is missing required field \"{$requiredField}\"";
					}
				}

				/* Child field validation (e.g., FAQPage → Question → acceptedAnswer → text) */
				if (isset($nestedRules["childField"], $nestedRules["childRequired"])) {
					$childData = $item[$nestedRules["childField"]] ?? null;

					if (is_array($childData)) {
						foreach ($nestedRules["childRequired"] as $childField) {
							if (!$this->isFieldPresent($childData, $childField)) {
								$position = count($items) > 1 ? " #" . ($itemIndex + 1) : "";
								$issues[] = "{$nestedLabel}{$position} → {$nestedRules['childField']} is missing \"{$childField}\"";
							}
						}
					}
				}
			}
		}

		return $issues;
	}

	/**
	 * Check if a field exists and has a non-empty value in the entity data.
	 */
	private function isFieldPresent(array $data, string $fieldName): bool
	{
		if (!isset($data[$fieldName])) {
			return false;
		}

		$value = $data[$fieldName];

		if (is_string($value)) {
			return trim($value) !== "";
		}

		if (is_array($value)) {
			return !empty($value);
		}

		return $value !== null;
	}

	/**
	 * Check if an array is sequential (list) vs associative (object).
	 */
	private function isSequentialArray(array $array): bool
	{
		if (empty($array)) {
			return false;
		}

		return array_keys($array) === range(0, count($array) - 1);
	}

	/**
	 * Determine overall module status from validation results.
	 */
	private function determineOverallStatus(array $validationResults): ModuleStatus
	{
		if (empty($validationResults)) {
			return ModuleStatus::Info;
		}

		$hasMissingRequired = false;
		$hasMissingRecommended = false;
		$hasNestedIssues = false;

		foreach ($validationResults as $result) {
			if (!empty($result["missingRequired"])) {
				$hasMissingRequired = true;
			}
			if (!empty($result["missingRecommended"])) {
				$hasMissingRecommended = true;
			}
			if (!empty($result["nestedIssues"])) {
				$hasNestedIssues = true;
			}
		}

		if ($hasMissingRequired || $hasNestedIssues) {
			return ModuleStatus::Bad;
		}

		if ($hasMissingRecommended) {
			return ModuleStatus::Warning;
		}

		return ModuleStatus::Ok;
	}

	/**
	 * Build findings from validation results.
	 */
	private function buildFindings(array $validationResults, int $totalEntities): array
	{
		$findings = array();
		$recognizedCount = count($validationResults);

		/* Summary */
		$summaryMessage = "Validated {$recognizedCount} recognized schema type(s)";

		if ($totalEntities > $recognizedCount) {
			$otherCount = $totalEntities - $recognizedCount;
			$summaryMessage .= " out of {$totalEntities} total ({$otherCount} skipped or unrecognized)";
		}

		if ($totalEntities > self::MAX_SCHEMA_ENTITIES) {
			$summaryMessage .= ". Analysis limited to first " . self::MAX_SCHEMA_ENTITIES . " entities";
		}

		$findings[] = array("type" => "info", "message" => $summaryMessage . ".");

		if (empty($validationResults)) {
			$findings[] = array("type" => "info", "message" => "No recognized rich result schema types found to validate. Detected schemas may be handled by other modules or are not eligible for Google rich results.");
			return $findings;
		}

		/* Per-type validation findings */
		$allValid = true;
		$validationSummary = array();

		foreach ($validationResults as $result) {
			$typeLabel = $result["label"];
			$originalType = $result["originalType"];
			$displayType = $originalType !== $result["validationType"] ? "{$originalType} ({$typeLabel})" : $typeLabel;

			$requiredMissing = $result["missingRequired"];
			$recommendedMissing = $result["missingRecommended"];
			$nestedIssues = $result["nestedIssues"];

			if (empty($requiredMissing) && empty($recommendedMissing) && empty($nestedIssues)) {
				$findings[] = array(
					"type" => "ok",
					"message" => "{$displayType}: All required and recommended fields present. Eligible for Google rich results.",
				);
			} else {
				$allValid = false;

				if (!empty($requiredMissing)) {
					$findings[] = array(
						"type" => "bad",
						"message" => "{$displayType}: Missing required field(s): " . implode(", ", $requiredMissing) . ". Rich result will NOT be generated without these.",
					);
				}

				if (!empty($nestedIssues)) {
					foreach ($nestedIssues as $nestedIssue) {
						$findings[] = array(
							"type" => "bad",
							"message" => "{$displayType}: {$nestedIssue}.",
						);
					}
				}

				if (!empty($recommendedMissing)) {
					$findings[] = array(
						"type" => "warning",
						"message" => "{$displayType}: Missing recommended field(s): " . implode(", ", $recommendedMissing) . ". Adding these improves rich result appearance.",
					);
				}
			}

			/* Collect structured data for UI */
			$validationSummary[] = array(
				"type" => $originalType,
				"validationType" => $result["validationType"],
				"format" => $result["format"],
				"requiredFields" => $result["requiredFields"],
				"recommendedFields" => $result["recommendedFields"],
				"missingRequired" => $requiredMissing,
				"missingRecommended" => $recommendedMissing,
				"nestedIssues" => $nestedIssues,
				"requiredTotal" => $result["requiredTotal"],
				"recommendedTotal" => $result["recommendedTotal"],
				"requiredPresent" => $result["requiredTotal"] - count($requiredMissing),
				"status" => !empty($requiredMissing) || !empty($nestedIssues) ? "bad" : (!empty($recommendedMissing) ? "warning" : "ok"),
			);
		}

		if ($allValid) {
			$findings[] = array("type" => "ok", "message" => "All detected schemas pass Google's field requirements for rich result eligibility.");
		}

		if (!empty($validationSummary)) {
			$findings[] = array("type" => "data", "key" => "schemaValidation", "value" => $validationSummary);
		}

		return $findings;
	}

	/**
	 * Build actionable recommendations based on validation results.
	 */
	private function buildRecommendations(array $validationResults): array
	{
		$recommendations = array();
		$typesWithRequiredIssues = array();
		$typesWithRecommendedIssues = array();
		$typesWithNestedIssues = array();

		foreach ($validationResults as $result) {
			$label = $result["label"];

			if (!empty($result["missingRequired"])) {
				$typesWithRequiredIssues[] = $label;
				$fieldList = implode(", ", $result["missingRequired"]);
				$recommendations[] = "Add the required field(s) ({$fieldList}) to your {$label} schema. Without these, Google cannot generate a rich result.";
			}

			if (!empty($result["nestedIssues"])) {
				$typesWithNestedIssues[] = $label;
			}

			if (!empty($result["missingRecommended"])) {
				$typesWithRecommendedIssues[] = $label;
			}
		}

		if (!empty($typesWithNestedIssues)) {
			$recommendations[] = "Fix nested schema structure issues. Each child entity (e.g., Offer within Product, Question within FAQPage) must include its own required fields.";
		}

		if (!empty($typesWithRecommendedIssues) && empty($typesWithRequiredIssues)) {
			$recommendations[] = "Consider adding recommended fields to enhance your rich result appearance. While not required, they provide richer information to search users.";
		}

		if (!empty($typesWithRequiredIssues)) {
			$recommendations[] = "Use Google's Rich Results Test (https://search.google.com/test/rich-results) to preview how your structured data will appear in search results.";
		}

		return $recommendations;
	}
}
