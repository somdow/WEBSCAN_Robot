<?php

namespace App\Services\Analyzers\Utility;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

/**
 * Detects and categorizes the full technology stack of a website.
 * Combines WhatCMS API data (from Phase 1 wpDetection) with HTML signal
 * detection for a comprehensive technology inventory.
 *
 * Technologies may appear in multiple constant arrays (e.g. WordPress in both
 * INLINE_SCRIPT_PATTERNS and GENERATOR_PATTERNS). This is intentional — each
 * constant targets a different signal type. Runtime deduplication in addTechnology()
 * ensures only one entry per technology in the final output.
 */
class TechStackDetectionAnalyzer implements AnalyzerInterface
{
	/** Known script URL patterns mapped to technology name and category. */
	private const SCRIPT_SIGNATURES = array(
		"jquery" => array("name" => "jQuery", "category" => "JavaScript Library"),
		"react" => array("name" => "React", "category" => "JavaScript Framework"),
		"vue" => array("name" => "Vue.js", "category" => "JavaScript Framework"),
		"angular" => array("name" => "Angular", "category" => "JavaScript Framework"),
		"alpine" => array("name" => "Alpine.js", "category" => "JavaScript Framework"),
		"htmx" => array("name" => "htmx", "category" => "JavaScript Framework"),
		"bootstrap" => array("name" => "Bootstrap", "category" => "CSS Framework"),
		"tailwindcss" => array("name" => "Tailwind CSS", "category" => "CSS Framework"),
		"font-awesome" => array("name" => "Font Awesome", "category" => "Icon Library"),
		"fontawesome" => array("name" => "Font Awesome", "category" => "Icon Library"),
		"gtag" => array("name" => "Google Analytics (gtag)", "category" => "Analytics"),
		"analytics.js" => array("name" => "Google Analytics (Universal)", "category" => "Analytics"),
		"ga.js" => array("name" => "Google Analytics (Classic)", "category" => "Analytics"),
		"gtm.js" => array("name" => "Google Tag Manager", "category" => "Analytics"),
		"googletagmanager" => array("name" => "Google Tag Manager", "category" => "Analytics"),
		"fbevents.js" => array("name" => "Facebook Pixel", "category" => "Analytics"),
		"hotjar" => array("name" => "Hotjar", "category" => "Analytics"),
		"clarity" => array("name" => "Microsoft Clarity", "category" => "Analytics"),
		"plausible" => array("name" => "Plausible Analytics", "category" => "Analytics"),
		"matomo" => array("name" => "Matomo", "category" => "Analytics"),
		"livewire" => array("name" => "Livewire", "category" => "JavaScript Framework"),
		"moment" => array("name" => "Moment.js", "category" => "JavaScript Library"),
		"lodash" => array("name" => "Lodash", "category" => "JavaScript Library"),
		"underscore" => array("name" => "Underscore.js", "category" => "JavaScript Library"),
		"axios" => array("name" => "Axios", "category" => "JavaScript Library"),
		"gsap" => array("name" => "GSAP", "category" => "JavaScript Library"),
		"swiper" => array("name" => "Swiper", "category" => "JavaScript Library"),
		"slick" => array("name" => "Slick Slider", "category" => "JavaScript Library"),
		"owl.carousel" => array("name" => "Owl Carousel", "category" => "JavaScript Library"),
		"lazysizes" => array("name" => "LazySizes", "category" => "JavaScript Library"),
		"recaptcha" => array("name" => "Google reCAPTCHA", "category" => "Security"),
		"hcaptcha" => array("name" => "hCaptcha", "category" => "Security"),
		"turnstile" => array("name" => "Cloudflare Turnstile", "category" => "Security"),
		"stripe.com" => array("name" => "Stripe", "category" => "Payment"),
		"js.stripe.com" => array("name" => "Stripe", "category" => "Payment"),
		"paypal.com/sdk" => array("name" => "PayPal", "category" => "Payment"),
		"embed.tawk.to" => array("name" => "Tawk.to", "category" => "Live Chat"),
		"crisp.chat" => array("name" => "Crisp", "category" => "Live Chat"),
		"intercom" => array("name" => "Intercom", "category" => "Live Chat"),
		"drift" => array("name" => "Drift", "category" => "Live Chat"),
		"zendesk" => array("name" => "Zendesk", "category" => "Live Chat"),
		"cookiebot" => array("name" => "Cookiebot", "category" => "Cookie Consent"),
		"cookieconsent" => array("name" => "Cookie Consent", "category" => "Cookie Consent"),
		"onetrust" => array("name" => "OneTrust", "category" => "Cookie Consent"),
		"sentry" => array("name" => "Sentry", "category" => "Error Tracking"),
		"bugsnag" => array("name" => "Bugsnag", "category" => "Error Tracking"),
		"segment.com" => array("name" => "Segment", "category" => "Analytics"),
		"mixpanel" => array("name" => "Mixpanel", "category" => "Analytics"),
		"amplitude" => array("name" => "Amplitude", "category" => "Analytics"),
		"hubspot" => array("name" => "HubSpot", "category" => "Marketing"),
		"mailchimp" => array("name" => "Mailchimp", "category" => "Marketing"),
		"sumo.com" => array("name" => "Sumo", "category" => "Marketing"),
		"optimizely" => array("name" => "Optimizely", "category" => "A/B Testing"),
		"googleoptimize" => array("name" => "Google Optimize", "category" => "A/B Testing"),
		"maps.googleapis.com" => array("name" => "Google Maps", "category" => "Mapping"),
		"maps.google.com" => array("name" => "Google Maps", "category" => "Mapping"),
		"api.mapbox.com" => array("name" => "Mapbox", "category" => "Mapping"),
		"unpkg.com" => array("name" => "unpkg CDN", "category" => "CDN"),
		"cdnjs.cloudflare.com" => array("name" => "cdnjs", "category" => "CDN"),
		"cdn.jsdelivr.net" => array("name" => "jsDelivr", "category" => "CDN"),
		"polyfill.io" => array("name" => "Polyfill.io", "category" => "JavaScript Library"),
	);

