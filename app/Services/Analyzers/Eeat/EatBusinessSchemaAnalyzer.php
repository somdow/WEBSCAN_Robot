<?php

namespace App\Services\Analyzers\Eeat;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Checks for Organization or LocalBusiness JSON-LD structured data
 * and evaluates the presence of required and recommended fields.
 */
class EatBusinessSchemaAnalyzer implements AnalyzerInterface
{
	private const ORGANIZATION_TYPES = array(
		"Organization", "LocalBusiness", "Corporation", "GovernmentOrganization",
		"NGO", "EducationalOrganization", "MedicalOrganization", "SportsOrganization",
		"Restaurant", "Hotel", "Store", "FinancialService", "LegalService",
		"RealEstateAgent", "TravelAgency", "AutoDealer", "HomeAndConstructionBusiness",
		"LodgingBusiness", "MedicalBusiness", "ProfessionalService",
	);

	private const RECOMMENDED_FIELDS = array("address", "telephone", "logo", "sameAs", "foundingDate");

	public function moduleKey(): string
	{
		return "eatBusinessSchema";
	}

	public function label(): string
	{
		return "Business Schema";
	}

	public function category(): string
	{
		return "E-E-A-T Signals";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.eatBusinessSchema", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$scriptNodes = $scanContext->xpath->query("//script[@type='application/ld+json']");
		if ($scriptNodes === false || $scriptNodes->length === 0) {
			return $this->buildNoSchemaResult();
		}

		$bestMatch = null;
		$bestRecommendedCount = -1;

		for ($i = 0; $i < $scriptNodes->length; $i++) {
			$decoded = json_decode(trim($scriptNodes->item($i)->textContent), true);
			if (!is_array($decoded)) {
				continue;
			}

			$orgSchema = $this->findOrganizationSchema($decoded);
			if ($orgSchema === null) {
				continue;
			}

			$recommendedCount = $this->countRecommendedFields($orgSchema);
			if ($recommendedCount > $bestRecommendedCount) {
				$bestMatch = $orgSchema;
				$bestRecommendedCount = $recommendedCount;
			}
		}

		if ($bestMatch === null) {
			return $this->buildNoSchemaResult();
		}

		return $this->evaluateOrganizationSchema($bestMatch, $bestRecommendedCount);
	}

	/**
	 * Recursively search JSON-LD for an Organization or LocalBusiness type.
	 */
	private function findOrganizationSchema(array $schemaData): ?array
	{
		$type = $schemaData["@type"] ?? null;
		if ($type !== null && $this->isOrganizationType($type)) {
			return $schemaData;
		}

		if (isset($schemaData["@graph"]) && is_array($schemaData["@graph"])) {
			foreach ($schemaData["@graph"] as $item) {
				if (!is_array($item)) {
					continue;
				}

				$result = $this->findOrganizationSchema($item);
				if ($result !== null) {
					return $result;
				}
			}
		}

		return null;
	}

	/**
	 * Check if the @type matches any known Organization subtype.
	 */
	private function isOrganizationType(mixed $type): bool
	{
		if (is_string($type)) {
			return in_array($type, self::ORGANIZATION_TYPES, true);
		}

		if (is_array($type)) {
			foreach ($type as $singleType) {
				if (in_array($singleType, self::ORGANIZATION_TYPES, true)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Count how many recommended fields are present in the schema.
	 */
	private function countRecommendedFields(array $orgSchema): int
	{
		$count = 0;

		foreach (self::RECOMMENDED_FIELDS as $field) {
			if (isset($orgSchema[$field]) && $orgSchema[$field] !== "" && $orgSchema[$field] !== array()) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Evaluate the found Organization schema and build the result.
	 */
	private function evaluateOrganizationSchema(array $orgSchema, int $recommendedCount): AnalysisResult
	{
		$findings = array();
		$recommendations = array();

		$schemaType = $orgSchema["@type"] ?? "Organization";
		if (is_array($schemaType)) {
			$schemaType = implode(", ", $schemaType);
		}

		$hasName = isset($orgSchema["name"]) && $orgSchema["name"] !== "";
		$hasUrl = isset($orgSchema["url"]) && $orgSchema["url"] !== "";

		$findings[] = array("type" => "info", "message" => "Found {$schemaType} structured data.");

		if (!$hasName) {
			$findings[] = array("type" => "warning", "message" => "Organization schema is missing the \"name\" field.");
			$recommendations[] = "Add a \"name\" field to your Organization schema.";
		}

		if (!$hasUrl) {
			$findings[] = array("type" => "warning", "message" => "Organization schema is missing the \"url\" field.");
			$recommendations[] = "Add a \"url\" field to your Organization schema.";
		}

		$presentFields = array();
		$missingFields = array();

		foreach (self::RECOMMENDED_FIELDS as $field) {
			if (isset($orgSchema[$field]) && $orgSchema[$field] !== "" && $orgSchema[$field] !== array()) {
				$presentFields[] = $field;
			} else {
				$missingFields[] = $field;
			}
		}

		if (!empty($presentFields)) {
			$findings[] = array("type" => "ok", "message" => "Recommended fields present: " . implode(", ", $presentFields) . ".");
		}

		if (!empty($missingFields)) {
			$findings[] = array("type" => "info", "message" => "Optional fields missing: " . implode(", ", $missingFields) . ".");
		}

		if ($hasName && $hasUrl && $recommendedCount >= 3) {
			$findings[] = array("type" => "ok", "message" => "Strong business schema with {$recommendedCount} recommended fields. This helps establish E-E-A-T credibility.");
			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		if ($hasName && $hasUrl) {
			$recommendations[] = "Add more recommended fields (address, telephone, logo, sameAs) to strengthen your Organization schema.";
			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$recommendations[] = "Ensure your Organization schema includes at minimum the \"name\" and \"url\" fields.";

		return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Build the result when no Organization/LocalBusiness schema is found.
	 */
	private function buildNoSchemaResult(): AnalysisResult
	{
		return new AnalysisResult(
			status: ModuleStatus::Bad,
			findings: array(
				array("type" => "bad", "message" => "No Organization or LocalBusiness structured data found. This schema type helps search engines identify and verify your business."),
			),
			recommendations: array(
				"Add JSON-LD structured data with @type \"Organization\" or \"LocalBusiness\" including name, url, logo, address, and telephone.",
				"Include a \"sameAs\" array with links to your social media profiles to connect your web presence.",
			),
		);
	}
}
