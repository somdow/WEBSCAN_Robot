<?php

namespace App\Services\Analyzers\WordPress;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

class WpPluginsAnalyzer implements AnalyzerInterface
{
	private const PLUGIN_PATH_PATTERN = "/\/wp-content\/plugins\/([a-zA-Z0-9_-]+)\//";

	public function __construct(
		private readonly WpPluginResultsBuilder $resultsBuilder,
	) {}

	public function moduleKey(): string
	{
		return "wpPlugins";
	}

	public function label(): string
	{
		return "WordPress Plugins";
	}

	public function category(): string
	{
		return "WordPress";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.wpPlugins", 7);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$htmlContent = $scanContext->htmlContent;
		$findings = array();
		$recommendations = array();
		$maxApiLookups = (int) config("scanning.max_plugin_api_lookups", 15);

		$pluginRegistry = array();
		$unresolvedHandles = array();
		$this->collectPluginsFromUrlAttributes($xpath, $pluginRegistry);
		$this->collectPluginsFromScriptHandles($xpath, $pluginRegistry, $unresolvedHandles);
		$this->collectPluginsFromHtmlComments($htmlContent, $pluginRegistry);
		$this->collectPluginsFromMetaGenerators($xpath, $pluginRegistry);
		$this->collectPluginsFromInlineScripts($xpath, $pluginRegistry);
		$this->collectPluginsFromHtmlFingerprints($htmlContent, $pluginRegistry);
		$this->resultsBuilder->discoverPluginsFromRestApi($scanContext->domainRoot(), $pluginRegistry);
		$this->resultsBuilder->discoverPluginsFromHandles($unresolvedHandles, $pluginRegistry);

		$detectedPlugins = $this->resultsBuilder->buildDetectedPlugins($pluginRegistry);
		$pluginCount = count($detectedPlugins);

		if ($pluginCount === 0) {
			$findings[] = array("type" => "info", "message" => "No WordPress plugins could be detected from the page source.");
			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: $recommendations);
		}

		$lookupMetrics = $this->resultsBuilder->performApiLookups($detectedPlugins, $maxApiLookups);
		$findings[] = array("type" => "info", "message" => "Detected {$pluginCount} plugin(s) from the page source.");
		$findings[] = array("type" => "data", "key" => "detectedPlugins", "value" => array_values($detectedPlugins));
		$status = $this->resultsBuilder->classifyPluginHealth($detectedPlugins, $lookupMetrics, $findings, $recommendations);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Scan link/script/img attributes for /wp-content/plugins/SLUG/ paths.
	 */
	private function collectPluginsFromUrlAttributes(\DOMXPath $xpath, array &$pluginRegistry): void
	{
		$urlQueries = array(
			array("query" => "//link[contains(@href, '/wp-content/plugins/')]", "attr" => "href"),
			array("query" => "//script[contains(@src, '/wp-content/plugins/')]", "attr" => "src"),
			array("query" => "//img[contains(@src, '/wp-content/plugins/')]", "attr" => "src"),
		);

		foreach ($urlQueries as $queryDef) {
			$nodes = $xpath->query($queryDef["query"]);
			if (!$nodes) {
				continue;
			}

			for ($nodeIndex = 0; $nodeIndex < $nodes->length; $nodeIndex++) {
				$node = $nodes->item($nodeIndex);
				if (!($node instanceof DOMElement)) {
					continue;
				}

				$url = $node->getAttribute($queryDef["attr"]);
				if (!preg_match(self::PLUGIN_PATH_PATTERN, $url, $slugMatch)) {
					continue;
				}

				$slug = strtolower($slugMatch[1]);
				$this->registerPlugin($pluginRegistry, $slug, "url-path");
				$this->extractVersionFromUrl($url, $pluginRegistry[$slug]);
			}
		}
	}

	/**
	 * Extract plugin slugs from WordPress script/style handle IDs.
	 * WordPress enqueues assets as <script id="{handle}-js"> and <link id="{handle}-css">.
	 */
	private function collectPluginsFromScriptHandles(\DOMXPath $xpath, array &$pluginRegistry, array &$unresolvedHandles): void
	{
		$handleSlugMap = config("wp-fingerprints.handle_to_slug", array());
		$coreHandleSet = array_flip(config("wp-fingerprints.core_handles", array()));

		$handleQueries = array(
			array("query" => "//script[@id]", "attr" => "id", "suffix" => "-js"),
			array("query" => "//link[@id]", "attr" => "id", "suffix" => "-css"),
		);

		foreach ($handleQueries as $queryDef) {
			$nodes = $xpath->query($queryDef["query"]);
			if (!$nodes) {
				continue;
			}

			for ($nodeIndex = 0; $nodeIndex < $nodes->length; $nodeIndex++) {
				$node = $nodes->item($nodeIndex);
				if (!($node instanceof DOMElement)) {
					continue;
				}

				$resolveResult = $this->resolveSlugFromHandle($node, $queryDef, $handleSlugMap, $coreHandleSet);
				if ($resolveResult["slug"] !== null) {
					$this->registerPlugin($pluginRegistry, $resolveResult["slug"], "script-handle");
					$this->extractVersionFromElement($node, $pluginRegistry[$resolveResult["slug"]]);
				} elseif ($resolveResult["handle"] !== null) {
					$unresolvedHandles[] = $resolveResult["handle"];
				}
			}
		}
	}