	/** Inline script content patterns — matched against <script> tags without src. */
	private const INLINE_SCRIPT_PATTERNS = array(
		"/window\\.__NEXT_DATA__/" => array("name" => "Next.js", "category" => "JavaScript Framework"),
		"/window\\.__NUXT__/" => array("name" => "Nuxt.js", "category" => "JavaScript Framework"),
		"/Shopify\\./" => array("name" => "Shopify", "category" => "E-commerce Platform"),
		"/_wpCustomizeSettings/" => array("name" => "WordPress", "category" => "CMS"),
		"/wp\\.customize/" => array("name" => "WordPress", "category" => "CMS"),
		"/wc_add_to_cart_params/" => array("name" => "WooCommerce", "category" => "E-commerce Plugin"),
		"/Wix\\./" => array("name" => "Wix", "category" => "Website Builder"),
		"/squarespace/" => array("name" => "Squarespace", "category" => "Website Builder"),
		"/webflow/" => array("name" => "Webflow", "category" => "Website Builder"),
		"/elementorFrontend/" => array("name" => "Elementor", "category" => "Page Builder"),
		"/var defined_vars/" => array("name" => "Laravel", "category" => "Backend Framework"),
		"/Laravel\\./" => array("name" => "Laravel", "category" => "Backend Framework"),
		"/Rails\\./" => array("name" => "Ruby on Rails", "category" => "Backend Framework"),
		"/django/" => array("name" => "Django", "category" => "Backend Framework"),
		"/__remixContext/" => array("name" => "Remix", "category" => "JavaScript Framework"),
		"/gatsby/" => array("name" => "Gatsby", "category" => "Static Site Generator"),
		"/astro/" => array("name" => "Astro", "category" => "JavaScript Framework"),
		"/fb:app_id/" => array("name" => "Facebook SDK", "category" => "Social"),
		"/platform\\.twitter\\.com/" => array("name" => "Twitter Widgets", "category" => "Social"),
		"/connect\\.facebook\\.net/" => array("name" => "Facebook SDK", "category" => "Social"),
		"/pinterest/" => array("name" => "Pinterest Tag", "category" => "Analytics"),
		"/snaptr/" => array("name" => "Snapchat Pixel", "category" => "Analytics"),
		"/tiktok/" => array("name" => "TikTok Pixel", "category" => "Analytics"),
		"/linkedin/" => array("name" => "LinkedIn Insight Tag", "category" => "Analytics"),
	);

