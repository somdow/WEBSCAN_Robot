<?php

namespace App\Services\Ai;

use App\Contracts\MultimodalPromptInterface;
use App\Models\Scan;
use App\Models\ScanModuleResult;
use App\Models\User;
use App\Services\Ai\Prompts\ExecutiveSummaryPrompt;
use App\Services\Ai\Prompts\ModulePromptFactory;
use App\Services\MarkdownService;
use Illuminate\Support\Facades\Log;

class OnDemandAiOptimizer
{
	public function __construct(
		private readonly AiGatewayFactory $gatewayFactory,
		private readonly ModulePromptFactory $promptFactory,
	) {}

	/**
	 * Optimize a single module with AI-generated suggestion.
	 * Returns a structured result for JSON response.
	 */
	public function optimizeModule(ScanModuleResult $moduleResult, User $user): array
	{
		try {
			$gateway = $this->gatewayFactory->make($user);
			$siteUrl = $moduleResult->scan->project->url;
			$prompt = $this->promptFactory->make($moduleResult, $siteUrl);

			$options = array();

			if ($prompt instanceof MultimodalPromptInterface) {
				$options["images"] = $prompt->buildImageUrls();
			}

			$response = $gateway->generate(
				$prompt->buildSystemPrompt(),
				$prompt->buildUserPrompt(),
				$options,
			);

			if (!$response->successful) {
				Log::warning("AI module optimization failed", array(
					"module_result_id" => $moduleResult->id,
					"module_key" => $moduleResult->module_key,
					"error" => $response->errorMessage,
				));

				return $this->buildErrorResult($response->errorMessage ?? "AI provider returned an error.");
			}

			$suggestion = trim($response->content);
			$formattedSuggestion = $this->convertMarkdownToHtml($suggestion, $moduleResult->module_key);
			$moduleResult->update(array("ai_suggestion" => $formattedSuggestion));
			$this->trackSingleAiCall($moduleResult->scan);

			return array(
				"success" => true,
				"suggestion" => $formattedSuggestion,
				"error" => null,
			);
		} catch (\Throwable $exception) {
			Log::error("AI module optimization threw exception", array(
				"module_result_id" => $moduleResult->id,
				"module_key" => $moduleResult->module_key,
				"error" => $exception->getMessage(),
			));

			return $this->buildErrorResult($this->classifyError($exception));
		}
	}

	/**
	 * Generate an executive summary for the entire scan.
	 * Returns a structured result for JSON response.
	 */
	public function generateExecutiveSummary(Scan $scan, User $user): array
	{
		try {
			$gateway = $this->gatewayFactory->make($user);
			$moduleResults = $scan->moduleResults()->get();
			$siteUrl = $scan->project->url;

			$prompt = new ExecutiveSummaryPrompt(
				$moduleResults,
				$scan->overall_score ?? 0,
				$siteUrl,
				$scan->project->target_keywords ?? array(),
				$scan->is_wordpress ?? false,
			);

			$systemPrompt = $prompt->buildSystemPrompt();
			$userPrompt = $prompt->buildUserPrompt();
			$summaryOptions = $this->buildExecutiveSummaryOptions();

			$response = $gateway->generate($systemPrompt, $userPrompt, $summaryOptions);

			if (!$response->successful) {
				Log::warning("AI executive summary failed", array(
					"scan_id" => $scan->id,
					"error" => $response->errorMessage,
				));

				return $this->buildErrorResult($response->errorMessage ?? "AI provider returned an error.");
			}

			$parsed = $this->parseJsonResponse($response->content);

			if ($parsed === null) {
				$retryResponse = $gateway->generate(
					$systemPrompt,
					$this->buildExecutiveSummaryRetryPrompt($response->content),
					$summaryOptions,
				);

				if ($retryResponse->successful) {
					$parsed = $this->parseJsonResponse($retryResponse->content);
				}
			}

			if ($parsed === null) {
				return $this->buildErrorResult("AI returned an invalid response format. Please try again.");
			}

			$scan->update(array("ai_executive_summary" => $parsed));
			$this->trackSingleAiCall($scan);

			return array(
				"success" => true,
				"summary" => $parsed,
				"error" => null,
			);
		} catch (\Throwable $exception) {
			Log::error("AI executive summary threw exception", array(
				"scan_id" => $scan->id,
				"error" => $exception->getMessage(),
			));

			return $this->buildErrorResult($this->classifyError($exception));
		}
	}