	/**
	 * Resolve a plugin slug from a script/style element's handle ID.
	 */
	private function resolveSlugFromHandle(DOMElement $node, array $queryDef, array $handleSlugMap, array $coreHandleSet): array
	{
		$handle = $this->parseHandleFromId($node->getAttribute($queryDef["attr"]), $queryDef["suffix"]);
		if ($handle === null || isset($coreHandleSet[$handle])) {
			return array("slug" => null, "handle" => null);
		}

		if (str_starts_with($handle, "theme-") || $handle === "style" || $handle === "main") {
			return array("slug" => null, "handle" => null);
		}

		if (isset($handleSlugMap[$handle])) {
			return array("slug" => $handleSlugMap[$handle], "handle" => $handle);
		}

		$srcAttr = $node->getAttribute("src") ?: $node->getAttribute("href");
		if (!empty($srcAttr) && preg_match(self::PLUGIN_PATH_PATTERN, $srcAttr, $slugMatch)) {
			return array("slug" => strtolower($slugMatch[1]), "handle" => $handle);
		}

		return array("slug" => null, "handle" => $handle);
	}

	/**
	 * Detect plugins from HTML comments left in page source.
	 */
	private function collectPluginsFromHtmlComments(string $htmlContent, array &$pluginRegistry): void
	{
		foreach (config("wp-fingerprints.comment_patterns", array()) as $fingerprint) {
			if (preg_match($fingerprint["pattern"], $htmlContent, $commentMatch)) {
				$this->registerPlugin($pluginRegistry, $fingerprint["slug"], "html-comment");
				if (!empty($commentMatch[1])) {
					$pluginRegistry[$fingerprint["slug"]]["versions"][] = $commentMatch[1];
				}
			}
		}
	}

	/**
	 * Check <meta name="generator"> tags for known plugin signatures.
	 */
	private function collectPluginsFromMetaGenerators(\DOMXPath $xpath, array &$pluginRegistry): void
	{
		$generatorNodes = $xpath->query("//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='generator']");
		if (!$generatorNodes) {
			return;
		}

		$generatorPatterns = config("wp-fingerprints.generator_patterns", array());

		for ($nodeIndex = 0; $nodeIndex < $generatorNodes->length; $nodeIndex++) {
			$genNode = $generatorNodes->item($nodeIndex);
			if (!($genNode instanceof DOMElement)) {
				continue;
			}

			$genContent = $genNode->getAttribute("content");
			if (empty($genContent) || preg_match("/^WordPress/i", $genContent)) {
				continue;
			}

			foreach ($generatorPatterns as $pattern => $slug) {
				if (preg_match($pattern, $genContent, $genMatch)) {
					$this->registerPlugin($pluginRegistry, $slug, "meta-generator");
					if (!empty($genMatch[1])) {
						$pluginRegistry[$slug]["versions"][] = $genMatch[1];
					}
					break;
				}
			}
		}
	}

	/**
	 * Scan inline <script> content for known plugin JS globals.
	 */
	private function collectPluginsFromInlineScripts(\DOMXPath $xpath, array &$pluginRegistry): void
	{
		$inlineScripts = $xpath->query("//script[not(@src)]");
		if (!$inlineScripts || $inlineScripts->length === 0) {
			return;
		}

		$concatenatedScripts = "";
		for ($nodeIndex = 0; $nodeIndex < $inlineScripts->length; $nodeIndex++) {
			$concatenatedScripts .= " " . $inlineScripts->item($nodeIndex)->textContent;
		}

		if (strlen($concatenatedScripts) < 10) {
			return;
		}

		foreach (config("wp-fingerprints.script_globals", array()) as $marker => $slug) {
			if (stripos($concatenatedScripts, $marker) !== false) {
				$this->registerPlugin($pluginRegistry, $slug, "inline-script");
			}
		}
	}

	/**
	 * Detect plugins from CSS classes, data attributes, and structural HTML patterns.
	 */
	private function collectPluginsFromHtmlFingerprints(string $htmlContent, array &$pluginRegistry): void
	{
		foreach (config("wp-fingerprints.html_patterns", array()) as $fingerprint) {
			$detected = match ($fingerprint["type"]) {
				"regex" => (bool) preg_match($fingerprint["pattern"], $htmlContent),
				"string" => stripos($htmlContent, $fingerprint["pattern"]) !== false,
				default => false,
			};

			if ($detected) {
				$this->registerPlugin($pluginRegistry, $fingerprint["slug"], "html-fingerprint");
			}
		}
	}

	private function registerPlugin(array &$pluginRegistry, string $slug, string $source): void
	{
		if (!isset($pluginRegistry[$slug])) {
			$pluginRegistry[$slug] = array("versions" => array(), "sources" => array());
		}

		if (!in_array($source, $pluginRegistry[$slug]["sources"], true)) {
			$pluginRegistry[$slug]["sources"][] = $source;
		}
	}

	private function parseHandleFromId(string $elementId, string $suffix): ?string
	{
		$cleanId = preg_replace("/-(extra|before|after|inline)$/", "", $elementId);
		if (!str_ends_with($cleanId, $suffix)) {
			return null;
		}

		$handle = substr($cleanId, 0, -strlen($suffix));
		return (strlen($handle) >= 2) ? strtolower($handle) : null;
	}

	private function extractVersionFromUrl(string $url, array &$registryEntry): void
	{
		if (preg_match("/[?&]ver=([\d.]+)/", $url, $verMatch)) {
			$registryEntry["versions"][] = $verMatch[1];
		}
	}

	private function extractVersionFromElement(DOMElement $node, array &$registryEntry): void
	{
		$url = $node->getAttribute("src") ?: $node->getAttribute("href");
		if (!empty($url)) {
			$this->extractVersionFromUrl($url, $registryEntry);
		}
	}
}
