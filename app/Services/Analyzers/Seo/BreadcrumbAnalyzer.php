<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

/**
 * Detects breadcrumb navigation in HTML and BreadcrumbList schema markup.
 * Breadcrumbs improve user navigation and enable breadcrumb-style rich results in SERPs.
 */
class BreadcrumbAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "breadcrumbs";
	}

	public function label(): string
	{
		return "Breadcrumbs";
	}

	public function category(): string
	{
		return "On-Page SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.breadcrumbs", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$htmlContent = $scanContext->htmlContent;
		$findings = array();
		$recommendations = array();

		$htmlBreadcrumb = $this->detectHtmlBreadcrumb($xpath);
		$schemaBreadcrumb = $this->detectBreadcrumbSchema($htmlContent);

		if ($htmlBreadcrumb["found"]) {
			$findings[] = array(
				"type" => "ok",
				"message" => "HTML breadcrumb navigation detected ({$htmlBreadcrumb["method"]}). {$htmlBreadcrumb["itemCount"]} breadcrumb item(s) found.",
			);

			if ($htmlBreadcrumb["hasAriaLabel"]) {
				$findings[] = array("type" => "ok", "message" => "Breadcrumb nav has ARIA label for accessibility.");
			} else {
				$findings[] = array("type" => "info", "message" => "Consider adding aria-label=\"Breadcrumb\" to the breadcrumb <nav> for screen readers.");
			}
		} else {
			$findings[] = array("type" => "warning", "message" => "No HTML breadcrumb navigation detected.");
			$recommendations[] = "Add a visible breadcrumb trail (e.g., Home > Category > Page) using a <nav> element with an ordered list. This helps users navigate and improves crawlability.";
		}

		if ($schemaBreadcrumb["found"]) {
			$findings[] = array(
				"type" => "ok",
				"message" => "BreadcrumbList schema markup found ({$schemaBreadcrumb["format"]}). This enables breadcrumb-style rich results in Google SERPs.",
			);

			if ($schemaBreadcrumb["itemCount"] > 0) {
				$findings[] = array("type" => "data", "message" => "Schema breadcrumb items: {$schemaBreadcrumb["itemCount"]}");
			}
		} else {
			$findings[] = array("type" => "warning", "message" => "No BreadcrumbList schema markup found.");
			$recommendations[] = "Add BreadcrumbList JSON-LD structured data to enable breadcrumb rich results in search. Google displays these as clickable paths beneath your page title.";
		}

		$status = $this->determineStatus($htmlBreadcrumb["found"], $schemaBreadcrumb["found"]);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Detect HTML breadcrumb patterns: nav[aria-label*=breadcrumb], .breadcrumb,
	 * [itemtype*=BreadcrumbList], or ol/ul inside a breadcrumb-like container.
	 */
	private function detectHtmlBreadcrumb(\DOMXPath $xpath): array
	{
		$result = array("found" => false, "method" => null, "itemCount" => 0, "hasAriaLabel" => false);

		$ariaNav = $xpath->query("//nav[contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'breadcrumb')]");
		if ($ariaNav && $ariaNav->length > 0) {
			$result["found"] = true;
			$result["method"] = "nav[aria-label]";
			$result["hasAriaLabel"] = true;
			$result["itemCount"] = $this->countListItems($xpath, $ariaNav->item(0));
			return $result;
		}

		$microdataList = $xpath->query("//*[contains(@itemtype, 'BreadcrumbList')]");
		if ($microdataList && $microdataList->length > 0) {
			$result["found"] = true;
			$result["method"] = "Microdata BreadcrumbList";
			$items = $xpath->query("//*[contains(@itemtype, 'ListItem')]", $microdataList->item(0));
			$result["itemCount"] = $items ? $items->length : 0;
			return $result;
		}

		$classBreadcrumb = $xpath->query("//*[contains(translate(@class, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'breadcrumb')]");
		if ($classBreadcrumb && $classBreadcrumb->length > 0) {
			$result["found"] = true;
			$result["method"] = "CSS class pattern";
			$result["itemCount"] = $this->countListItems($xpath, $classBreadcrumb->item(0));

			$parentNav = $xpath->query("ancestor::nav", $classBreadcrumb->item(0));
			if ($parentNav && $parentNav->length > 0) {
				$navElement = $parentNav->item(0);
				$result["hasAriaLabel"] = $navElement->hasAttribute("aria-label");
			}

			return $result;
		}

		$idBreadcrumb = $xpath->query("//*[contains(translate(@id, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'breadcrumb')]");
		if ($idBreadcrumb && $idBreadcrumb->length > 0) {
			$result["found"] = true;
			$result["method"] = "ID pattern";
			$result["itemCount"] = $this->countListItems($xpath, $idBreadcrumb->item(0));
			return $result;
		}

		return $result;
	}

	/**
	 * Detect BreadcrumbList structured data in JSON-LD or Microdata format.
	 */
	private function detectBreadcrumbSchema(string $htmlContent): array
	{
		$result = array("found" => false, "format" => null, "itemCount" => 0);

		if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $htmlContent, $matches)) {
			foreach ($matches[1] as $jsonBlock) {
				$decoded = json_decode(trim($jsonBlock), true);

				if ($decoded === null) {
					continue;
				}

				if ($this->containsBreadcrumbList($decoded)) {
					$result["found"] = true;
					$result["format"] = "JSON-LD";
					$result["itemCount"] = $this->countSchemaItems($decoded);
					return $result;
				}
			}
		}

		if (stripos($htmlContent, "BreadcrumbList") !== false && stripos($htmlContent, "itemtype") !== false) {
			$result["found"] = true;
			$result["format"] = "Microdata";
			$result["itemCount"] = substr_count(strtolower($htmlContent), "listitem");
			return $result;
		}

		return $result;
	}

	/**
	 * Recursively check if a JSON-LD structure contains BreadcrumbList.
	 */
	private function containsBreadcrumbList(array $data): bool
	{
		$type = $data["@type"] ?? null;

		if ($type === "BreadcrumbList") {
			return true;
		}

		if (isset($data["@graph"]) && is_array($data["@graph"])) {
			foreach ($data["@graph"] as $item) {
				if (is_array($item) && ($item["@type"] ?? null) === "BreadcrumbList") {
					return true;
				}
			}
		}

		if (array_is_list($data)) {
			foreach ($data as $item) {
				if (is_array($item) && $this->containsBreadcrumbList($item)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Count itemListElement entries in a BreadcrumbList schema.
	 */
	private function countSchemaItems(array $data): int
	{
		if (($data["@type"] ?? null) === "BreadcrumbList") {
			return count($data["itemListElement"] ?? array());
		}

		if (isset($data["@graph"]) && is_array($data["@graph"])) {
			foreach ($data["@graph"] as $item) {
				if (is_array($item) && ($item["@type"] ?? null) === "BreadcrumbList") {
					return count($item["itemListElement"] ?? array());
				}
			}
		}

		return 0;
	}

	/**
	 * Count <li> items within a breadcrumb container element.
	 */
	private function countListItems(\DOMXPath $xpath, \DOMNode $container): int
	{
		$items = $xpath->query(".//li", $container);

		if ($items && $items->length > 0) {
			return $items->length;
		}

		$links = $xpath->query(".//a", $container);

		return $links ? $links->length : 0;
	}

	private function determineStatus(bool $hasHtml, bool $hasSchema): ModuleStatus
	{
		if ($hasHtml && $hasSchema) {
			return ModuleStatus::Ok;
		}

		if ($hasHtml || $hasSchema) {
			return ModuleStatus::Warning;
		}

		return ModuleStatus::Bad;
	}
}
