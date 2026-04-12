<?php

/**
 * WordPress plugin detection fingerprints.
 *
 * Used by WpPluginsAnalyzer to identify plugins from HTML source.
 * Organized by detection method: script handles, inline JS globals,
 * HTML comments, meta generators, and structural HTML patterns.
 */

return array(

	/*
	|--------------------------------------------------------------------------
	| Core WordPress Script/Style Handles
	|--------------------------------------------------------------------------
	|
	| Handles enqueued by WordPress core that must be excluded from plugin
	| detection. WordPress outputs <script id="{handle}-js"> and
	| <link id="{handle}-css"> for every enqueued asset.
	|
	*/

	"core_handles" => array(
		"jquery", "jquery-core", "jquery-migrate", "jquery-ui-core",
		"jquery-ui-dialog", "jquery-ui-tabs", "jquery-ui-accordion",
		"jquery-ui-sortable", "jquery-ui-draggable", "jquery-ui-droppable",
		"jquery-ui-datepicker", "jquery-ui-autocomplete", "jquery-ui-slider",
		"jquery-ui-tooltip", "jquery-ui-progressbar",
		"wp-embed", "wp-emoji", "wp-emoji-release", "wp-polyfill",
		"wp-hooks", "wp-i18n", "wp-dom-ready", "wp-api-fetch",
		"wp-block-library", "wp-block-library-theme",
		"wp-components", "wp-compose", "wp-data", "wp-element",
		"wp-editor", "wp-edit-post", "wp-plugins", "wp-primitives",
		"global-styles", "global-styles-inline", "classic-theme-styles",
		"comment-reply", "admin-bar", "dashicons",
		"hoverintent-js", "imagesloaded", "masonry", "mediaelement",
		"underscore", "backbone", "heartbeat", "thickbox",
		"wp-a11y", "wp-api", "wp-auth-check", "wp-backbone",
		"wp-mediaelement", "wp-pointer", "wp-util",
		"regenerator-runtime", "react", "react-dom", "lodash",
	),

	/*
	|--------------------------------------------------------------------------
	| Script Handle → Plugin Slug Map
	|--------------------------------------------------------------------------
	|
	| Maps known WordPress script/style handles to their plugin slug.
	| Only needed when the handle name differs from the plugin slug.
	|
	*/

	"handle_to_slug" => array(
		/* Contact Form 7 */
		"wpcf7" => "contact-form-7",
		"contact-form-7" => "contact-form-7",

		/* Elementor */
		"elementor-frontend" => "elementor",
		"elementor-common" => "elementor",
		"elementor-dialog" => "elementor",
		"elementor-pro-frontend" => "elementor-pro",

		/* WooCommerce */
		"woocommerce" => "woocommerce",
		"wc-cart" => "woocommerce",
		"wc-checkout" => "woocommerce",
		"wc-cart-fragments" => "woocommerce",
		"wc-add-to-cart" => "woocommerce",
		"wc-single-product" => "woocommerce",
		"woocommerce-payments" => "woocommerce-payments",

		/* Forms */
		"wpforms" => "wpforms-lite",
		"wpforms-gutenberg" => "wpforms-lite",
		"gform_gravityforms" => "gravityforms",
		"gravityforms" => "gravityforms",
		"nf-front-end" => "ninja-forms",
		"ninja-forms-display" => "ninja-forms",
		"formidable" => "formidable",

		/* SEO */
		"aioseo-app" => "all-in-one-seo-pack",
		"rank-math-frontend" => "seo-by-rank-math",
		"wp-seopress-global" => "wp-seopress",
		"yoast-seo-premium-redirect" => "wordpress-seo-premium",

		/* Caching / Performance */
		"rocket-lazyload" => "wp-rocket",
		"litespeed-cache" => "litespeed-cache",
		"sg-cachepress" => "sg-cachepress",

		/* Analytics / Tracking */
		"monsterinsights-frontend-script" => "google-analytics-for-wordpress",
		"google-analytics-for-wordpress" => "google-analytics-for-wordpress",
		"exactmetrics-frontend-script" => "google-analytics-dashboard-for-wp",
		"google-site-kit" => "google-site-kit",
		"sitekit-main" => "google-site-kit",

		/* Jetpack */
		"jetpack-stats" => "jetpack",
		"jetpack-lazy-images" => "jetpack",

		/* Security */
		"wordfence-ls" => "wordfence",

		/* Page Builders */
		"beaver-builder-frontend" => "beaver-builder-lite-version",
		"fl-builder-frontend" => "beaver-builder-lite-version",
		"js_composer_front" => "js_composer",
		"wpb_composer_front" => "js_composer",
		"oxygen-frontend" => "oxygen",
		"brizy-frontend" => "brizy",
		"siteorigin-panels-front" => "siteorigin-panels",

		/* Block Plugins */
		"kadence-blocks" => "kadence-blocks",
		"uagb-frontend" => "ultimate-addons-for-gutenberg",
		"spectra-frontend" => "ultimate-addons-for-gutenberg",
		"generateblocks" => "generateblocks",
		"stackable-frontend" => "stackable-ultimate-gutenberg-blocks",

		/* Elementor Addons */
		"ultimate-elementor" => "ultimate-elementor",
		"eael-general" => "essential-addons-for-elementor-lite",
		"happy-elementor-addons" => "happy-elementor-addons",
		"powerpack-frontend" => "powerpack-lite-for-elementor",

		/* Images */
		"wp-smush-lazy-load" => "wp-smushit",

		/* Tables */
		"tablepress" => "tablepress",

		/* Sliders */
		"revslider" => "revslider",
		"rs-plugin-settings" => "revslider",

		/* Cookie / GDPR */
		"cookie-law-info" => "cookie-law-info",
		"cookieyes" => "cookie-law-info",
		"real-cookie-banner" => "real-cookie-banner",
		"complianz" => "complianz-gdpr",

		/* Marketing / Email */
		"mailchimp-for-wp" => "mailchimp-for-wp",
		"mc4wp-forms" => "mailchimp-for-wp",
		"optinmonster" => "optinmonster",

		/* E-Commerce / Membership */
		"easy-digital-downloads" => "easy-digital-downloads",
		"edd-ajax" => "easy-digital-downloads",
		"memberpress" => "memberpress",
		"mepr-frontend" => "memberpress",
		"surecart" => "surecart",
		"cartflows" => "cartflows",

		/* LMS / Community */
		"learndash-front" => "sfwd-lms",
		"buddypress" => "buddypress",
		"bbpress" => "bbpress",

		/* Events */
		"tribe-events-calendar" => "the-events-calendar",
		"tribe-common" => "the-events-calendar",
		"the-events-calendar" => "the-events-calendar",

		/* Misc */
		"really-simple-ssl" => "really-simple-ssl",
		"limit-login-attempts" => "limit-login-attempts-reloaded",
		"duplicate-post" => "duplicate-post",
		"classic-editor" => "classic-editor",
		"redirection" => "redirection",
		"wp-mail-smtp" => "wp-mail-smtp",
		"amp" => "amp",
	),

	/*
	|--------------------------------------------------------------------------
	| Inline Script Fingerprints
	|--------------------------------------------------------------------------
	|
	| JavaScript global variables and config objects injected by plugins
	| into inline <script> blocks. Searched via case-insensitive stripos().
	|
	*/

	"script_globals" => array(
		/* Forms */
		"wpcf7" => "contact-form-7",
		"wpforms_settings" => "wpforms-lite",
		"wpformsModern" => "wpforms-lite",
		"gf_global" => "gravityforms",
		"gform_init_scripts" => "gravityforms",
		"nfForms" => "ninja-forms",
		"frmGlobal" => "formidable",
		"frmFrontForm" => "formidable",

		/* Page Builders */
		"elementorFrontend" => "elementor",
		"elementorFrontendConfig" => "elementor",
		"elementorProFrontend" => "elementor-pro",
		"FLBuilderLayout" => "beaver-builder-lite-version",
		"vc_js" => "js_composer",

		/* WooCommerce */
		"wc_add_to_cart_params" => "woocommerce",
		"woocommerce_params" => "woocommerce",
		"wc_cart_params" => "woocommerce",

		/* SEO */
		"aioseo" => "all-in-one-seo-pack",
		"aioseoMeta" => "all-in-one-seo-pack",
		"rankMath" => "seo-by-rank-math",
		"seopress" => "wp-seopress",

		/* Analytics / Tracking */
		"monsterinsights_frontend" => "google-analytics-for-wordpress",
		"__gaTracker" => "google-analytics-for-wordpress",
		"exactmetrics_frontend" => "google-analytics-dashboard-for-wp",
		"gtm4wp" => "duracelltomi-google-tag-manager",

		/* Jetpack */
		"_stq" => "jetpack",
		"jp_carousel" => "jetpack",
		"JetpackLazyImagesL10n" => "jetpack",

		/* Security */
		"wfParam" => "wordfence",
		"wordfenceAJAXWatcher" => "wordfence",

		/* Caching / Performance */
		"RocketLazyLoad" => "wp-rocket",
		"RocketPreloadLinksConfig" => "wp-rocket",
		"LiteSpeed" => "litespeed-cache",
		"sgCachePress" => "sg-cachepress",
		"perfmattersLazy" => "perfmatters",

		/* Sliders */
		"setREVStartSize" => "revslider",
		"revslider_showDoubleJqueryError" => "revslider",

		/* Cookie / GDPR */
		"cookielawinfo" => "cookie-law-info",
		"cky_consent" => "cookie-law-info",
		"complianz" => "complianz-gdpr",
		"cmplz_categories" => "complianz-gdpr",
		"rcb_consent" => "real-cookie-banner",

		/* Marketing */
		"optinMonster" => "optinmonster",
		"mc4wp_forms_config" => "mailchimp-for-wp",

		/* E-Commerce / Membership */
		"surecart_block_data" => "surecart",
		"cartflows_vars" => "cartflows",
		"EDD_Checkout" => "easy-digital-downloads",

		/* Block Plugins */
		"kadence_blocks_params" => "kadence-blocks",
		"uagb_data" => "ultimate-addons-for-gutenberg",

		/* Images */
		"smush" => "wp-smushit",

		/* LMS / Community */
		"learndash" => "sfwd-lms",
		"bbpJSData" => "bbpress",
		"bp_nouveau" => "buddypress",

		/* Events */
		"tribe_ev" => "the-events-calendar",
		"TribeCalendar" => "the-events-calendar",

		/* Tables */
		"tablepress_datatables" => "tablepress",

		/* Misc */
		"wpp_params" => "wordpress-popular-posts",
	),

	/*
	|--------------------------------------------------------------------------
	| HTML Comment Fingerprints
	|--------------------------------------------------------------------------
	|
	| Regex patterns matched against the full HTML source to detect plugins
	| that leave identifying comments. Capture group 1 = version (optional).
	|
	*/

	"comment_patterns" => array(
		array("pattern" => "/<!--\s*This site is optimized with the Yoast SEO.*?v([\d.]+)/i", "slug" => "wordpress-seo"),
		array("pattern" => "/<!--\s*All in One SEO(?:\s+Pack)?\s*([\d.]+)?/i", "slug" => "all-in-one-seo-pack"),
		array("pattern" => "/<!--\s*Rank Math SEO.*?v?([\d.]+)?/i", "slug" => "seo-by-rank-math"),
		array("pattern" => "/<!--\s*SEOPress\s*([\d.]+)?/i", "slug" => "wp-seopress"),
		array("pattern" => "/<!--\s*Performance optimized by W3 Total Cache/i", "slug" => "w3-total-cache"),
		array("pattern" => "/<!--\s*WP Super Cache/i", "slug" => "wp-super-cache"),
		array("pattern" => "/<!--\s*Starter Templates\s*v?([\d.]+)?/i", "slug" => "starter-templates"),
		array("pattern" => "/<!--\s*Starter Sites\s*v?([\d.]+)?/i", "slug" => "starter-sites"),
		array("pattern" => "/<!--\s*Autoptimize/i", "slug" => "autoptimize"),
		array("pattern" => "/<!--\s*(?:LiteSpeed Cache|Page generated by LiteSpeed Cache)/i", "slug" => "litespeed-cache"),
		array("pattern" => "/<!--\s*WP Rocket/i", "slug" => "wp-rocket"),
		array("pattern" => "/<!--\s*noptimize/i", "slug" => "autoptimize"),
		array("pattern" => "/<!--\s*Jetpack Open Graph Tags/i", "slug" => "jetpack"),
		array("pattern" => "/<!--\s*(?:Created|Generated) by WPBakery/i", "slug" => "js_composer"),
		array("pattern" => "/<!--\s*Perfmatters/i", "slug" => "perfmatters"),
		array("pattern" => "/<!--\s*Flying Press/i", "slug" => "flying-press"),
		array("pattern" => "/<!--\s*(?:SG Optimizer|Cache served by SiteGround Optimizer)/i", "slug" => "sg-cachepress"),
		array("pattern" => "/<!--\s*(?:This site uses|Powered by) [Ff]lavor\s*v?([\d.]+)?/i", "slug" => "flavor"),
		array("pattern" => "/<!--\s*Powered by Starter Sites/i", "slug" => "starter-sites"),
	),

	/*
	|--------------------------------------------------------------------------
	| Meta Generator Patterns
	|--------------------------------------------------------------------------
	|
	| Regex patterns matched against <meta name="generator"> content values.
	| Capture group 1 = version (optional).
	|
	*/

	"generator_patterns" => array(
		"/^Powered by Slider Revolution\s+([\d.]+)/i" => "revslider",
		"/^Starter Templates\s*v?([\d.]+)?/i" => "starter-templates",
		"/^Starter Sites\s*v?([\d.]+)?/i" => "starter-sites",
		"/^Site Kit by Google\s*v?([\d.]+)?/i" => "google-site-kit",
		"/^FLAVOR\s*v?([\d.]+)?/i" => "flavor",
		"/^WooCommerce\s*([\d.]+)?/i" => "woocommerce",
		"/^Elementor\s*([\d.]+)?/i" => "elementor",
		"/^Powered by WPBakery/i" => "js_composer",
		"/^Visual Composer/i" => "js_composer",
	),

	/*
	|--------------------------------------------------------------------------
	| HTML Structure Fingerprints
	|--------------------------------------------------------------------------
	|
	| CSS classes, data attributes, and markup patterns detected from the HTML.
	| Types: "string" (stripos), "regex" (preg_match), "xpath" (DOM query).
	|
	*/

	"html_patterns" => array(
		/* Page Builders */
		array("type" => "string", "pattern" => "data-elementor-type", "slug" => "elementor"),
		array("type" => "string", "pattern" => "elementor-kit-", "slug" => "elementor"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\belementor-widget\b/i", "slug" => "elementor"),
		array("type" => "string", "pattern" => "fl-builder", "slug" => "beaver-builder-lite-version"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bfl-row\b/i", "slug" => "beaver-builder-lite-version"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bvc_row\b/i", "slug" => "js_composer"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bwpb_row\b/i", "slug" => "js_composer"),
		array("type" => "string", "pattern" => "data-vc-full-width", "slug" => "js_composer"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bct-section\b/i", "slug" => "oxygen"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\boxygen-body\b/i", "slug" => "oxygen"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bbrz-root\b/i", "slug" => "brizy"),
		array("type" => "string", "pattern" => "data-brz-", "slug" => "brizy"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bso-panel\b/i", "slug" => "siteorigin-panels"),

		/* WooCommerce */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bwoocommerce\b/i", "slug" => "woocommerce"),
		array("type" => "string", "pattern" => "data-product_id", "slug" => "woocommerce"),

		/* Forms */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bwpcf7-form\b/i", "slug" => "contact-form-7"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bwpforms-form\b/i", "slug" => "wpforms-lite"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bgform_wrapper\b/i", "slug" => "gravityforms"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bnf-form-cont\b/i", "slug" => "ninja-forms"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bfrm_forms\b/i", "slug" => "formidable"),

		/* SEO — structured data markers */
		array("type" => "string", "pattern" => "yoast-schema-graph", "slug" => "wordpress-seo"),
		array("type" => "string", "pattern" => "rank-math-schema", "slug" => "seo-by-rank-math"),

		/* Caching / Performance */
		array("type" => "string", "pattern" => "data-rocket-lazyload", "slug" => "wp-rocket"),
		array("type" => "string", "pattern" => "rocket-lazyload", "slug" => "wp-rocket"),
		array("type" => "string", "pattern" => "data-perfmatters-type", "slug" => "perfmatters"),
		array("type" => "string", "pattern" => "data-litespeed-", "slug" => "litespeed-cache"),

		/* Image Optimization */
		array("type" => "string", "pattern" => "data-smush-", "slug" => "wp-smushit"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bwp-smush-lazy\b/i", "slug" => "wp-smushit"),
		array("type" => "string", "pattern" => "data-spai", "slug" => "shortpixel-adaptive-images"),

		/* Cookie / GDPR */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bcookie-law-info\b/i", "slug" => "cookie-law-info"),
		array("type" => "string", "pattern" => "data-cky-tag", "slug" => "cookie-law-info"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bcmplz-\b/i", "slug" => "complianz-gdpr"),
		array("type" => "string", "pattern" => "id=\"real-cookie-banner", "slug" => "real-cookie-banner"),

		/* Sliders */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\brev_slider\b/i", "slug" => "revslider"),
		array("type" => "string", "pattern" => "rs-fullwidth-wrap", "slug" => "revslider"),

		/* Tables */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\btablepress\b/i", "slug" => "tablepress"),

		/* Events */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\btribe-events\b/i", "slug" => "the-events-calendar"),
		array("type" => "string", "pattern" => "tribe-common", "slug" => "the-events-calendar"),

		/* Gutenberg Block Plugins */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bkb-row-layout\b/i", "slug" => "kadence-blocks"),
		array("type" => "string", "pattern" => "data-kb-block", "slug" => "kadence-blocks"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\buagb-\b/i", "slug" => "ultimate-addons-for-gutenberg"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bgb-container\b/i", "slug" => "generateblocks"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bstk-block\b/i", "slug" => "stackable-ultimate-gutenberg-blocks"),

		/* Elementor Addons */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\buael-\b/i", "slug" => "ultimate-elementor"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\beael-\b/i", "slug" => "essential-addons-for-elementor-lite"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bhappy-addon\b/i", "slug" => "happy-elementor-addons"),

		/* Membership / LMS / Community */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bmepr-\b/i", "slug" => "memberpress"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bedd-\b/i", "slug" => "easy-digital-downloads"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\blearndash\b/i", "slug" => "sfwd-lms"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bbuddypress\b/i", "slug" => "buddypress"),
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bbbpress\b/i", "slug" => "bbpress"),

		/* Marketing */
		array("type" => "regex", "pattern" => "/class=\"[^\"]*\bmc4wp-form\b/i", "slug" => "mailchimp-for-wp"),
		array("type" => "string", "pattern" => "data-om-render", "slug" => "optinmonster"),

		/* AMP */
		array("type" => "string", "pattern" => "amp-custom", "slug" => "amp"),
	),

	/*
	|--------------------------------------------------------------------------
	| REST API Namespace → Plugin Slug Map
	|--------------------------------------------------------------------------
	|
	| WordPress plugins register REST API namespaces at /wp-json/.
	| A single GET request reveals all registered namespaces.
	| Map namespace prefixes (before the /) to plugin slugs.
	|
	*/

	"rest_namespace_to_slug" => array(
		/* WooCommerce */
		"wc" => "woocommerce",
		"wc-analytics" => "woocommerce",
		"wc-admin" => "woocommerce",

		/* SEO */
		"yoast" => "wordpress-seo",
		"aioseo" => "all-in-one-seo-pack",
		"rankmath" => "seo-by-rank-math",
		"seopress" => "wp-seopress",

		/* Page Builders */
		"elementor" => "elementor",
		"brizy" => "brizy",
		"fl-builder" => "beaver-builder-lite-version",

		/* Forms */
		"wpforms" => "wpforms-lite",
		"frm" => "formidable",
		"frm-admin" => "formidable",
		"gf" => "gravityforms",
		"nf-submissions" => "ninja-forms",
		"contact-form-7" => "contact-form-7",

		/* Jetpack */
		"jetpack" => "jetpack",

		/* Analytics */
		"google-site-kit" => "google-site-kit",
		"monsterinsights" => "google-analytics-for-wordpress",
		"exactmetrics" => "google-analytics-dashboard-for-wp",

		/* Security */
		"wordfence" => "wordfence",
		"ithemes-security" => "developer",
		"really-simple-ssl" => "really-simple-ssl",

		/* Caching */
		"wp-rocket" => "wp-rocket",
		"litespeed" => "litespeed-cache",

		/* Cookie / GDPR */
		"cookieyes" => "cookie-law-info",
		"complianz" => "complianz-gdpr",
		"real-cookie-banner" => "real-cookie-banner",

		/* E-Commerce / Membership */
		"edd" => "easy-digital-downloads",
		"mepr" => "memberpress",
		"surecart" => "surecart",
		"learndash" => "sfwd-lms",
		"buddypress" => "buddypress",
		"bbpress" => "bbpress",

		/* Marketing */
		"mailchimp-for-wp" => "mailchimp-for-wp",
		"mc4wp" => "mailchimp-for-wp",
		"optinmonster" => "optinmonster",

		/* Events */
		"tribe" => "the-events-calendar",

		/* Media */
		"redirection" => "redirection",
		"wp-mail-smtp" => "wp-mail-smtp",
		"updraftplus" => "updraftplus",
	),

	/*
	|--------------------------------------------------------------------------
	| Handle Suffixes to Strip for API Slug Lookup
	|--------------------------------------------------------------------------
	|
	| When trying an unknown script handle as a WordPress.org slug, strip
	| these common suffixes first. E.g. "elementor-frontend" → "elementor".
	|
	*/

	"handle_strip_suffixes" => array(
		"-frontend", "-public", "-script", "-scripts", "-style", "-styles",
		"-main", "-core", "-common", "-app", "-bundle", "-init",
		"-display", "-global", "-general", "-lite", "-pro",
		"_frontend", "_public", "_script", "_scripts",
		"_main", "_core", "_common", "_app", "_bundle", "_init",
	),
);
