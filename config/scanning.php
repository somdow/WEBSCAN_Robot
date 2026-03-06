<?php

return array(
	"user_agent" => env("SCAN_USER_AGENT", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"),
	"page_timeout" => (int) env("SCAN_PAGE_TIMEOUT", 15),
	"resource_timeout" => (int) env("SCAN_RESOURCE_TIMEOUT", 5),
	"max_redirects" => 5,
	"max_content_size" => 5 * 1024 * 1024,
	"max_plugin_api_lookups" => 15,

	/*
	|--------------------------------------------------------------------------
	| Multi-Page Crawl Settings
	|--------------------------------------------------------------------------
	*/
	"crawl" => array(
		"concurrency" => (int) env("SCAN_CRAWL_CONCURRENCY", 3),
		"delay_ms" => (int) env("SCAN_CRAWL_DELAY_MS", 200),
		"default_max_depth" => 3,
		"homepage_score_weight" => 2.0,
		"retry_on_202" => true,
		"retry_max_attempts" => 3,
		"retry_delay_ms" => 1500,
		"verify_ssl" => (bool) env("SCAN_CRAWL_VERIFY_SSL", true),
	),

	"thresholds" => array(
		"titleTag" => array("minLength" => 30, "maxLength" => 65),
		"metaDescription" => array("minLength" => 70, "maxLength" => 160),
		"performance" => array(
			"ttfbGoodSeconds" => 0.2,
			"ttfbWarnSeconds" => 0.6,
			"cssWarnCount" => 8,
			"jsWarnCount" => 10,
		),
		"coreWebVitals" => array(
			"lcpGoodMs" => 2500,
			"lcpWarnMs" => 4000,
			"clsGood" => 0.1,
			"clsWarn" => 0.25,
			"inpGoodMs" => 200,
			"inpWarnMs" => 500,
			"tbtGoodMs" => 200,
			"tbtWarnMs" => 600,
			"fcpGoodMs" => 1800,
			"fcpWarnMs" => 3000,
			"speedIndexGoodMs" => 3400,
			"speedIndexWarnMs" => 5800,
		),
		"trustPages" => array(
			"maxPagesToFetch" => 6,
			"fetchTimeoutSeconds" => 5,
			"minContentWords" => 200,
		),
		"contentReadability" => array(
			"minWords" => 300,
			"thinContentWords" => 100,
			"idealFleschKincaidMin" => 50,
			"poorFleschKincaidMax" => 30,
			"maxAvgSentenceLength" => 25,
		),
		"contentKeywords" => array(
			"goodPresenceCount" => 4,
			"warnPresenceCount" => 2,
		),
		"urlStructure" => array(
			"maxRecommendedLength" => 75,
			"warnLength" => 100,
			"maxRecommendedDepth" => 4,
			"warnDepth" => 6,
		),
	),

	/*
	|--------------------------------------------------------------------------
	| Module Scoring Weights (Research-Backed, Recalibrated Mar 2026)
	|--------------------------------------------------------------------------
	|
	| Cross-tool validated against WooRank, SEMrush, Ahrefs, Sitechecker,
	| Seobility, Ubersuggest, SE Ranking, Raven Tools, Screaming Frog, and
	| Lighthouse. See documentation/scoring-research.md for full methodology.
	|
	| Calibration target: mediocre site ~55 (WooRank eCommerce avg: 54.7),
	| good site ~70, excellent ~90. Warning multiplier set to 0.25 in
	| ScoreCalculator to match industry penalty severity.
	|
	| Modules with weight 0 are informational-only (displayed but not scored).
	| ScoreCalculator: sum(weight × multiplier) / totalWeight × 100
	|
	| Tier 1: Content Fundamentals — hard to get right, high fail rate
	| Tier 2: Technical Essentials — important but most CMSes handle by default
	| Tier 3: E-E-A-T & Trust — indirect quality signals, context-dependent
	| Tier 4: Supporting Factors — optimization opportunities
	| Tier 5: Baseline Expectations — should pass on any functioning site
	| Tier 6: WordPress-Specific — security/performance for WP sites only
	| Info-Only: nice-to-have checks with no confirmed ranking impact
	|
	*/
	"weights" => array(
		// Tier 1: Content Fundamentals
		// High fail rate (59-80% per Ahrefs) — these differentiate optimized from unoptimized.
		"titleTag" => 15,          // ERROR in SEMrush + Ahrefs + SF HIGH — #1 on-page signal
		"h1Tag" => 12,             // 59% missing (Ahrefs) — primary content signal
		"contentReadability" => 12, // Content quality = 23% of algo (First Page Sage)
		"metaDescription" => 10,   // 72% missing (Ahrefs) — CTR driver
		"contentDuplicate" => 8,   // SEMrush=ERROR but only 5% true duplicates — most sites pass

		// Tier 2: Technical Essentials
		// Important when broken, but most modern CMSes auto-configure these correctly.
		// Reduced from previous values — these were inflating scores for "default pass" sites.
		"canonicalTag" => 8,       // ERROR in SEMrush + Ahrefs — but CMSes auto-add canonical
		"redirectChain" => 5,      // Default state is no chains — most sites pass automatically
		"sitemapAnalysis" => 6,    // CMSes auto-generate — but broken sitemaps are ERROR-level
		"httpsRedirect" => 5,      // Default in 2026 — virtually all sites redirect HTTP→HTTPS
		"duplicateUrl" => 4,       // www/slash variations — default state is usually fine
		"httpHeaders" => 3,        // Basic HTTPS present on most modern sites
		"robotsTxt" => 4,          // Most sites have basic robots.txt — low differentiation
		"urlStructure" => 5,       // SF=MEDIUM — clean URLs require intentional effort

		// Tier 3: E-E-A-T & Trust Signals
		// Indirect quality signals — not direct ranking factors, context-dependent by site type.
		"eatTrustPages" => 4,      // About/Contact pages — indirect trust signal, not a ranking factor
		"eatAuthor" => 3,          // Author attribution — matters most for YMYL/advice content
		"eatBusinessSchema" => 3,  // Entity verification — helpful for rich results, not required
		"eatPrivacyTerms" => 2,    // Privacy Policy/Terms — indirect trust signal only
		"breadcrumbs" => 2,        // Breadcrumb schema — rich results enabler, not for all site types

		// Tier 4: Supporting Factors
		// Optimization opportunities — active effort that improves SEO.
		"linkAnalysis" => 7,       // Internal linking = topic authority signal (Google confirmed)
		"brokenLinks" => 8,        // ERROR across tools — broken links hurt UX and crawl budget
		"imageAnalysis" => 8,      // 80% missing alt text (Ahrefs) — confirmed image search signal
		"coreWebVitalsMobile" => 6,  // Google CWV tiebreaker signal — mobile-first indexing prioritized
		"coreWebVitalsDesktop" => 5, // Desktop CWV complements mobile for full performance picture
		"performanceHints" => 6,   // CWV is a tiebreaker per Google, not primary factor
		"h2h6Tags" => 5,           // Most CMS sites have headings — low differentiation value
		"schemaOrg" => 5,          // SEMrush=ERROR, SF=HIGH — enables rich results
		"schemaValidation" => 5,   // Deep field validation against Google rich result requirements
		"viewportTag" => 3,        // Every modern template includes viewport — near-zero differentiation
		"compressionCheck" => 4,   // Server-side compression and caching optimization
		"accessibilityCheck" => 3, // Form labels, skip nav, empty links, ARIA roles
		"semanticHtml" => 3,       // Landmark elements (header, nav, main, footer)
		"hreflang" => 3,           // International sites only; excluded when not applicable

		// Security
		// Defensive security posture — not direct ranking signals but protect site integrity.
		"sslCertificate" => 4,        // Most sites have valid SSL; critical-failure penalty via score_caps
		"securityHeaders" => 4,       // Defensive headers (CSP, HSTS, etc.) — many sites fail this
		"mixedContent" => 4,          // Most modern sites pass — low differentiation
		"exposedSensitiveFiles" => 5, // Exposed creds can lead to site compromise and blacklisting

		// Tier 5: Baseline Expectations
		// Should pass on any functioning site — near-zero differentiation value.
		"noindexCheck" => 3,       // Not blocking yourself is the default state
		"blacklistCheck" => 2,     // Not being malware is baseline — not checked by any major tool
		"robotsMeta" => 2,         // Not blocking crawlers is the default state
		"htmlLang" => 2,           // Basic HTML attribute, rarely missing
		"doctypeCharset" => 2,     // HTML5 boilerplate, nearly universal

		// Tier 6: WordPress-Specific (only scored for WP sites)
		"wpPlugins" => 7,          // Outdated/vulnerable plugins = security liability
		"wpTheme" => 5,            // Theme updates and security advisories

		// Info-Only: displayed in results but NOT scored (weight 0)
		"contentKeywords" => 0,    // Keyword presence — info-only, requires user-set keywords
		"socialTags" => 0,         // Open Graph is for social platforms, not Google
		"favicon" => 0,            // Cosmetic only
		"googleMapEmbed" => 0,     // Local SEO display, not a ranking signal
		"analyticsDetection" => 0, // Operational concern, not a ranking signal
		"wpDetection" => 0,        // Detection only, informational
		"techStackDetection" => 0, // Full technology inventory, informational
		"keywordConsistency" => 0, // Visual keyword presence matrix, informational
		"serpPreview" => 0,         // Preview generator, not ranked
	),

	/*
	|--------------------------------------------------------------------------
	| Critical Failure Score Caps
	|--------------------------------------------------------------------------
	|
	| Modules whose failure is so severe it should cap the final score.
	| A broken SSL cert triggers browser warnings that block all visitors —
	| no amount of good SEO compensates for an unreachable site.
	|
	| Format: module_key => max score (0-100) when that module status is "bad"
	|
	*/
	/*
	|--------------------------------------------------------------------------
	| Broken Links Probe Limit
	|--------------------------------------------------------------------------
	*/
	"broken_links_max_probes" => 50,

	"score_caps" => array(
		"sslCertificate" => 30,
	),
);