	private function buildExecutiveSummaryOptions(): array
	{
		return array(
			"temperature" => 0.1,
			"response_mime_type" => "application/json",
		);
	}

	/**
	 * Parse a JSON string from the AI response, handling markdown code fences.
	 */
	private function parseJsonResponse(string $content): ?array
	{
		$cleaned = trim($content);

		if (str_starts_with($cleaned, "```")) {
			$cleaned = preg_replace("/^```(?:json)?\s*/", "", $cleaned);
			$cleaned = preg_replace("/\s*```$/", "", $cleaned);
		}

		$decoded = json_decode($cleaned, true);

		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
			return $this->normalizeExecutiveSummary($decoded);
		}

		$jsonObject = $this->extractFirstJsonObject($cleaned);
		if ($jsonObject !== null) {
			$decoded = json_decode($jsonObject, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				return $this->normalizeExecutiveSummary($decoded);
			}
		}

		if (json_last_error() !== JSON_ERROR_NONE) {
			Log::warning("Failed to parse AI JSON response", array(
				"error" => json_last_error_msg(),
				"content_preview" => mb_substr($content, 0, 200),
			));
			return null;
		}

		return null;
	}

	/**
	 * Ask the model to repair a previous invalid summary payload without changing intent.
	 */
	private function buildExecutiveSummaryRetryPrompt(string $invalidResponse): string
	{
		$snippet = mb_substr($invalidResponse, 0, 3000);

		return <<<PROMPT
Your previous answer was not valid JSON.

Return ONLY valid JSON with this exact structure and no markdown:
{
  "summary": "2-3 sentence overview of the site's SEO health",
  "topIssues": [
    {"module": "moduleKey", "issue": "one-line description", "impact": "high|medium|low"}
  ],
  "quickWins": [
    {"action": "specific actionable step", "estimatedPoints": 5}
  ]
}

Previous invalid output:
{$snippet}
PROMPT;
	}

	/**
	 * Normalize the executive summary payload into the expected UI schema.
	 */
	private function normalizeExecutiveSummary(array $decoded): ?array
	{
		$summary = trim((string) ($decoded["summary"] ?? ""));

		if ($summary === "") {
			return null;
		}

		$topIssuesInput = is_array($decoded["topIssues"] ?? null) ? $decoded["topIssues"] : array();
		$quickWinsInput = is_array($decoded["quickWins"] ?? null) ? $decoded["quickWins"] : array();

		$topIssues = array();
		foreach ($topIssuesInput as $issue) {
			if (!is_array($issue)) {
				continue;
			}

			$module = trim((string) ($issue["module"] ?? ""));
			$message = trim((string) ($issue["issue"] ?? ""));
			$impact = strtolower(trim((string) ($issue["impact"] ?? "low")));

			if ($message === "") {
				continue;
			}

			if (!in_array($impact, array("high", "medium", "low"), true)) {
				$impact = "low";
			}

			$topIssues[] = array(
				"module" => $module,
				"issue" => $message,
				"impact" => $impact,
			);
		}

		$quickWins = array();
		foreach ($quickWinsInput as $win) {
			if (!is_array($win)) {
				continue;
			}

			$action = trim((string) ($win["action"] ?? ""));
			if ($action === "") {
				continue;
			}

			$estimatedPoints = (int) ($win["estimatedPoints"] ?? 0);
			$estimatedPoints = max(0, min(100, $estimatedPoints));

			$quickWins[] = array(
				"action" => $action,
				"estimatedPoints" => $estimatedPoints,
			);
		}

		return array(
			"summary" => $summary,
			"topIssues" => array_slice($topIssues, 0, 5),
			"quickWins" => array_slice($quickWins, 0, 4),
		);
	}

	/**
	 * Extract the first valid-looking JSON object from mixed text.
	 */
	private function extractFirstJsonObject(string $text): ?string
	{
		$start = strpos($text, "{");

		if ($start === false) {
			return null;
		}

		$depth = 0;
		$inString = false;
		$escape = false;
		$length = strlen($text);

		for ($index = $start; $index < $length; $index++) {
			$char = $text[$index];

			if ($inString) {
				if ($escape) {
					$escape = false;
					continue;
				}

				if ($char === "\\") {
					$escape = true;
					continue;
				}

				if ($char === "\"") {
					$inString = false;
				}
				continue;
			}

			if ($char === "\"") {
				$inString = true;
				continue;
			}

			if ($char === "{") {
				$depth++;
			} elseif ($char === "}") {
				$depth--;
				if ($depth === 0) {
					return substr($text, $start, $index - $start + 1);
				}
			}
		}

		return null;
	}

	/**
	 * Track a single AI API call for the scan's organization usage.
	 */
	private function trackSingleAiCall(Scan $scan): void
	{
		try {
			$organization = $scan->project->organization;

			if ($organization === null) {
				return;
			}

			$usage = $organization->subscriptionUsage()
				->where("period_start", "<=", now())
				->where("period_end", ">=", now())
				->first();

			if ($usage !== null) {
				$usage->incrementAiCalls(1);
			}
		} catch (\Throwable $exception) {
			Log::warning("Failed to track AI usage", array(
				"scan_id" => $scan->id,
				"error" => $exception->getMessage(),
			));
		}
	}

	/**
	 * Classify an exception into a user-friendly error message with error code.
	 */
	private function classifyError(\Throwable $exception): string
	{
		$message = $exception->getMessage();

		if (str_contains($message, "API key") || str_contains($message, "authentication") || str_contains($message, "Unauthorized")) {
			return "invalid_api_key: Your API key is invalid or expired. Check your AI settings.";
		}

		if (str_contains($message, "rate limit") || str_contains($message, "429")) {
			return "rate_limited: AI provider rate limit reached. Please wait a moment and try again.";
		}

		if (str_contains($message, "timeout") || str_contains($message, "timed out")) {
			return "timeout: The AI request timed out. Please try again.";
		}

		return "An unexpected error occurred. Please try again.";
	}

	/**
	 * Extract the PAGE_TYPE: line from the AI response.
	 * Returns the normalized line (plain text) or empty string if not found.
	 */
	private function extractPageType(string $suggestion): string
	{
		if (preg_match("/^\*{0,2}PAGE_TYPE:\*{0,2}\s*(.+)$/im", $suggestion, $matches)) {
			return "PAGE_TYPE: " . trim($matches[1]);
		}

		return "";
	}

	/**
	 * Convert AI markdown response to HTML, preserving structured lines.
	 * PAGE_TYPE: and OPTIMIZED: lines are kept as plain text.
	 * For OPTIMIZED format modules, everything after OPTIMIZED: is HTML-converted.
	 * For socialTags, OG_TITLE: and OG_DESC: lines are preserved as plain text.
	 * For other modules, everything after PAGE_TYPE: is HTML-converted.
	 */
	private function convertMarkdownToHtml(string $suggestion, string $moduleKey): string
	{
		$pageType = $this->extractPageType($suggestion);

		/* Single-value OPTIMIZED format (titleTag, metaDescription, h1Tag) */
		$optimizedModules = array("titleTag", "metaDescription", "h1Tag");

		if (in_array($moduleKey, $optimizedModules, true)
			&& preg_match("/^\*{0,2}OPTIMIZED:\*{0,2}\s*(.+)$/im", $suggestion, $matches, PREG_OFFSET_CAPTURE)
		) {
			return $this->assembleOptimizedResponse($suggestion, $pageType, $matches);
		}

		/* Dual-value OG format (socialTags) */
		if ($moduleKey === "socialTags") {
			return $this->assembleSocialTagsResponse($suggestion, $pageType);
		}

		/* imageAnalysis: keep raw text so the Alpine parser can extract IMAGE:/ALT: pairs */
		if ($moduleKey === "imageAnalysis") {
			return $suggestion;
		}

		/* Default: convert everything to HTML, prepend PAGE_TYPE */
		$htmlContent = MarkdownService::toHtml($suggestion);

		if ($pageType !== "") {
			return $pageType . "\n" . $this->stripPageTypeFromHtml($htmlContent);
		}

		return $htmlContent;
	}

	/**
	 * Assemble response for single OPTIMIZED: value modules.
	 * Preserves PAGE_TYPE and OPTIMIZED lines as plain text, converts rest to HTML.
	 */
	private function assembleOptimizedResponse(string $suggestion, string $pageType, array $matches): string
	{
		$optimizedText = trim($matches[1][0]);
		$afterOptimized = trim(substr($suggestion, $matches[0][1] + strlen($matches[0][0])));

		$result = "";
		if ($pageType !== "") {
			$result .= $pageType . "\n";
		}
		$result .= "OPTIMIZED: " . $optimizedText;

		if ($afterOptimized !== "") {
			$result .= "\n" . MarkdownService::toHtml($afterOptimized);
		}

		return $result;
	}

	/**
	 * Assemble response for socialTags dual OG_TITLE: / OG_DESC: format.
	 * Preserves marker lines as plain text, converts explanation to HTML.
	 */
	private function assembleSocialTagsResponse(string $suggestion, string $pageType): string
	{
		$ogTitle = "";
		$ogDesc = "";

		if (preg_match("/^\*{0,2}OG_TITLE:\*{0,2}\s*(.+)$/im", $suggestion, $titleMatch)) {
			$ogTitle = trim($titleMatch[1]);
		}

		if (preg_match("/^\*{0,2}OG_DESC:\*{0,2}\s*(.+)$/im", $suggestion, $descMatch, PREG_OFFSET_CAPTURE)) {
			$ogDesc = trim($descMatch[1][0]);
			$afterDesc = trim(substr($suggestion, $descMatch[0][1] + strlen($descMatch[0][0])));
		} else {
			$afterDesc = "";
		}

		/* If no OG markers found, fall back to generic conversion */
		if ($ogTitle === "" && $ogDesc === "") {
			$htmlContent = MarkdownService::toHtml($suggestion);
			if ($pageType !== "") {
				return $pageType . "\n" . $this->stripPageTypeFromHtml($htmlContent);
			}
			return $htmlContent;
		}

		$result = "";
		if ($pageType !== "") {
			$result .= $pageType . "\n";
		}
		if ($ogTitle !== "") {
			$result .= "OG_TITLE: " . $ogTitle . "\n";
		}
		if ($ogDesc !== "") {
			$result .= "OG_DESC: " . $ogDesc;
		}
		if ($afterDesc !== "") {
			$result .= "\n" . MarkdownService::toHtml($afterDesc);
		}

		return $result;
	}

	/**
	 * Remove the PAGE_TYPE paragraph from HTML output to avoid duplication.
	 * The PAGE_TYPE line gets converted to a <p> by MarkdownService — strip it.
	 */
	private function stripPageTypeFromHtml(string $html): string
	{
		return preg_replace("/<p>\s*(?:<\w+>)*\s*PAGE_TYPE:[^<]*(?:<\/\w+>)*\s*<\/p>/i", "", $html);
	}

	private function buildErrorResult(string $error): array
	{
		return array(
			"success" => false,
			"suggestion" => null,
			"summary" => null,
			"error" => $error,
		);
	}
}
