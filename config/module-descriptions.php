<?php

/**
 * Educational descriptions for all analyzer modules.
 *
 * Each module has a "description" (always shown — explains why this check matters)
 * and a "passing" message (shown when the module passes — positive reinforcement).
 * These replace the old separated "Recommendations" section with a unified
 * Insight block that combines education + actionable fixes.
 */

return array(

	/*
	|--------------------------------------------------------------------------
	| On-Page SEO
	|--------------------------------------------------------------------------
	*/

	"titleTag" => array(
		"description" => "Title tags are one of the strongest on-page ranking signals and the first thing users see in search results. A well-crafted title between 30-65 characters that includes your target keyword can significantly improve click-through rates and rankings.",
		"passing" => "Your title tag is well-optimized and within the recommended length range.",
	),

	"metaDescription" => array(
		"description" => "Meta descriptions appear as the snippet below your title in search results. While not a direct ranking factor, a compelling description between 70-160 characters can dramatically improve click-through rates and drive more organic traffic.",
		"passing" => "Your meta description is well-written and within the optimal length range.",
	),

	"h1Tag" => array(
		"description" => "The H1 heading is your page's main headline and tells both users and search engines what the page is about. Each page should have exactly one H1 that clearly describes the content and ideally includes your primary keyword.",
		"passing" => "Your page has a single, well-structured H1 heading.",
	),

	"h2h6Tags" => array(
		"description" => "Subheadings (H2-H6) create a content hierarchy that helps readers scan your page and helps search engines understand your content structure. Well-organized headings improve both user experience and topical relevance signals.",
		"passing" => "Your heading structure is well-organized with a clear hierarchy.",
	),

	"schemaOrg" => array(
		"description" => "Schema markup is structured data that helps search engines understand your content and can enable rich results like star ratings, FAQ dropdowns, and event details directly in search results. Rich results can increase click-through rates by up to 30%.",
		"passing" => "Your page includes valid schema markup that may enable rich results.",
	),

	"semanticHtml" => array(
		"description" => "Semantic HTML elements like header, nav, main, and footer help search engines and assistive technologies understand your page layout. Proper semantic structure improves accessibility and can enhance how search engines interpret your content.",
		"passing" => "Your page uses proper semantic HTML elements for clear content structure.",
	),

	"breadcrumbs" => array(
		"description" => "Breadcrumb navigation shows users where they are within your site hierarchy and helps search engines understand your site structure. Google often displays breadcrumbs in search results, making your listings more informative and clickable.",
		"passing" => "Your page includes breadcrumb navigation for clear site hierarchy.",
	),

	"socialTags" => array(
		"description" => "Open Graph and Twitter Card tags control how your pages appear when shared on social media. Without these tags, social platforms may display incorrect images, titles, or descriptions, reducing engagement with shared links.",
		"passing" => "Your page has social meta tags configured for optimal sharing appearance.",
	),

	"linkAnalysis" => array(
		"description" => "Internal links distribute page authority throughout your site and help search engines discover content. External links to authoritative sources can boost your content's credibility. A healthy link profile balances both internal and outbound links.",
		"passing" => "Your page has a healthy balance of internal and external links.",
	),

	/*
	|--------------------------------------------------------------------------
	| Technical SEO
	|--------------------------------------------------------------------------
	*/

	"canonicalTag" => array(
		"description" => "The canonical tag tells search engines which version of a page is the \"original\" when duplicate or similar content exists. Without it, search engines may split ranking signals across multiple URLs, diluting your page's authority.",
		"passing" => "Your page has a properly configured canonical tag pointing to the correct URL.",
	),

	"doctypeCharset" => array(
		"description" => "The HTML doctype declaration and character encoding ensure browsers and search engines render your content correctly. Missing or incorrect settings can cause display issues and may prevent proper indexing of special characters.",
		"passing" => "Your page has correct doctype and character encoding declarations.",
	),

	"htmlLang" => array(
		"description" => "The HTML lang attribute tells browsers and search engines what language your page is written in. This helps search engines serve the correct version to users in different regions and enables assistive technologies like screen readers to use the proper pronunciation rules.",
		"passing" => "Your page has a valid HTML lang attribute declaring the content language.",
	),

	"robotsMeta" => array(
		"description" => "The robots meta tag controls how search engines crawl and index your pages. Incorrect settings can accidentally block important pages from appearing in search results or waste crawl budget on pages that shouldn't be indexed.",
		"passing" => "Your robots meta settings allow proper crawling and indexing of this page.",
	),

	"noindexCheck" => array(
		"description" => "A noindex directive tells search engines not to include a page in search results. While useful for private or duplicate pages, accidentally applying noindex to important content will completely remove it from search visibility.",
		"passing" => "No noindex directives detected — this page is eligible to appear in search results.",
	),

	"httpHeaders" => array(
		"description" => "HTTP response headers communicate important information to browsers and search engines, including security policies, caching instructions, and content type. Properly configured headers improve both security and performance.",
		"passing" => "Your HTTP headers are properly configured with appropriate security and caching settings.",
	),

	"httpsRedirect" => array(
		"description" => "Google confirmed HTTPS as a ranking signal in 2014, and browsers now show 'Not Secure' warnings on HTTP pages. If the HTTP version of your site serves content instead of redirecting to HTTPS, search engines may index both versions separately — splitting your ranking signals and creating duplicate content.",
		"passing" => "Your HTTP traffic properly redirects to HTTPS with a 301 permanent redirect.",
	),

	"duplicateUrl" => array(
		"description" => "Search engines treat www and non-www versions as separate sites, and URLs with and without trailing slashes as different pages. If both versions serve content without redirecting, your ranking signals get split across duplicates. Proper 301 redirects and canonical tags ensure all authority consolidates on your preferred URL format.",
		"passing" => "Your URL variations (www/non-www and trailing slashes) are properly handled with redirects.",
	),

	"redirectChain" => array(
		"description" => "Redirect chains occur when a URL redirects through multiple intermediate URLs before reaching the final destination. Each redirect adds latency and can cause search engines to lose ranking signals. Ideally, redirects should go directly to the final URL.",
		"passing" => "No redirect chains detected — your URL resolves directly.",
	),

	"robotsTxt" => array(
		"description" => "The robots.txt file tells search engine crawlers which parts of your site they can and cannot access. A missing or misconfigured robots.txt can either block important content from being indexed or fail to protect private areas.",
		"passing" => "Your robots.txt is accessible and properly configured.",
	),

	"sitemapAnalysis" => array(
		"description" => "An XML sitemap is a roadmap of your site's pages that helps search engines discover and crawl your content efficiently. It's especially important for large sites, new sites, or sites with pages that aren't well-linked internally.",
		"passing" => "Your XML sitemap is accessible and helps search engines discover your content.",
	),

	"hreflang" => array(
		"description" => "Hreflang tags tell search engines which language and regional version of a page to show users in different locations. Without them, international sites risk showing the wrong language version in search results, hurting user experience and rankings.",
		"passing" => "Your hreflang tags are properly configured for international targeting.",
	),

	"urlStructure" => array(
		"description" => "Clean, descriptive URLs help users and search engines understand page content before clicking. Short URLs with relevant keywords tend to perform better in search results than long, parameter-heavy URLs with random characters.",
		"passing" => "Your URL structure is clean, descriptive, and SEO-friendly.",
	),

	"blacklistCheck" => array(
		"description" => "Search engines and browsers maintain blocklists of sites known to distribute malware or phishing content. Being flagged on these lists triggers browser warnings that block visitors and can cause dramatic traffic drops overnight.",
		"passing" => "Your site is not flagged on any known malware or phishing blocklists.",
	),

	/*
	|--------------------------------------------------------------------------
	| Security
	|--------------------------------------------------------------------------
	*/

	"sslCertificate" => array(
		"description" => "An SSL/TLS certificate encrypts traffic between your server and visitors, and is required for the HTTPS protocol. Google uses HTTPS as a ranking signal, and browsers display prominent 'Not Secure' warnings on sites without a valid certificate — driving visitors away and eroding trust.",
		"passing" => "Your site has a valid SSL/TLS certificate with no expiration or configuration issues.",
	),

	"securityHeaders" => array(
		"description" => "Security response headers like Content-Security-Policy, HSTS, and X-Frame-Options protect your site and visitors from cross-site scripting, clickjacking, and protocol downgrade attacks. While not direct ranking factors, missing security headers can lead to site compromise — which triggers blacklisting and devastating traffic loss.",
		"passing" => "Your server sends all recommended security response headers.",
	),

	"mixedContent" => array(
		"description" => "Mixed content occurs when an HTTPS page loads resources (scripts, stylesheets, images) over insecure HTTP. This triggers browser warnings, breaks the padlock indicator, and undermines the HTTPS ranking signal that Google has confirmed since 2014. Modern browsers may block mixed content entirely, causing broken functionality.",
		"passing" => "No mixed content detected — all resources are loaded securely over HTTPS.",
	),

	"exposedSensitiveFiles" => array(
		"description" => "Sensitive files like .env, .git/HEAD, database backups, and debug logs should never be publicly accessible. These files can expose database credentials, API keys, and server configuration — giving attackers everything they need to compromise your site. A compromised site gets blacklisted, destroying SEO overnight.",
		"passing" => "No publicly accessible sensitive files were detected on your server.",
	),

	/*
	|--------------------------------------------------------------------------
	| Usability & Performance
	|--------------------------------------------------------------------------
	*/

	"viewportTag" => array(
		"description" => "The viewport meta tag controls how your page scales on mobile devices. Without it, mobile users see a tiny desktop version of your site. Google uses mobile-first indexing, so a missing viewport tag directly impacts your mobile rankings.",
		"passing" => "Your viewport tag is properly configured for mobile responsiveness.",
	),

	"favicon" => array(
		"description" => "A favicon is the small icon displayed in browser tabs, bookmarks, and mobile home screens. While not a ranking factor, it makes your site look professional and helps users identify your brand when they have multiple tabs open.",
		"passing" => "Your site has a favicon configured for brand recognition in browser tabs.",
	),

	"imageAnalysis" => array(
		"description" => "Images need descriptive alt text for accessibility and image search rankings, explicit dimensions to prevent layout shifts, and modern formats like WebP for faster loading. Optimized images improve both user experience and Core Web Vitals scores.",
		"passing" => "Your images are well-optimized with proper alt text, dimensions, and formats.",
	),

	"performanceHints" => array(
		"description" => "Page speed is a confirmed Google ranking factor that directly affects user experience. Slow pages have higher bounce rates and lower conversion rates. This checks server response time, render-blocking resources, and resource counts.",
		"passing" => "Your page loads efficiently with good server response time and optimized resources.",
	),

	"coreWebVitalsMobile" => array(
		"description" => "Core Web Vitals are Google's official user experience metrics that directly influence rankings. This tests your site on a simulated mobile device — the primary benchmark since Google uses mobile-first indexing. LCP measures loading speed, CLS measures visual stability, and INP measures interactivity.",
		"passing" => "Your mobile Core Web Vitals all meet Google's recommended thresholds.",
	),

	"coreWebVitalsDesktop" => array(
		"description" => "Core Web Vitals measured on desktop typically show better performance than mobile due to faster processors and network connections. While Google prioritizes mobile metrics for rankings, desktop performance still matters for user experience — especially for B2B sites where desktop traffic dominates.",
		"passing" => "Your desktop Core Web Vitals all meet Google's recommended thresholds.",
	),

	"compressionCheck" => array(
		"description" => "Text compression (gzip or Brotli) can reduce page size by 60-80%, making your site load significantly faster. Combined with proper cache headers, compression reduces bandwidth costs and ensures returning visitors get near-instant page loads.",
		"passing" => "Your server uses compression and has proper caching headers configured.",
	),

	"accessibilityCheck" => array(
		"description" => "Web accessibility ensures your site is usable by everyone, including people using screen readers and keyboard navigation. Accessible sites tend to rank better because many accessibility best practices overlap with SEO fundamentals.",
		"passing" => "Your page follows core accessibility best practices.",
	),

	/*
	|--------------------------------------------------------------------------
	| Content Analysis
	|--------------------------------------------------------------------------
	*/

	"contentKeywords" => array(
		"description" => "Search engines determine what a page is about by analyzing where and how often target keywords appear. Placing your primary keyword in the title, headings, opening paragraph, URL, and meta description sends strong relevance signals.",
		"passing" => "Your target keyword is present in the key locations across your page.",
	),

	"keywordConsistency" => array(
		"description" => "The consistency matrix shows at a glance where each of your target keywords appears across seven critical page locations. Gaps in the matrix indicate missed opportunities to reinforce your content's topical relevance to search engines.",
		"passing" => "Your keywords have strong coverage across key page locations.",
	),

	"contentReadability" => array(
		"description" => "Content that's easy to read keeps users on your page longer, reducing bounce rates and sending positive engagement signals to search engines. Most successful web content is written at a 7th-9th grade reading level with sufficient depth (300+ words).",
		"passing" => "Your content has good readability and sufficient depth for search engines.",
	),

	"contentDuplicate" => array(
		"description" => "Duplicate content across your site or copied from other sources can confuse search engines about which page to rank. Google may filter duplicate pages from results entirely, wasting your crawl budget and splitting ranking authority.",
		"passing" => "No significant duplicate content signals detected on this page.",
	),

	/*
	|--------------------------------------------------------------------------
	| E-E-A-T Signals
	|--------------------------------------------------------------------------
	*/

	"eatAuthor" => array(
		"description" => "Google's quality guidelines emphasize Experience, Expertise, Authoritativeness, and Trustworthiness (E-E-A-T). Clear author information with bios, credentials, and schema markup helps establish who's behind your content and builds trust with both users and search engines.",
		"passing" => "Your page includes clear author attribution with supporting credentials.",
	),

	"eatBusinessSchema" => array(
		"description" => "Business schema markup provides search engines with verified details about your organization including name, address, contact information, and social profiles. This structured data strengthens your brand's authority signals and can enable enhanced search features.",
		"passing" => "Your site includes business schema markup with organizational details.",
	),

	"eatPrivacyTerms" => array(
		"description" => "Privacy policies and terms of service pages are trust signals that demonstrate your site operates transparently and professionally. Google's quality raters look for these pages when evaluating site trustworthiness, especially for sites that handle user data.",
		"passing" => "Your site has accessible privacy policy and terms of service pages.",
	),

	"eatTrustPages" => array(
		"description" => "About Us and Contact pages help establish who is behind a website and how to reach them. Google's quality guidelines specifically look for these trust pages, and their absence can negatively impact how your site's quality is evaluated.",
		"passing" => "Your site has accessible About and Contact pages that establish credibility.",
	),

	/*
	|--------------------------------------------------------------------------
	| WordPress
	|--------------------------------------------------------------------------
	*/

	"wpDetection" => array(
		"description" => "Keeping WordPress core updated is critical for security and performance. Outdated versions may contain known vulnerabilities that hackers actively exploit. This check identifies your WordPress version and flags if updates are available.",
		"passing" => "Your WordPress installation is running a current, secure version.",
	),

	"wpTheme" => array(
		"description" => "WordPress themes control your site's appearance and can significantly impact performance and security. Outdated or poorly coded themes may introduce vulnerabilities, slow down your site, or cause compatibility issues with newer WordPress versions.",
		"passing" => "Your WordPress theme is up to date and properly maintained.",
	),

	"wpPlugins" => array(
		"description" => "WordPress plugins extend functionality but are also the most common source of security vulnerabilities. Outdated plugins can be exploited by attackers, and too many plugins can slow your site. Regular audits help maintain a healthy plugin ecosystem.",
		"passing" => "Your WordPress plugins are up to date with no known security issues.",
	),

	/*
	|--------------------------------------------------------------------------
	| Extras & Utility
	|--------------------------------------------------------------------------
	*/

	"analyticsDetection" => array(
		"description" => "Web analytics tools like Google Analytics and Tag Manager track how visitors find and use your site. Without analytics, you're flying blind — unable to measure which SEO efforts are working, which pages need improvement, or where visitors drop off.",
		"passing" => "Your site has analytics tracking installed to measure visitor behavior.",
	),

	"serpPreview" => array(
		"description" => "This preview shows how your page may appear in Google search results. The title and description are the first impression potential visitors get — compelling copy here directly influences whether users click through to your site or choose a competitor.",
		"passing" => "Your search result preview looks complete and inviting to potential visitors.",
	),

	"googleMapEmbed" => array(
		"description" => "Embedded Google Maps on your site help visitors find your physical location and send local relevance signals to search engines. For businesses serving a local area, a map embed combined with consistent name, address, and phone data strengthens local SEO.",
		"passing" => "Your page includes an embedded Google Map for local visibility.",
	),

	"techStackDetection" => array(
		"description" => "Understanding your site's technology stack helps identify performance bottlenecks, security considerations, and optimization opportunities. The detected technologies include your CMS, frameworks, analytics tools, CDN, and third-party services.",
		"passing" => "Your technology stack has been identified for reference.",
	),
);