	/** HTML attribute patterns that indicate specific frameworks. */
	private const ATTRIBUTE_SIGNATURES = array(
		array("query" => "//*[@ng-app or @ng-controller or @ng-model]", "name" => "Angular", "category" => "JavaScript Framework"),
		array("query" => "//*[@data-reactroot or @data-reactid]", "name" => "React", "category" => "JavaScript Framework"),
		array("query" => "//*[@x-data or @x-init or @x-show]", "name" => "Alpine.js", "category" => "JavaScript Framework"),
		array("query" => "//*[@v-if or @v-for or @v-bind or @v-model or @v-on]", "name" => "Vue.js", "category" => "JavaScript Framework"),
		array("query" => "//*[@data-turbo or @data-turbo-frame]", "name" => "Hotwire Turbo", "category" => "JavaScript Framework"),
		array("query" => "//*[@data-controller and @data-action]", "name" => "Stimulus", "category" => "JavaScript Framework"),
		array("query" => "//*[@wire:model or @wire:click]", "name" => "Livewire", "category" => "JavaScript Framework"),
		array("query" => "//*[contains(@class,'elementor-')]", "name" => "Elementor", "category" => "Page Builder"),
		array("query" => "//*[contains(@class,'wp-block-')]", "name" => "WordPress Gutenberg", "category" => "CMS"),
		array("query" => "//*[contains(@class,'shopify-section')]", "name" => "Shopify", "category" => "E-commerce Platform"),
		array("query" => "//*[contains(@class,'woocommerce')]", "name" => "WooCommerce", "category" => "E-commerce Plugin"),
		array("query" => "//*[contains(@class,'divi_')]", "name" => "Divi", "category" => "Page Builder"),
		array("query" => "//*[contains(@class,'fl-builder')]", "name" => "Beaver Builder", "category" => "Page Builder"),
		array("query" => "//*[contains(@class,'sqs-')]", "name" => "Squarespace", "category" => "Website Builder"),
		array("query" => "//*[contains(@class,'w-')]/@data-wf-site", "name" => "Webflow", "category" => "Website Builder"),
	);

	/** Preconnect/DNS-prefetch link patterns for service detection. */
	private const PRECONNECT_SIGNATURES = array(
		"fonts.googleapis.com" => array("name" => "Google Fonts", "category" => "Font Service"),
		"fonts.gstatic.com" => array("name" => "Google Fonts", "category" => "Font Service"),
		"use.typekit.net" => array("name" => "Adobe Fonts", "category" => "Font Service"),
		"cdn.shopify.com" => array("name" => "Shopify CDN", "category" => "CDN"),
		"images.unsplash.com" => array("name" => "Unsplash", "category" => "Media"),
		"www.youtube.com" => array("name" => "YouTube Embed", "category" => "Media"),
		"player.vimeo.com" => array("name" => "Vimeo Embed", "category" => "Media"),
	);

	/** Cookie name patterns from Set-Cookie headers. */
	private const COOKIE_SIGNATURES = array(
		"_shopify" => array("name" => "Shopify", "category" => "E-commerce Platform"),
		"laravel_session" => array("name" => "Laravel", "category" => "Backend Framework"),
		"PHPSESSID" => array("name" => "PHP", "category" => "Programming Language"),
		"JSESSIONID" => array("name" => "Java", "category" => "Programming Language"),
		"ASP.NET" => array("name" => "ASP.NET", "category" => "Backend Framework"),
		"csrftoken" => array("name" => "Django", "category" => "Backend Framework"),
		"_rails" => array("name" => "Ruby on Rails", "category" => "Backend Framework"),
		"wp-settings" => array("name" => "WordPress", "category" => "CMS"),
		"wordpress" => array("name" => "WordPress", "category" => "CMS"),
	);

	/** CDN detection via response headers. */
	private const CDN_HEADERS = array(
		"cf-ray" => "Cloudflare",
		"x-cdn" => null,
		"x-amz-cf-id" => "Amazon CloudFront",
		"x-fastly-request-id" => "Fastly",
		"x-vercel-id" => "Vercel",
		"x-netlify-request-id" => "Netlify",
	);

