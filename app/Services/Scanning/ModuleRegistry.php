<?php

namespace App\Services\Scanning;

use App\Contracts\AnalyzerInterface;
use App\Enums\AnalyzerScope;

class ModuleRegistry
{
	/**
	 * Analyzers that operate at the site level — run once per scan, not per page.
	 * Everything else is classified as per-page.
	 */
	private const SITE_WIDE_MODULES = array(
		"robotsTxt",
		"sitemapAnalysis",
		"wpDetection",
		"wpPlugins",
		"wpTheme",
		"httpHeaders",
		"blacklistCheck",
		"securityHeaders",
		"exposedSensitiveFiles",
		"sslCertificate",
		"httpsRedirect",
		"duplicateUrl",
		"techStackDetection",
		"coreWebVitalsMobile",
		"coreWebVitalsDesktop",
		"analyticsDetection",
	);

	/** WordPress-specific module keys — single source of truth for WP filtering. */
	public const WORDPRESS_MODULE_KEYS = array("wpDetection", "wpPlugins", "wpTheme");

	/** @var array<string, AnalyzerInterface> Keyed by moduleKey */
	private array $analyzers = array();

	public function register(AnalyzerInterface $analyzer): void
	{
		$this->analyzers[$analyzer->moduleKey()] = $analyzer;
	}

	/**
	 * @return AnalyzerInterface[]
	 */
	public function allAnalyzers(): array
	{
		return array_values($this->analyzers);
	}

	public function getAnalyzer(string $moduleKey): ?AnalyzerInterface
	{
		return $this->analyzers[$moduleKey] ?? null;
	}

	/**
	 * Phase 1: Analyzers that produce shared state (robotsTxt content, WP detection)
	 * These must run before other modules.
	 *
	 * @return AnalyzerInterface[]
	 */
	public function phaseOneAnalyzers(): array
	{
		return $this->filterByKeys(array("robotsTxt", "wpDetection"));
	}

	/**
	 * Phase 2: Analyzers that depend on Phase 1 output (sitemap needs robotsTxtContent)
	 *
	 * @return AnalyzerInterface[]
	 */
	public function phaseTwoAnalyzers(): array
	{
		return $this->filterByKeys(array("sitemapAnalysis"));
	}

	/**
	 * Phase 4: All remaining SEO, E-E-A-T, and Content analyzers (DOM-only, no dependencies).
	 * Runs after trust page crawl so E-E-A-T modules have access to trust page data.
	 *
	 * @return AnalyzerInterface[]
	 */
	public function standardSeoAnalyzers(): array
	{
		$standardCategories = array(
			"On-Page SEO",
			"Technical SEO",
			"Usability & Performance",
			"Graphs, Schema & Links",
			"E-E-A-T Signals",
			"Content Analysis",
			"Security",
			"Extras",
		);

		$excludeKeys = array("robotsTxt", "sitemapAnalysis", "wpDetection", "wpPlugins", "wpTheme", "googleMapEmbed", "analyticsDetection", "serpPreview", "blacklistCheck", "securityHeaders", "exposedSensitiveFiles", "sslCertificate", "techStackDetection", "coreWebVitalsMobile", "coreWebVitalsDesktop", "httpsRedirect", "duplicateUrl");

		return array_values(array_filter(
			$this->analyzers,
			fn(AnalyzerInterface $analyzer) => in_array($analyzer->category(), $standardCategories, true)
				&& !in_array($analyzer->moduleKey(), $excludeKeys, true),
		));
	}

	/**
	 * Phase 5: WordPress-specific analyzers (only run if WP detected).
	 * Excludes wpDetection which runs in Phase 1.
	 *
	 * @return AnalyzerInterface[]
	 */
	public function wordPressAnalyzers(): array
	{
		return $this->filterByKeys(array("wpPlugins", "wpTheme"));
	}

	/**
	 * Phase 6: Utility analyzers (always run last).
	 *
	 * @return AnalyzerInterface[]
	 */
	public function utilityAnalyzers(): array
	{
		return $this->filterByKeys(array("googleMapEmbed", "serpPreview"));
	}

	/**
	 * Build a moduleKey => human-readable label map for all registered analyzers.
	 *
	 * @return array<string, string>
	 */
	public function labelMap(): array
	{
		$labels = array();

		foreach ($this->analyzers as $key => $analyzer) {
			$labels[$key] = $analyzer->label();
		}

		return $labels;
	}

