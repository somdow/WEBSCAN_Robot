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
	| Module Scoring Weights (Research-Backed, Rebalanced Feb 2026)
	|--------------------------------------------------------------------------
	|
	| Cross-tool validated against SEMrush, Ahrefs, Screaming Frog, Lighthouse,
	| and WooRank methodologies. See documentation/scoring-research.md for full
	| methodology and sources.
	|
	| Modules with weight 0 are informational-only (displayed but not scored).
	| ScoreCalculator: sum(weight × multiplier) / totalWeight × 100
	|
	| Tier 1: Content Fundamentals — what makes or breaks SEO
	| Tier 2: Technical Essentials — confirmed signals requiring active configuration
	| Tier 3: E-E-A-T & Trust — quality/trustworthiness signals
	| Tier 4: Supporting Factors — optimization opportunities
	| Tier 5: Baseline Expectations — should pass on any functioning site
	| Tier 6: WordPress-Specific — security/performance for WP sites only
	| Info-Only: nice-to-have checks with no confirmed ranking impact
	|
	*/
	"weights" => array(
		// Tier 1: Content Fundamentals
		// These ARE SEO — active optimization that directly impacts rankings and CTR.
		"titleTag" => 15,          // ERROR in SEMrush + Ahrefs + SF HIGH — #1 on-page signal
		"contentKeywords" => 12,   // Relevance = 0.47 correlation, Google's #1 ranking factor
		"h1Tag" => 10,             // WARNING in SEMrush/Ahrefs, MEDIUM in SF — primary content signal
		"contentReadability" => 9, // Content quality = 23% of algo (First Page Sage)
		"metaDescription" => 8,    // Ahrefs=ERROR, SEMrush=WARNING, SF=LOW — CTR driver
		"contentDuplicate" => 8,   // SEMrush=ERROR — dilutes ranking signals, indexation confusion

		// Tier 2: Technical Essentials
		// Confirmed ranking signals that require active configuration.
		"canonicalTag" => 8,       // ERROR in SEMrush + Ahrefs — duplicate content resolution
		"redirectChain" => 7,      // ERROR everywhere — wastes crawl budget and link equity
		"sitemapAnalysis" => 6,    // SEMrush=ERROR for sitemap issues — crawl efficiency
		"httpsRedirect" => 7,      // HTTPS is confirmed ranking signal — HTTP must 301 to HTTPS
		"duplicateUrl" => 6,       // www/slash variations must redirect — prevents split indexing
		"httpHeaders" => 5,        // HTTPS is confirmed signal (2% weight)
		"robotsTxt" => 5,          // Controls crawl directives, Lighthouse scores this
		"urlStructure" => 5,       // SF=MEDIUM — clean URLs improve crawlability

		// Tier 3: E-E-A-T & Trust Signals
		// Google Quality Rater Guidelines weight E-E-A-T heavily for YMYL topics.
		"eatTrustPages" => 8,      // About/Contact pages = real effort, trust signal
		"eatAuthor" => 7,          // Author attribution signals expertise
		"eatBusinessSchema" => 5,  // Entity verification via Organization/LocalBusiness schema
		"eatPrivacyTerms" => 4,    // Privacy Policy/Terms signal legitimacy

		// Tier 4: Supporting Factors
		// Optimization opportunities — active effort that improves SEO.
		"linkAnalysis" => 7,       // Internal linking = topic authority signal (Google confirmed)
		"brokenLinks" => 6,        // Broken outbound links hurt UX and waste crawl budget
		"imageAnalysis" => 6,      // WARNING across tools — alt text + modern formats
		"coreWebVitalsMobile" => 6,  // Google CWV tiebreaker signal — mobile-first indexing prioritized
		"coreWebVitalsDesktop" => 5, // Desktop CWV complements mobile for full performance picture
		"performanceHints" => 6,   // CWV is a tiebreaker per Google, not primary factor
		"h2h6Tags" => 5,           // Content structure and heading hierarchy signal
		"schemaOrg" => 5,          // SEMrush=ERROR, SF=HIGH — enables rich results
		"schemaValidation" => 5,   // Deep field validation against Google rich result requirements
		"viewportTag" => 5,        // SEMrush=ERROR but every modern template has it
		"compressionCheck" => 4,   // Server-side compression and caching optimization
		"accessibilityCheck" => 3, // Form labels, skip nav, empty links, ARIA roles
		"breadcrumbs" => 3,        // Breadcrumb schema for rich results
		"semanticHtml" => 3,       // Landmark elements (header, nav, main, footer)
		"hreflang" => 3,           // International sites only; excluded when not applicable

		// Security
		// Defensive security posture — not direct ranking signals but protect site integrity.
		"sslCertificate" => 6,        // Normal weight when valid; critical-failure penalty applied when broken (see score_caps)
		"securityHeaders" => 4,       // Defensive headers (CSP, HSTS, etc.), not direct ranking signal
		"mixedContent" => 6,          // Undermines HTTPS which IS a confirmed ranking signal
		"exposedSensitiveFiles" => 5, // Exposed creds can lead to site compromise and blacklisting

		// Tier 5: Baseline Expectations
		// Should pass on any functioning site — not an achievement, just not broken.
		"noindexCheck" => 4,       // WARNING in SEMrush + Ahrefs — not blocking yourself is default
		"blacklistCheck" => 3,     // Not checked by any major tool — not being malware is baseline
		"robotsMeta" => 3,         // WARNING — not blocking crawlers is default state
		"htmlLang" => 2,           // Basic HTML attribute, rarely missing
		"doctypeCharset" => 2,     // HTML5 boilerplate, nearly universal

		// Tier 6: WordPress-Specific (only scored for WP sites)
		"wpPlugins" => 7,          // Outdated/vulnerable plugins = security liability
		"wpTheme" => 5,            // Theme updates and security advisories

		// Info-Only: displayed in results but NOT scored (weight 0)
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
