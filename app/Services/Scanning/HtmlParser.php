<?php

namespace App\Services\Scanning;

use DOMDocument;
use DOMElement;
use DOMXPath;

class HtmlParser
{
	/**
	 * Parse HTML into a DOMDocument and extract all shared metadata
	 * needed by the ScanContext.
	 *
	 * @return array Associative array of extracted values
	 */
	public function parseHtml(string $htmlContent): array
	{
		$domDocument = new DOMDocument();
		libxml_use_internal_errors(true);

		$htmlToLoad = $this->ensureCharsetDeclaration($htmlContent);
		$domDocument->loadHTML($htmlToLoad);

		libxml_clear_errors();
		libxml_use_internal_errors(false);

		$xpath = new DOMXPath($domDocument);

		return array(
			"domDocument" => $domDocument,
			"xpath" => $xpath,
			"titleContent" => $this->extractTitle($xpath),
			"titleTagCount" => $this->countTitleTags($xpath),
			"metaDescriptionContent" => $this->extractMetaDescriptionContent($xpath),
			"metaDescriptionTagCount" => $this->countMetaDescriptionTags($xpath),
			"langAttribute" => $this->extractLangAttribute($xpath),
			"canonicalHrefs" => $this->extractCanonicalHrefs($xpath),
			"canonicalTagCount" => $this->countCanonicalTags($xpath),
			"allHeadingsData" => $this->extractAllHeadings($xpath),
			"viewportContents" => $this->extractViewportContents($xpath),
			"viewportTagCount" => $this->countViewportTags($xpath),
			"robotsMetaContent" => $this->extractRobotsMetaContent($xpath),
			"robotsMetaTagCount" => $this->countRobotsMetaTags($xpath),
		);
	}

	/**
	 * Prepend UTF-8 encoding declaration if the HTML lacks a charset declaration.
	 * Prevents DOMDocument from defaulting to ISO-8859-1.
	 */
	private function ensureCharsetDeclaration(string $htmlContent): string
	{
		$hasCharset = stripos($htmlContent, "<meta charset") !== false
			|| stripos($htmlContent, "content=\"text/html; charset=") !== false;

		if (!$hasCharset) {
			return "<?xml encoding=\"utf-8\" ?>" . $htmlContent;
		}

		return $htmlContent;
	}

	private function extractTitle(DOMXPath $xpath): ?string
	{
		$nodes = $xpath->query("//head/title");

		return ($nodes && $nodes->length > 0) ? trim($nodes->item(0)->textContent) : null;
	}

	private function countTitleTags(DOMXPath $xpath): int
	{
		$nodes = $xpath->query("//head/title");

		return $nodes ? $nodes->length : 0;
	}

	private function extractMetaDescriptionContent(DOMXPath $xpath): ?string
	{
		$nodes = $xpath->query("//head/meta[" . self::xpathLowercase("@name") . "=\"description\"]/@content");

		return ($nodes && $nodes->length > 0) ? trim($nodes->item(0)->nodeValue) : null;
	}

	private function countMetaDescriptionTags(DOMXPath $xpath): int
	{
		$nodes = $xpath->query("//head/meta[" . self::xpathLowercase("@name") . "=\"description\"]");

		return $nodes ? $nodes->length : 0;
	}

	private function extractLangAttribute(DOMXPath $xpath): ?string
	{
		$nodes = $xpath->query("//html/@lang");

		return ($nodes && $nodes->length > 0) ? trim($nodes->item(0)->nodeValue) : null;
	}

	private function extractCanonicalHrefs(DOMXPath $xpath): array
	{
		$nodes = $xpath->query("//head/link[" . self::xpathLowercase("@rel") . "=\"canonical\"]");
		$hrefs = array();

		if ($nodes) {
			foreach ($nodes as $node) {
				if ($node instanceof DOMElement) {
					$href = $node->getAttribute("href");
					if ($href) {
						$hrefs[] = trim($href);
					}
				}
			}
		}

		return $hrefs;
	}

