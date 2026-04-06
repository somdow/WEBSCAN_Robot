<?php

/**
 * Shared UI configuration for scan result views.
 *
 * Centralizes sidebar groups, category icons, status filter metadata,
 * and score ring geometry so they aren't duplicated across partials.
 */

return array(

	/*
	|--------------------------------------------------------------------------
	| Score Ring Geometry
	|--------------------------------------------------------------------------
	|
	| SVG circle radius and computed circumference for the score donut chart.
	| Used in score-cards-single, score-cards-crawl, and show-page views.
	|
	*/

	"score_ring_radius" => 34,
	"score_ring_circumference" => 2 * M_PI * 34,

	/*
	|--------------------------------------------------------------------------
	| PDF Default Accent Color
	|--------------------------------------------------------------------------
	|
	| The default accent color used in PDF reports when the organization
	| has no custom brand color configured. Hex format with leading #.
	|
	*/

	"pdf_default_accent_color" => "#f25a15",

	/*
	|--------------------------------------------------------------------------
	| Category Icons (Heroicon SVG paths)
	|--------------------------------------------------------------------------
	|
	| Maps each analyzer category name to its Heroicon outline path data.
	| Used in sidebar navigation and result panel headers.
	|
	*/

	"category_descriptions" => array(
		"On-Page SEO" => "Title tags, meta descriptions, headings, and keyword usage that directly affect search rankings.",
		"Technical SEO" => "Crawlability, indexability, and technical foundations that help search engines access your site.",
		"Usability & Performance" => "Core Web Vitals, page speed, accessibility, and user experience signals that influence rankings.",
		"Graphs, Schema & Links" => "Open Graph tags, structured data markup, and link quality that help search engines and platforms understand your content.",
		"Local SEO" => "Schema markup and local signals that boost visibility in location-based searches.",
		"Utility" => "General site health checks including HTTP status, SSL, and redirects.",
		"WordPress" => "WordPress-specific checks for core version, plugins, and theme security.",
		"E-E-A-T Signals" => "Experience, expertise, authoritativeness, and trustworthiness indicators valued by Google.",
		"Content Analysis" => "Readability, content depth, and keyword relevance that drive organic traffic.",
		"Extras" => "Informational checks that don't affect your score.",
		"Technology Stack" => "Detected technologies, frameworks, libraries, and services powering this site.",
		"Core Web Vitals" => "Google's real-world performance metrics — LCP, CLS, and INP — that directly impact search rankings.",
		"Analytics" => "Detected analytics platforms, tag managers, and tracking scripts installed on your site.",
		"Security" => "SSL certificate validation, security headers, mixed content detection, exposed file scanning, and blacklist status for your site.",
	),

	"category_icons" => array(
		"On-Page SEO" => "M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z",
		"Technical SEO" => "M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z",
		"Usability & Performance" => "M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z",
		"Graphs, Schema & Links" => "M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418",
		"Local SEO" => "M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z",
		"Utility" => "M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z",
		"WordPress" => "M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z",
		"E-E-A-T Signals" => "M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z",
		"Content Analysis" => "M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z",
		"Extras" => "m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z",
		"Technology Stack" => "M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0L12 16.5l-5.571-2.25m11.142 0L21.75 16.5 12 21.75 2.25 16.5l4.179-2.25",
		"Core Web Vitals" => "M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z",
		"Analytics" => "M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z",
		"Security" => "M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z",
	),

	/*
	|--------------------------------------------------------------------------
	| Sidebar Groups
	|--------------------------------------------------------------------------
	|
	| Defines how categories are grouped in the scan results sidebar.
	| Sidebar navigation groups for scan results.
	|
	*/

	"sidebar_groups" => array(
		"SEO & Content" => array(
			"icon" => "m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z",
			"categories" => array("On-Page SEO", "Technical SEO", "Content Analysis", "E-E-A-T Signals", "Graphs, Schema & Links", "Local SEO"),
		),
		"Site Health" => array(
			"icon" => "M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z",
			"categories" => array("Core Web Vitals", "Usability & Performance", "Security", "Analytics", "Technology Stack", "Utility", "Extras", "WordPress"),
		),
	),

	/*
	|--------------------------------------------------------------------------
	| Score Category Groups
	|--------------------------------------------------------------------------
	|
	| Defines which analyzer categories contribute to each sub-score.
	| Used by ScoreCalculator::calculateSubScores() to partition modules.
	|
	*/

	"score_category_groups" => array(
		"seo" => array("On-Page SEO", "Technical SEO", "Graphs, Schema & Links", "Local SEO", "E-E-A-T Signals", "Content Analysis"),
		"health" => array("Core Web Vitals", "Usability & Performance", "Security", "Analytics", "Technology Stack", "Utility", "Extras", "WordPress"),
	),

	/*
	|--------------------------------------------------------------------------
	| Status Filter Configuration
	|--------------------------------------------------------------------------
	|
	| UI metadata for the status filter view in single-page scan results.
	| Maps status values to display labels, icon paths, and color classes.
	|
	*/

	"status_filters" => array(
		"ok" => array(
			"label" => "Passed Modules",
			"iconPath" => "m4.5 12.75 6 6 9-13.5",
			"iconColor" => "text-emerald-500",
			"borderColor" => "border-emerald-200",
			"bgColor" => "bg-emerald-50",
		),
		"warning" => array(
			"label" => "Warning Modules",
			"iconPath" => "M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z",
			"iconColor" => "text-amber-500",
			"borderColor" => "border-amber-200",
			"bgColor" => "bg-amber-50",
		),
		"bad" => array(
			"label" => "Failed Modules",
			"iconPath" => "M6 18 18 6M6 6l12 12",
			"iconColor" => "text-red-500",
			"borderColor" => "border-red-200",
			"bgColor" => "bg-red-50",
		),
		"info" => array(
			"label" => "Info Modules",
			"iconPath" => "m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z",
			"iconColor" => "text-blue-400",
			"borderColor" => "border-blue-200",
			"bgColor" => "bg-blue-50",
		),
	),
);