	/** Known CMS meta generator patterns. */
	private const GENERATOR_PATTERNS = array(
		"/WordPress/i" => array("name" => "WordPress", "category" => "CMS"),
		"/Drupal/i" => array("name" => "Drupal", "category" => "CMS"),
		"/Joomla/i" => array("name" => "Joomla", "category" => "CMS"),
		"/Shopify/i" => array("name" => "Shopify", "category" => "E-commerce Platform"),
		"/Wix\\.com/i" => array("name" => "Wix", "category" => "Website Builder"),
		"/Squarespace/i" => array("name" => "Squarespace", "category" => "Website Builder"),
		"/Ghost/i" => array("name" => "Ghost", "category" => "CMS"),
		"/Hugo/i" => array("name" => "Hugo", "category" => "Static Site Generator"),
		"/Jekyll/i" => array("name" => "Jekyll", "category" => "Static Site Generator"),
		"/Next\\.js/i" => array("name" => "Next.js", "category" => "JavaScript Framework"),
		"/Gatsby/i" => array("name" => "Gatsby", "category" => "Static Site Generator"),
		"/Webflow/i" => array("name" => "Webflow", "category" => "Website Builder"),
		"/HubSpot/i" => array("name" => "HubSpot", "category" => "CMS"),
		"/Magento/i" => array("name" => "Magento", "category" => "E-commerce Platform"),
		"/PrestaShop/i" => array("name" => "PrestaShop", "category" => "E-commerce Platform"),
		"/WooCommerce/i" => array("name" => "WooCommerce", "category" => "E-commerce Plugin"),
		"/TYPO3/i" => array("name" => "TYPO3", "category" => "CMS"),
		"/Blogger/i" => array("name" => "Blogger", "category" => "CMS"),
	);

	/** Display order for technology categories. */
	private const CATEGORY_ORDER = array(
		"CMS",
		"E-commerce Platform",
		"E-commerce Plugin",
		"Website Builder",
		"Page Builder",
		"Backend Framework",
		"JavaScript Framework",
		"CSS Framework",
		"JavaScript Library",
		"Static Site Generator",
		"Programming Language",
		"Icon Library",
		"Analytics",
		"Marketing",
		"A/B Testing",
		"Payment",
		"Live Chat",
		"Cookie Consent",
		"Error Tracking",
		"Security",
		"Social",
		"Font Service",
		"Media",
		"Mapping",
		"CDN",
		"Server",
		"Other",
	);

	public function moduleKey(): string
	{
		return "techStackDetection";
	}

	public function label(): string
	{
		return "Technology Stack";
	}

	public function category(): string
	{
		return "Technology Stack";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.techStackDetection", 0);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$detectedTech = array();
		$findings = array();

		/** Source 1: WhatCMS API data from Phase 1 */
		$this->mergeApiTechStack($scanContext->techStack, $detectedTech);

		/** Source 2: HTML signal detection */
		$this->detectFromMetaGenerator($scanContext->xpath, $detectedTech);
		$this->detectFromScripts($scanContext->xpath, $detectedTech);
		$this->detectFromInlineScripts($scanContext->xpath, $detectedTech);
		$this->detectFromStylesheets($scanContext->xpath, $detectedTech);
		$this->detectFromHtmlAttributes($scanContext->xpath, $detectedTech);
		$this->detectFromPreconnectLinks($scanContext->xpath, $detectedTech);
		$this->detectFromHeaders($scanContext->responseHeaders, $detectedTech);
		$this->detectFromCookies($scanContext->responseHeaders, $detectedTech);

		if (empty($detectedTech)) {
			$findings[] = array("type" => "info", "message" => "No technologies could be detected on this site.");

			return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: array());
		}

		/** Categorize and sort for display */
		$categorized = $this->categorizeAndSort($detectedTech);

		$techCount = count($detectedTech);
		$categoryCount = count($categorized);
		$findings[] = array("type" => "ok", "message" => "{$techCount} technologies detected across {$categoryCount} categories.");

		/** List top technologies in findings for the summary preview */
		$topTechNames = array_slice(array_column($detectedTech, "name"), 0, 5);
		$findings[] = array("type" => "info", "message" => "Detected: " . implode(", ", $topTechNames) . (count($detectedTech) > 5 ? ", ..." : ""));

