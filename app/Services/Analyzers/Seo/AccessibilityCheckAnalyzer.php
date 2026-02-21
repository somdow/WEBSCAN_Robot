<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;
use DOMNodeList;

/**
 * Checks basic accessibility practices: form labels, skip navigation,
 * empty interactive elements, tabindex misuse, and ARIA landmark roles.
 * Complements SemanticHtmlAnalyzer (HTML5 landmarks) and ImageAnalysisAnalyzer (alt text).
 */
class AccessibilityCheckAnalyzer implements AnalyzerInterface
{
	/** Input types that don't need visible labels. */
	private const EXEMPT_INPUT_TYPES = array("hidden", "submit", "button", "reset", "image");

	/** Common skip navigation anchor targets. */
	private const SKIP_NAV_TARGETS = array("#main", "#content", "#main-content", "#maincontent", "#skip", "#skip-content", "#skip-nav", "#skipnav");

	/** ARIA landmark roles that improve accessibility. */
	private const LANDMARK_ROLES = array("navigation", "main", "banner", "contentinfo", "complementary", "search");

	public function moduleKey(): string
	{
		return "accessibilityCheck";
	}

	public function label(): string
	{
		return "Accessibility Basics";
	}

	public function category(): string
	{
		return "Usability & Performance";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.accessibilityCheck", 3);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$findings = array();
		$recommendations = array();
		$issueCount = 0;

		$issueCount += $this->checkFormLabels($xpath, $findings, $recommendations);
		$issueCount += $this->checkSkipNavigation($xpath, $findings, $recommendations);
		$issueCount += $this->checkEmptyInteractiveElements($xpath, $findings, $recommendations);
		$issueCount += $this->checkTabindexMisuse($xpath, $findings, $recommendations);
		$issueCount += $this->checkAriaLandmarks($xpath, $findings, $recommendations);

		$status = $this->determineStatus($issueCount);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Check that form inputs have associated labels via for/id pairing or wrapping <label>.
	 */
	private function checkFormLabels(\DOMXPath $xpath, array &$findings, array &$recommendations): int
	{
		$inputNodes = $xpath->query("//input | //select | //textarea");

		if ($inputNodes === false || $inputNodes->length === 0) {
			$findings[] = array("type" => "info", "message" => "No form inputs found on this page.");
			return 0;
		}

		$totalInputs = 0;
		$unlabeledInputs = 0;

		for ($index = 0; $index < $inputNodes->length; $index++) {
			$node = $inputNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$inputType = strtolower(trim($node->getAttribute("type")));
			if (in_array($inputType, self::EXEMPT_INPUT_TYPES, true)) {
				continue;
			}

			$totalInputs++;

			if (!$this->inputHasLabel($node, $xpath)) {
				$unlabeledInputs++;
			}
		}

		if ($totalInputs === 0) {
			return 0;
		}

		$labeledCount = $totalInputs - $unlabeledInputs;

		if ($unlabeledInputs === 0) {
			$findings[] = array("type" => "ok", "message" => "All {$totalInputs} form input(s) have associated labels.");
			return 0;
		}

		$findings[] = array(
			"type" => "warning",
			"message" => "{$unlabeledInputs} of {$totalInputs} form input(s) are missing accessible labels.",
		);
		$recommendations[] = "Add <label for=\"id\"> elements or aria-label attributes to all form inputs so screen readers can identify them.";

		return $unlabeledInputs > 2 ? 2 : 1;
	}

	/**
	 * Check if an input element has an accessible label via for/id, wrapping <label>, aria-label, or aria-labelledby.
	 */
	private function inputHasLabel(DOMElement $input, \DOMXPath $xpath): bool
	{
		/** aria-label or aria-labelledby provide accessible names directly */
		if ($input->hasAttribute("aria-label") || $input->hasAttribute("aria-labelledby")) {
			return true;
		}

		/** title attribute serves as a fallback accessible name */
		if ($input->hasAttribute("title") && trim($input->getAttribute("title")) !== "") {
			return true;
		}

		/** placeholder alone is NOT sufficient for accessibility, but check for/id and wrapping */
		$inputId = $input->getAttribute("id");
		if ($inputId !== "") {
			$escapedId = addcslashes($inputId, "'");
			$matchingLabels = $xpath->query("//label[@for='{$escapedId}']");
			if ($matchingLabels !== false && $matchingLabels->length > 0) {
				return true;
			}
		}

		/** Check for wrapping <label> ancestor */
		$parent = $input->parentNode;
		while ($parent !== null) {
			if ($parent instanceof DOMElement && strtolower($parent->tagName) === "label") {
				return true;
			}
			$parent = $parent->parentNode;
		}

		return false;
	}

	/**
	 * Check for a skip navigation link near the top of the page.
	 */
	private function checkSkipNavigation(\DOMXPath $xpath, array &$findings, array &$recommendations): int
	{
		$earlyLinks = $xpath->query("//body//a[position() <= 5]");

		if ($earlyLinks === false || $earlyLinks->length === 0) {
			$findings[] = array("type" => "warning", "message" => "No skip navigation link found. Keyboard users must tab through the entire header to reach main content.");
			$recommendations[] = "Add a skip navigation link (e.g., <a href=\"#main-content\" class=\"sr-only\">Skip to content</a>) as the first focusable element in the page body.";
			return 1;
		}

		for ($index = 0; $index < $earlyLinks->length; $index++) {
			$link = $earlyLinks->item($index);
			if (!($link instanceof DOMElement)) {
				continue;
			}

			$href = strtolower(trim($link->getAttribute("href")));
			foreach (self::SKIP_NAV_TARGETS as $target) {
				if ($href === $target) {
					$findings[] = array("type" => "ok", "message" => "Skip navigation link found (href=\"{$href}\"). Keyboard users can bypass navigation.");
					return 0;
				}
			}
		}

		$findings[] = array("type" => "warning", "message" => "No skip navigation link detected among the first links on the page.");
		$recommendations[] = "Add a skip navigation link as the first focusable element so keyboard and screen reader users can jump directly to the main content.";
		return 1;
	}

	/**
	 * Check for buttons and links that have no accessible text content.
	 */
	private function checkEmptyInteractiveElements(\DOMXPath $xpath, array &$findings, array &$recommendations): int
	{
		$emptyLinkCount = $this->countEmptyElements($xpath, "//a[@href]");
		$emptyButtonCount = $this->countEmptyElements($xpath, "//button");
		$totalEmpty = $emptyLinkCount + $emptyButtonCount;

		if ($totalEmpty === 0) {
			$findings[] = array("type" => "ok", "message" => "All links and buttons have accessible text content.");
			return 0;
		}

		$parts = array();
		if ($emptyLinkCount > 0) {
			$parts[] = "{$emptyLinkCount} link(s)";
		}
		if ($emptyButtonCount > 0) {
			$parts[] = "{$emptyButtonCount} button(s)";
		}

		$findings[] = array(
			"type" => "warning",
			"message" => implode(" and ", $parts) . " have no accessible text. Screen readers cannot describe these elements to users.",
		);
		$recommendations[] = "Add text content, aria-label, or aria-labelledby to all interactive elements. For icon-only buttons, use aria-label to describe the action.";

		return $totalEmpty > 3 ? 2 : 1;
	}

	/**
	 * Count elements matching an XPath query that have no visible text and no aria-label.
	 */
	private function countEmptyElements(\DOMXPath $xpath, string $query): int
	{
		$nodes = $xpath->query($query);
		if ($nodes === false || $nodes->length === 0) {
			return 0;
		}

		$emptyCount = 0;

		for ($index = 0; $index < $nodes->length; $index++) {
			$node = $nodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$textContent = trim($node->textContent);
			$ariaLabel = trim($node->getAttribute("aria-label"));
			$ariaLabelledBy = trim($node->getAttribute("aria-labelledby"));
			$title = trim($node->getAttribute("title"));

			/** Check for child <img> with alt text (common for image links) */
			$hasImageAlt = false;
			$childImages = $node->getElementsByTagName("img");
			for ($imgIndex = 0; $imgIndex < $childImages->length; $imgIndex++) {
				$img = $childImages->item($imgIndex);
				if ($img instanceof DOMElement && trim($img->getAttribute("alt")) !== "") {
					$hasImageAlt = true;
					break;
				}
			}

			/** Check for child <svg> with aria-label or <title> */
			$hasSvgLabel = false;
			$childSvgs = $node->getElementsByTagName("svg");
			for ($svgIndex = 0; $svgIndex < $childSvgs->length; $svgIndex++) {
				$svg = $childSvgs->item($svgIndex);
				if ($svg instanceof DOMElement) {
					if (trim($svg->getAttribute("aria-label")) !== "") {
						$hasSvgLabel = true;
						break;
					}
					$svgTitles = $svg->getElementsByTagName("title");
					if ($svgTitles->length > 0) {
						$hasSvgLabel = true;
						break;
					}
				}
			}

			if ($textContent === "" && $ariaLabel === "" && $ariaLabelledBy === "" && $title === "" && !$hasImageAlt && !$hasSvgLabel) {
				$emptyCount++;
			}
		}

		return $emptyCount;
	}

	/**
	 * Check for tabindex values greater than 0, which disrupt natural tab order.
	 */
	private function checkTabindexMisuse(\DOMXPath $xpath, array &$findings, array &$recommendations): int
	{
		$tabindexNodes = $xpath->query("//*[@tabindex]");
		if ($tabindexNodes === false || $tabindexNodes->length === 0) {
			return 0;
		}

		$positiveTabindexCount = 0;

		for ($index = 0; $index < $tabindexNodes->length; $index++) {
			$node = $tabindexNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$tabindexValue = (int) $node->getAttribute("tabindex");
			if ($tabindexValue > 0) {
				$positiveTabindexCount++;
			}
		}

		if ($positiveTabindexCount === 0) {
			return 0;
		}

		$findings[] = array(
			"type" => "warning",
			"message" => "{$positiveTabindexCount} element(s) use positive tabindex values. This overrides the natural DOM tab order and confuses keyboard navigation.",
		);
		$recommendations[] = "Remove positive tabindex values (tabindex=\"1\", \"2\", etc.). Use tabindex=\"0\" to make elements focusable in DOM order, or tabindex=\"-1\" for programmatic focus only.";

		return 1;
	}

	/**
	 * Check for ARIA landmark roles on the page.
	 * Complements SemanticHtmlAnalyzer by checking role attributes in addition to HTML5 elements.
	 */
	private function checkAriaLandmarks(\DOMXPath $xpath, array &$findings, array &$recommendations): int
	{
		$foundRoles = array();

		foreach (self::LANDMARK_ROLES as $role) {
			$nodes = $xpath->query("//*[@role='{$role}']");
			if ($nodes !== false && $nodes->length > 0) {
				$foundRoles[] = $role;
			}
		}

		if (count($foundRoles) === 0) {
			$findings[] = array("type" => "info", "message" => "No explicit ARIA landmark roles found. The page may rely on HTML5 semantic elements instead (checked separately in Semantic HTML module).");
			return 0;
		}

		$roleList = implode(", ", $foundRoles);
		$findings[] = array(
			"type" => "ok",
			"message" => count($foundRoles) . " ARIA landmark role(s) found: {$roleList}. These help assistive technologies understand page structure.",
		);

		return 0;
	}

	private function determineStatus(int $issueCount): ModuleStatus
	{
		if ($issueCount === 0) {
			return ModuleStatus::Ok;
		}

		if ($issueCount <= 2) {
			return ModuleStatus::Warning;
		}

		return ModuleStatus::Bad;
	}
}
