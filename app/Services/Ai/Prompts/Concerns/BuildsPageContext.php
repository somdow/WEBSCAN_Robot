<?php

namespace App\Services\Ai\Prompts\Concerns;

use App\Models\ScanModuleResult;

/**
 * Loads sibling module results from the same scan to build rich page context.
 * Gives the AI a complete understanding of the page's topic, structure,
 * and content so it can generate specific, relevant optimization suggestions.
 */
trait BuildsPageContext
{
	private const CONTEXT_MODULE_KEYS = array(
		"titleTag",
		"metaDescription",
		"h1Tag",
		"h2h6Tags",
		"schemaOrg",
		"contentKeywords",
		"contentReadability",
	);

	private const MAX_BODY_EXCERPT_CHARS = 2000;

	/**
	 * Build a rich page context string from sibling module results and project keywords.
	 * Returns empty string if no sibling data is available.
	 */
	protected function buildPageContext(ScanModuleResult $moduleResult): string
	{
		$siblingResults = $moduleResult->scan->moduleResults()
			->whereIn("module_key", self::CONTEXT_MODULE_KEYS)
			->where("scan_page_id", $moduleResult->scan_page_id)
			->get()
			->keyBy("module_key");

		$contextParts = array();

		$targetKeywords = $moduleResult->scan->project->target_keywords ?? array();
		if (!empty($targetKeywords)) {
			$contextParts[] = "Target Keywords (set by site owner): " . implode(", ", $targetKeywords);
		}

		$this->appendSiblingMessage($contextParts, $siblingResults->get("titleTag"), "Page Title");
		$this->appendSiblingMessage($contextParts, $siblingResults->get("metaDescription"), "Meta Description");
		$this->appendSiblingMessage($contextParts, $siblingResults->get("h1Tag"), "H1");
		$this->appendSiblingMessage($contextParts, $siblingResults->get("schemaOrg"), "Schema");
		$this->appendSiblingMessage($contextParts, $siblingResults->get("contentKeywords"), "Keywords");

		$headingsOutline = $this->extractHeadingsOutline($siblingResults->get("h2h6Tags"));
		if ($headingsOutline !== "") {
			$contextParts[] = "Page Headings:\n{$headingsOutline}";
		}

		$bodyExcerpt = $this->extractBodyExcerpt($siblingResults->get("contentReadability"));
		if ($bodyExcerpt !== "") {
			$contextParts[] = "Page Content Excerpt:\n{$bodyExcerpt}";
		}

		if (empty($contextParts)) {
			return "";
		}

		return implode("\n\n", $contextParts);
	}

	/**
	 * Append a label + first finding message to context parts if available.
	 */
	private function appendSiblingMessage(array &$contextParts, ?ScanModuleResult $result, string $label): void
	{
		if ($result === null) {
			return;
		}

		$message = $this->extractFirstVisibleMessage($result);

		if ($message !== "") {
			$contextParts[] = "{$label}: {$message}";
		}
	}

	/**
	 * Extract the heading structure from h2h6Tags data finding.
	 * Returns an indented outline like "H2: About Our Services\n  H3: Web Design"
	 */
	private function extractHeadingsOutline(?ScanModuleResult $headingsResult): string
	{
		if ($headingsResult === null) {
			return "";
		}

		$headingsList = $this->extractDataFindingByKey($headingsResult, "headingsList");

		if (empty($headingsList)) {
			return "";
		}

		$lines = array();

		foreach ($headingsList as $heading) {
			$tag = strtoupper($heading["tag"] ?? "H2");
			$text = trim($heading["text"] ?? "");

			if ($text === "") {
				continue;
			}

			$indent = str_repeat("  ", ((int) substr($tag, 1)) - 2);
			$lines[] = "{$indent}{$tag}: {$text}";
		}

		return implode("\n", $lines);
	}

	/**
	 * Extract body text excerpt from contentReadability data finding.
	 */
	private function extractBodyExcerpt(?ScanModuleResult $readabilityResult): string
	{
		if ($readabilityResult === null) {
			return "";
		}

		$excerpt = $this->extractDataFindingByKey($readabilityResult, "bodyTextExcerpt");

		if (!is_string($excerpt) || $excerpt === "") {
			return "";
		}

		return mb_substr($excerpt, 0, self::MAX_BODY_EXCERPT_CHARS);
	}

	/**
	 * Extract the first visible (non-data) finding message from a module result.
	 */
	private function extractFirstVisibleMessage(ScanModuleResult $result): string
	{
		foreach ($result->findings ?? array() as $finding) {
			if (($finding["type"] ?? "") === "data") {
				continue;
			}

			$message = $finding["message"] ?? "";

			if ($message !== "") {
				return $message;
			}
		}

		return "";
	}

	/**
	 * Extract a structured data finding by key from a module result.
	 */
	private function extractDataFindingByKey(ScanModuleResult $result, string $key): mixed
	{
		foreach ($result->findings ?? array() as $finding) {
			if (($finding["type"] ?? "") === "data" && ($finding["key"] ?? "") === $key) {
				return $finding["value"] ?? null;
			}
		}

		return null;
	}
}