		/** Store full categorized stack as structured data for custom display */
		$findings[] = array("type" => "data", "key" => "detectedTechStack", "value" => $categorized);

		return new AnalysisResult(status: ModuleStatus::Info, findings: $findings, recommendations: array());
	}

	/**
	 * Merge WhatCMS API tech stack into the detected technologies array.
	 * API data includes name, version, and categories from WhatCMS.
	 */
	private function mergeApiTechStack(array $apiTechStack, array &$detectedTech): void
	{
		foreach ($apiTechStack as $tech) {
			$name = $tech["name"] ?? "";
			if ($name === "") {
				continue;
			}

			$category = $this->resolveApiCategory($tech["categories"] ?? array());

			$this->addTechnology($detectedTech, $name, $category, $tech["version"] ?? null, "api");
		}
	}

	/**
	 * Map WhatCMS category arrays to our internal category names.
	 */
	private function resolveApiCategory(array $apiCategories): string
	{
		$categoryMap = array(
			"CMS" => "CMS",
			"cms" => "CMS",
			"Ecommerce" => "E-commerce Platform",
			"ecommerce" => "E-commerce Platform",
			"JavaScript frameworks" => "JavaScript Framework",
			"javascript-frameworks" => "JavaScript Framework",
			"CSS frameworks" => "CSS Framework",
			"css-frameworks" => "CSS Framework",
			"Web servers" => "Server",
			"web-servers" => "Server",
			"CDN" => "CDN",
			"cdn" => "CDN",
			"Analytics" => "Analytics",
			"analytics" => "Analytics",
			"JavaScript libraries" => "JavaScript Library",
			"javascript-libraries" => "JavaScript Library",
			"Static site generator" => "Static Site Generator",
			"Font scripts" => "Icon Library",
		);

		foreach ($apiCategories as $apiCategory) {
			$catName = is_array($apiCategory) ? ($apiCategory["name"] ?? (string) $apiCategory) : (string) $apiCategory;
			if (isset($categoryMap[$catName])) {
				return $categoryMap[$catName];
			}
		}

		return "Other";
	}

	/**
	 * Detect CMS and platforms from <meta name="generator"> tags.
	 */
	private function detectFromMetaGenerator(\DOMXPath $xpath, array &$detectedTech): void
	{
		$generatorNodes = $xpath->query("//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='generator']");
		if ($generatorNodes === false || $generatorNodes->length === 0) {
			return;
		}

		for ($index = 0; $index < $generatorNodes->length; $index++) {
			$node = $generatorNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$content = trim($node->getAttribute("content"));
			if ($content === "") {
				continue;
			}

			foreach (self::GENERATOR_PATTERNS as $pattern => $techInfo) {
				if (preg_match($pattern, $content, $matches)) {
					$version = null;
					if (preg_match("/[\d]+\.[\d]+(?:\.[\d]+)?/", $content, $versionMatch)) {
						$version = $versionMatch[0];
					}
					$this->addTechnology($detectedTech, $techInfo["name"], $techInfo["category"], $version, "generator");
					break;
				}
			}
		}
	}

	/**
	 * Detect JavaScript frameworks and libraries from script src URLs.
	 */
	private function detectFromScripts(\DOMXPath $xpath, array &$detectedTech): void
	{
		$scriptNodes = $xpath->query("//script[@src]");
		if ($scriptNodes === false || $scriptNodes->length === 0) {
			return;
		}

		$detectedKeys = array();

		for ($index = 0; $index < $scriptNodes->length; $index++) {
			$node = $scriptNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$src = strtolower($node->getAttribute("src"));

			foreach (self::SCRIPT_SIGNATURES as $signatureKey => $techInfo) {
				if (isset($detectedKeys[$signatureKey])) {
					continue;
				}
				if (str_contains($src, $signatureKey)) {
					$this->addTechnology($detectedTech, $techInfo["name"], $techInfo["category"], null, "script");
					$detectedKeys[$signatureKey] = true;
				}
			}
		}
	}

	/**
	 * Detect CSS frameworks from stylesheet URLs.
	 */
	private function detectFromStylesheets(\DOMXPath $xpath, array &$detectedTech): void
	{
		$linkNodes = $xpath->query("//link[@rel='stylesheet']");
		if ($linkNodes === false || $linkNodes->length === 0) {
			return;
		}

		$cssSignatures = array(
			"bootstrap" => array("name" => "Bootstrap", "category" => "CSS Framework"),
			"tailwindcss" => array("name" => "Tailwind CSS", "category" => "CSS Framework"),
			"bulma" => array("name" => "Bulma", "category" => "CSS Framework"),
			"foundation" => array("name" => "Foundation", "category" => "CSS Framework"),
			"materialize" => array("name" => "Materialize", "category" => "CSS Framework"),
			"font-awesome" => array("name" => "Font Awesome", "category" => "Icon Library"),
			"fontawesome" => array("name" => "Font Awesome", "category" => "Icon Library"),
		);

		$detectedKeys = array();

		for ($index = 0; $index < $linkNodes->length; $index++) {
			$node = $linkNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$href = strtolower($node->getAttribute("href"));

			foreach ($cssSignatures as $signatureKey => $techInfo) {
				if (isset($detectedKeys[$signatureKey])) {
					continue;
				}
				if (str_contains($href, $signatureKey)) {
					$this->addTechnology($detectedTech, $techInfo["name"], $techInfo["category"], null, "stylesheet");
					$detectedKeys[$signatureKey] = true;
				}
			}
		}
	}

	/**
	 * Detect CDN and server technologies from response headers.
	 */
	private function detectFromHeaders(array $headers, array &$detectedTech): void
	{
		$headersLower = array();
		foreach ($headers as $key => $value) {
			$headersLower[strtolower($key)] = is_array($value) ? ($value[0] ?? "") : (string) $value;
		}

		/** CDN detection */
		foreach (self::CDN_HEADERS as $headerKey => $cdnName) {
			if (isset($headersLower[$headerKey])) {
				$resolvedName = $cdnName ?? $headersLower[$headerKey];
				if ($resolvedName !== "") {
					$this->addTechnology($detectedTech, $resolvedName, "CDN", null, "header");
				}
			}
		}

		/** Server detection */
		if (isset($headersLower["server"])) {
			$serverValue = $headersLower["server"];
			if ($serverValue !== "" && strtolower($serverValue) !== "cloudflare") {
				$this->addTechnology($detectedTech, $serverValue, "Server", null, "header");
			}
		}

		/** X-Powered-By detection */
		if (isset($headersLower["x-powered-by"])) {
			$poweredBy = $headersLower["x-powered-by"];
			if ($poweredBy !== "") {
				$this->addTechnology($detectedTech, $poweredBy, "Server", null, "header");
			}
		}
	}

	/**
	 * Detect frameworks and platforms from inline <script> content (no src attribute).
	 * Scans the text content of inline scripts against known patterns.
	 */
	private function detectFromInlineScripts(\DOMXPath $xpath, array &$detectedTech): void
	{
		$inlineNodes = $xpath->query("//script[not(@src)]");
		if ($inlineNodes === false || $inlineNodes->length === 0) {
			return;
		}

		/** Concatenate inline script content (cap at 50KB to avoid regex on huge pages) */
		$combinedContent = "";
		$maxContentLength = 50000;

		for ($index = 0; $index < $inlineNodes->length; $index++) {
			$textContent = $inlineNodes->item($index)->textContent ?? "";
			$combinedContent .= $textContent . "\n";
			if (strlen($combinedContent) > $maxContentLength) {
				$combinedContent = substr($combinedContent, 0, $maxContentLength);
				break;
			}
		}

		if ($combinedContent === "") {
			return;
		}

		foreach (self::INLINE_SCRIPT_PATTERNS as $pattern => $techInfo) {
			if (preg_match($pattern, $combinedContent)) {
				$this->addTechnology($detectedTech, $techInfo["name"], $techInfo["category"], null, "inline-script");
			}
		}
	}

	/**
	 * Detect frameworks from HTML element attributes (Angular ng-*, Vue v-*, Alpine x-data, etc.).
	 * Uses XPath queries to check for framework-specific attribute patterns.
	 */
	private function detectFromHtmlAttributes(\DOMXPath $xpath, array &$detectedTech): void
	{
		foreach (self::ATTRIBUTE_SIGNATURES as $signature) {
			try {
				$nodes = @$xpath->query($signature["query"]);
				if ($nodes !== false && $nodes->length > 0) {
					$this->addTechnology($detectedTech, $signature["name"], $signature["category"], null, "attribute");
				}
			} catch (\Exception $exception) {
				/** Silently skip malformed XPath — some DOMs may not support all queries */
				continue;
			}
		}
	}

	/**
	 * Detect services from <link rel="preconnect"> and <link rel="dns-prefetch"> hints.
	 * Websites preconnect to third-party services they use for fonts, CDNs, embeds, etc.
	 */
	private function detectFromPreconnectLinks(\DOMXPath $xpath, array &$detectedTech): void
	{
		$linkNodes = $xpath->query("//link[@rel='preconnect' or @rel='dns-prefetch']");
		if ($linkNodes === false || $linkNodes->length === 0) {
			return;
		}

		for ($index = 0; $index < $linkNodes->length; $index++) {
			$node = $linkNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$href = strtolower($node->getAttribute("href"));

			foreach (self::PRECONNECT_SIGNATURES as $domain => $techInfo) {
				if (str_contains($href, $domain)) {
					$this->addTechnology($detectedTech, $techInfo["name"], $techInfo["category"], null, "preconnect");
					break;
				}
			}
		}
	}

	/**
	 * Detect backend platforms from Set-Cookie headers.
	 * Cookie names reveal the server-side framework (Laravel, PHP, Django, Rails, etc.).
	 */
	private function detectFromCookies(array $headers, array &$detectedTech): void
	{
		$cookieValues = array();
		foreach ($headers as $key => $value) {
			if (strtolower($key) === "set-cookie") {
				$cookieValues = is_array($value) ? $value : array($value);
				break;
			}
		}

		if (empty($cookieValues)) {
			return;
		}

		$combinedCookies = strtolower(implode(" ", $cookieValues));

		foreach (self::COOKIE_SIGNATURES as $cookieName => $techInfo) {
			if (str_contains($combinedCookies, strtolower($cookieName))) {
				$this->addTechnology($detectedTech, $techInfo["name"], $techInfo["category"], null, "cookie");
			}
		}
	}

	/**
	 * Add a technology to the detected list, deduplicating by name.
	 * API source takes priority over HTML signal detection for version info.
	 */
	private function addTechnology(array &$detectedTech, string $name, string $category, ?string $version, string $source): void
	{
		$normalizedName = strtolower(trim($name));

		foreach ($detectedTech as &$existing) {
			if (strtolower($existing["name"]) === $normalizedName) {
				/** Update version if API provides it and existing doesn't have it */
				if ($version !== null && $existing["version"] === null) {
					$existing["version"] = $version;
				}
				/** API source overrides HTML detection source */
				if ($source === "api" && $existing["source"] !== "api") {
					$existing["source"] = "api";
					if ($version !== null) {
						$existing["version"] = $version;
					}
				}
				return;
			}
		}
		unset($existing);

		$detectedTech[] = array(
			"name" => trim($name),
			"category" => $category,
			"version" => $version,
			"source" => $source,
		);
	}

	/**
	 * Group technologies by category and sort by display order.
	 *
	 * @return array<string, array<int, array{name: string, version: ?string, source: string}>>
	 */
	private function categorizeAndSort(array $detectedTech): array
	{
		$grouped = array();

		foreach ($detectedTech as $tech) {
			$category = $tech["category"];
			if (!isset($grouped[$category])) {
				$grouped[$category] = array();
			}
			$grouped[$category][] = array(
				"name" => $tech["name"],
				"version" => $tech["version"],
				"source" => $tech["source"],
			);
		}

		/** Sort categories by predefined display order */
		$sorted = array();
		foreach (self::CATEGORY_ORDER as $category) {
			if (isset($grouped[$category])) {
				$sorted[$category] = $grouped[$category];
				unset($grouped[$category]);
			}
		}

		/** Append any remaining categories not in the predefined order */
		foreach ($grouped as $category => $techs) {
			$sorted[$category] = $techs;
		}

		return $sorted;
	}
}
