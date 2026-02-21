<?php

namespace App\Services\Analyzers\Concerns;

use App\DataTransferObjects\ScanContext;

/**
 * Shared content extraction methods used by multiple Content and E-E-A-T analyzers.
 * Prevents duplication of DOM traversal logic across analyzer classes.
 */
trait ExtractsPageContent
{
	/**
	 * Extract the first H1 heading text from the scan context.
	 */
	protected function extractH1Text(ScanContext $scanContext): ?string
	{
		foreach ($scanContext->allHeadingsData as $heading) {
			if (($heading["level"] ?? 0) === 1) {
				return $heading["text"] ?? null;
			}
		}

		return null;
	}

	/**
	 * Extract the first substantial paragraph (5+ words) from the page body.
	 */
	protected function extractFirstParagraph(ScanContext $scanContext): ?string
	{
		$paragraphNodes = $scanContext->xpath->query("//body//p");
		if ($paragraphNodes === false || $paragraphNodes->length === 0) {
			return null;
		}

		for ($i = 0; $i < min($paragraphNodes->length, 5); $i++) {
			$text = trim($paragraphNodes->item($i)->textContent);
			if (str_word_count($text) >= 5) {
				return $text;
			}
		}

		return null;
	}

	/**
	 * Extract visible body text from HTML, stripping navigation and boilerplate elements.
	 */
	protected function extractVisibleBodyText(string $html): string
	{
		$cleaned = preg_replace("/<(script|style|nav|header|footer|noscript|aside)[^>]*>.*?<\\/\\1>/si", " ", $html);
		$cleaned = preg_replace("/<[^>]+>/", " ", $cleaned);
		$cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, "UTF-8");
		$cleaned = preg_replace("/\\s+/", " ", $cleaned);

		return trim($cleaned);
	}
}