	/**
	 * Resolve the display category for a module key.
	 */
	public function resolveCategory(string $moduleKey): string
	{
		$analyzer = $this->analyzers[$moduleKey] ?? null;

		return $analyzer !== null ? $analyzer->category() : "Other";
	}

	/**
	 * Determine the execution scope for a given analyzer module.
	 */
	public function analyzerScope(string $moduleKey): AnalyzerScope
	{
		return in_array($moduleKey, self::SITE_WIDE_MODULES, true)
			? AnalyzerScope::SiteWide
			: AnalyzerScope::PerPage;
	}

	/**
	 * Analyzers that run once per scan (robots.txt, sitemap, WP detection, HTTP headers).
	 *
	 * @return AnalyzerInterface[]
	 */
	public function siteWideAnalyzers(): array
	{
		return $this->filterByKeys(self::SITE_WIDE_MODULES);
	}

	/**
	 * Analyzers that run for each crawled page (titles, meta, content, etc.).
	 *
	 * @return AnalyzerInterface[]
	 */
	public function perPageAnalyzers(): array
	{
		return array_values(array_filter(
			$this->analyzers,
			fn(AnalyzerInterface $analyzer) => !in_array(
				$analyzer->moduleKey(),
				self::SITE_WIDE_MODULES,
				true,
			),
		));
	}

	/**
	 * Display order within each category — modules appear in this sequence in the UI.
	 * Follows the natural top-to-bottom order of a web page where applicable.
	 */
	private const DISPLAY_ORDER = array(
		/* On-Page SEO — document structure top to bottom */
		"doctypeCharset" => 1,
		"titleTag" => 2,
		"metaDescription" => 3,
		"breadcrumbs" => 4,
		"h1Tag" => 5,
		"h2h6Tags" => 6,

		/* Technical SEO — foundational checks first */
		"robotsTxt" => 1,
		"sitemapAnalysis" => 2,
		"canonicalTag" => 3,
		"htmlLang" => 4,
		"robotsMeta" => 5,
		"noindexCheck" => 6,
		"httpHeaders" => 7,
		"httpsRedirect" => 8,
		"duplicateUrl" => 9,
		"hreflang" => 10,
		"semanticHtml" => 11,
		"urlStructure" => 12,
		"redirectChain" => 13,

		/* Content Analysis */
		"contentReadability" => 1,
		"contentKeywords" => 2,
		"keywordConsistency" => 3,
		"contentDuplicate" => 4,

		/* E-E-A-T Signals */
		"eatAuthor" => 1,
		"eatBusinessSchema" => 2,
		"eatTrustPages" => 3,
		"eatPrivacyTerms" => 4,

		/* Graphs, Schema & Links */
		"socialTags" => 1,
		"schemaOrg" => 2,
		"schemaValidation" => 3,
		"linkAnalysis" => 4,
		"brokenLinks" => 5,

		/* Usability & Performance */
		"viewportTag" => 1,
		"imageAnalysis" => 2,
		"performanceHints" => 3,
		"compressionCheck" => 4,
		"accessibilityCheck" => 5,

		/* Core Web Vitals */
		"coreWebVitalsMobile" => 1,
		"coreWebVitalsDesktop" => 2,

		/* Security */
		"sslCertificate" => 1,
		"securityHeaders" => 2,
		"mixedContent" => 3,
		"exposedSensitiveFiles" => 4,
		"blacklistCheck" => 5,

		/* Extras */
		"favicon" => 1,
		"googleMapEmbed" => 2,
		"serpPreview" => 3,

		/* WordPress */
		"wpDetection" => 1,
		"wpPlugins" => 2,
		"wpTheme" => 3,

		/* Analytics / Tech */
		"analyticsDetection" => 1,
		"techStackDetection" => 1,
	);

	/**
	 * Get the display position for a module within its category.
	 * Unregistered keys sort to the end.
	 */
	public function displayOrder(string $moduleKey): int
	{
		return self::DISPLAY_ORDER[$moduleKey] ?? 999;
	}

	/**
	 * Filter registered analyzers by their module keys.
	 *
	 * @return AnalyzerInterface[]
	 */
	private function filterByKeys(array $keys): array
	{
		$result = array();

		foreach ($keys as $key) {
			if (isset($this->analyzers[$key])) {
				$result[] = $this->analyzers[$key];
			}
		}

		return $result;
	}
}