	private function countCanonicalTags(DOMXPath $xpath): int
	{
		$nodes = $xpath->query("//head/link[" . self::xpathLowercase("@rel") . "=\"canonical\"]");

		return $nodes ? $nodes->length : 0;
	}

	private function extractAllHeadings(DOMXPath $xpath): array
	{
		$nodes = $xpath->query("//body//h1|//body//h2|//body//h3|//body//h4|//body//h5|//body//h6");
		$headings = array();

		if ($nodes) {
			foreach ($nodes as $node) {
				if ($node instanceof DOMElement) {
					$text = trim($node->textContent);
					$imageOnly = false;
					$imageAlt = null;

					if ($text === "") {
						$imgResult = $this->extractImageFromHeading($node);
						if ($imgResult !== null) {
							$imageOnly = true;
							$imageAlt = $imgResult;
							$text = $imgResult;
						}
					}

					$headings[] = array(
						"tag" => strtolower($node->nodeName),
						"text" => $text,
						"hidden" => $this->isElementHidden($node),
						"imageOnly" => $imageOnly,
						"imageAlt" => $imageAlt,
					);
				}
			}
		}

		return $headings;
	}

	/**
	 * Check if a heading element contains an img child.
	 * Returns the image alt text (may be empty string), or null if no img found.
	 */
	private function extractImageFromHeading(DOMElement $headingElement): ?string
	{
		$imgNodes = $headingElement->getElementsByTagName("img");

		if ($imgNodes->length === 0) {
			return null;
		}

		$firstImg = $imgNodes->item(0);

		if ($firstImg instanceof DOMElement && $firstImg->hasAttribute("alt")) {
			return trim($firstImg->getAttribute("alt"));
		}

		return "";
	}

	/**
	 * Check whether a DOM element or any of its ancestors is hidden.
	 * Inspects aria-hidden attribute and inline style for display:none / visibility:hidden.
	 */
	private function isElementHidden(DOMElement $element): bool
	{
		$current = $element;

		while ($current instanceof DOMElement) {
			if ($current->getAttribute("aria-hidden") === "true") {
				return true;
			}

			$style = $current->getAttribute("style");
			if ($style !== "") {
				$normalizedStyle = strtolower(preg_replace("/\s+/", "", $style));
				if (str_contains($normalizedStyle, "display:none") || str_contains($normalizedStyle, "visibility:hidden")) {
					return true;
				}
			}

			$current = $current->parentNode instanceof DOMElement ? $current->parentNode : null;
		}

		return false;
	}

	private function extractViewportContents(DOMXPath $xpath): array
	{
		$nodes = $xpath->query("//head/meta[" . self::xpathLowercase("@name") . "=\"viewport\"]");
		$contents = array();

		if ($nodes) {
			foreach ($nodes as $node) {
				if ($node instanceof DOMElement) {
					$content = $node->getAttribute("content");
					$contents[] = $content ? trim($content) : "";
				}
			}
		}

		return $contents;
	}

	private function countViewportTags(DOMXPath $xpath): int
	{
		$nodes = $xpath->query("//head/meta[" . self::xpathLowercase("@name") . "=\"viewport\"]");

		return $nodes ? $nodes->length : 0;
	}

	private function extractRobotsMetaContent(DOMXPath $xpath): ?string
	{
		$nodes = $xpath->query("//head/meta[" . self::xpathLowercase("@name") . "=\"robots\"]/@content");

		return ($nodes && $nodes->length > 0) ? trim($nodes->item(0)->nodeValue) : null;
	}

	private function countRobotsMetaTags(DOMXPath $xpath): int
	{
		$nodes = $xpath->query("//head/meta[" . self::xpathLowercase("@name") . "=\"robots\"]");

		return $nodes ? $nodes->length : 0;
	}

	/**
	 * XPath 1.0 case-insensitive comparison helper.
	 * Translates an attribute value to lowercase for matching.
	 * DOMDocument preserves attribute VALUES as-is (only names are lowercased),
	 * so sites with NAME="ROBOTS" vs name="robots" need this.
	 */
	private static function xpathLowercase(string $attribute): string
	{
		return "translate({$attribute},'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')";
	}
}
