# The Blueprint — Hello SEO Analyzer Feature Catalog

> Complete teardown of the original app. Every module, every feature, every data flow.
> Use this as the reference spec for the rebuild.
>
> **Cataloged:** February 6, 2026
> **Branch at time of catalog:** `printRep`

---

## Architecture

- **Stack:** PHP backend (no framework) + Vanilla JS frontend (ES6 modules)
- **Pattern:** PHP `analyzeXxxxx()` → `["status", "findings", "recommendations"]` → JSON API → JS `renderXxxxxCard()` → DOM card
- **Theme:** Dark mode UI, accordion cards, tabbed results
- **Entry points:** `index.php` (HTML), `app.php` (API), `ai-modules/optimize-onpage.php` (AI), `pdf-report/generate-report.php` (PDF)

---

## Core Features

### 1. URL Analysis Engine
- **Files:** `app.php`, `utils/fetcher.php`
- cURL-based page fetcher with redirect following (max 5), SSL, 15s timeout
- Captures response headers + timing (TTFB, total load)
- DOMDocument/XPath HTML parsing with charset detection
- Returns structured JSON: `{ success, data: { analysis_meta, seoAudit } }`

### 2. Tabbed Results UI
- **File:** `scripts-n-css/app.js`
- Tab 1: **SEO Audit** (23 modules, always visible)
- Tab 2: **WordPress Scanner** (3 modules, conditional — only appears for WP sites)
- Staggered fade-in card animation on load (100ms per card)
- Lazy animation for WP tab cards on first switch

### 3. Accordion Card System
- Click-to-expand cards with smooth max-height transitions (0.4-0.5s)
- Color-coded left border by status: green (ok), yellow (warning), red (bad), blue (info)
- Toggle indicator (+/-), auto-scroll into view on expand

### 4. External Tool Links
- Generated dynamically after scan using the analyzed URL
- Google PageSpeed Insights
- W3C HTML Validator
- SSL Labs Test
- Security Headers Check
- Whois Lookup

---

## SEO Analyzer Modules (23)

All modules return: `{ status, findings: [{ type, message }], recommendations: [string] }`

### On-Page SEO

| # | Key | Display Name | Backend File | Frontend File | What It Checks |
|---|-----|-------------|-------------|--------------|----------------|
| 1 | `titleTag` | Title Tag | `modules/title-tag-analyzer.php` | `modules/title-tag-analyzer.js` | Presence, length (30-65 chars), content quality. **AI-enabled.** |
| 2 | `metaDescription` | Meta Description | `modules/meta-description-analyzer.php` | `modules/meta-description-renderer.js` | Presence, length (70-160 chars), duplicates, empty content. **AI-enabled.** |
| 3 | `h1Tag` | H1 Heading | `modules/h1-analyzer.php` | `modules/h1-renderer.js` | Presence (required), count (exactly 1), non-empty. **AI-enabled.** |
| 4 | `h2h6Tags` | H2-H6 Headings | `modules/h2-h6-analyzer.php` | `modules/h2-h6-renderer.js` | Heading hierarchy, skipped levels, empty headings |
| 5 | `serpPreview` | SERP Preview | *(assembled in app.php)* | `modules/serp-preview-renderer.js` | Google search result preview (title 65 chars, desc 160 chars). **AI-enabled.** |

### Technical SEO

| # | Key | Display Name | Backend File | Frontend File | What It Checks |
|---|-----|-------------|-------------|--------------|----------------|
| 6 | `canonicalTag` | Canonical Tag | `modules/canonical-analyzer.php` | `modules/canonical-renderer.js` | Presence, count (only 1), absolute URL, self-referencing |
| 7 | `robotsMeta` | Robots Meta Tag | `modules/robots-meta-analyzer.php` | `modules/robots-meta-renderer.js` | Directives validity, conflicts (index vs noindex), count. 10 known directives. |
| 8 | `robotsTxt` | robots.txt | `modules/robots-txt-analyzer.php` | `modules/robots-txt-renderer.js` | File accessibility from domain root, content presence |
| 9 | `sitemapAnalysis` | XML Sitemap | `modules/sitemap-analyzer.php` | `modules/sitemap-renderer.js` | Sitemap: directives in robots.txt, each URL tested for HTTP 200 |
| 10 | `noindexCheck` | Noindex Check | `modules/noindex-checker.php` | `modules/noindex-renderer.js` | Noindex/none in robots meta + X-Robots-Tag HTTP header |
| 11 | `doctypeCharset` | Doctype & Charset | `modules/doctype-charset-analyzer.php` | `modules/doctype-charset-renderer.js` | HTML5 doctype, charset in HTTP header or meta, UTF-8 preference |
| 12 | `httpHeaders` | HTTP Headers | `modules/http-header-analyzer.php` | `modules/http-header-renderer.js` | Content-Encoding (gzip/brotli), Cache-Control, Expires, Pragma |
| 13 | `htmlLang` | HTML Lang Attribute | `modules/language-analyzer.php` | `modules/language-renderer.js` | Presence, BCP 47 format validation |
| 14 | `hreflang` | Hreflang Tags | `modules/hreflang-analyzer.php` | `modules/hreflang-renderer.js` | ISO language codes, absolute URLs, self-references |

### Usability & Performance

| # | Key | Display Name | Backend File | Frontend File | What It Checks |
|---|-----|-------------|-------------|--------------|----------------|
| 15 | `performanceHints` | Performance Hints | `modules/performance-analyzer.php` | `modules/performance-renderer.js` | TTFB (warn >= 0.6s), page load time, CSS/JS file counts |
| 16 | `viewportTag` | Viewport Tag | `modules/viewport-analyzer.php` | `modules/viewport-renderer.js` | width=device-width, initial-scale=1.0, user-scalable, maximum-scale |
| 17 | `imageAnalysis` | Image Analysis | `modules/image-analyzer.php` | `modules/image-renderer.js` | Alt text audit, image count, URL resolution, toggleable image grid. **AI-enabled (per-image alt text).** |
| 18 | `favicon` | Favicon | `modules/favicon-analyzer.php` | `modules/favicon-renderer.js` | Link rel="icon" variants (icon, shortcut, apple-touch, mask-icon), URL resolution, preview |

### Discovery & Social

| # | Key | Display Name | Backend File | Frontend File | What It Checks |
|---|-----|-------------|-------------|--------------|----------------|
| 19 | `socialTags` | Social / Open Graph Tags | `modules/social-tags-analyzer.php` | `modules/social-tags-renderer.js` | OG tags (og:title/type/image/url) + Twitter Card tags |
| 20 | `schemaOrg` | Schema.org Structured Data | `modules/schema-analyzer.php` | `modules/schema-renderer.js` | JSON-LD detection, @type extraction, JSON validation, links to Google tools |
| 21 | `linkAnalysis` | Link Analysis | `modules/link-analyzer.php` | `modules/link-renderer.js` | Internal/external counts, nofollow attributes, link text descriptiveness |
| 22 | `googleMapEmbed` | Google Map Embed | `modules/google-map-analyzer.php` | `modules/google-map-renderer.js` | Detects iframe with google.com/maps |
| 23 | `wikipediaLinkCheck` | Wikipedia Links | `modules/wikipedia-link-analyzer.php` | `modules/wikipedia-link-renderer.js` | Detects links to wikipedia.org (trust signal) |

---

## WordPress Scanner Modules (3)

Gated by `wpDetection.is_wordpress === true`. Only run if WordPress is detected.

| Key | Display Name | Backend File | Frontend File | What It Does |
|-----|-------------|-------------|--------------|-------------|
| `wpDetection` | WordPress Detection | `modules/wp-detect-analyzer.php` | `modules/wp-detect-renderer.js` | 4 detection signals (wp-content paths, wp-includes with ?ver= params, meta generator regex, RSS feed /feed/ endpoint). Core version extraction + comparison with WP.org. Core CVE lookup via WPVulnerability.net. |
| `wpPlugins` | WordPress Plugins | `modules/wp-plugins-analyzer.php` | `modules/wp-plugins-renderer.js` | Plugin slug detection from URL paths + Yoast HTML comments + meta generators (RevSlider, Google Site Kit, etc.). Max 15 WP.org API lookups. Version comparison. Per-plugin vulnerability check. Sorted: vulnerable -> outdated -> current -> unknown. Detail table with 5 columns. CVE detail rows auto-open for vulnerable plugins. |
| `wpTheme` | WordPress Theme | `modules/wp-theme-analyzer.php` | `modules/wp-theme-renderer.js` | Active theme detection from /wp-content/themes/ paths. style.css header parsing fallback for version. WP.org version comparison. Theme vulnerability check. Expandable CVE detail section. |

### External APIs Used

| API | Auth | Purpose |
|-----|------|---------|
| WordPress.org Plugin API (`api.wordpress.org/plugins/info/1.2/`) | None | Plugin name, latest version, metadata |
| WordPress.org Theme API (`api.wordpress.org/themes/info/1.2/`) | None | Theme name, latest version |
| WordPress.org Core Version Check | None | Latest WP core version |
| WPVulnerability.net API | None | CVE data with CVSS scores for plugins/themes/core |

---

## AI Optimization Feature

- **Trigger:** Purple "Optimize" button appears in sidebar after successful audit
- **UI:** Slide-in panel from right with semi-transparent overlay
- **Inputs:** Primary keyword, secondary keywords, custom body text (textarea)
- **Backend:** `ai-modules/optimize-onpage.php` → `config/ai-api-connection.php` → OpenAI GPT-4 Chat Completion API
- **Output:** Optimized title tag (max 72 chars, geographic targeting format)
- **Display:** Blinking purple AI badge on optimized cards, AI-optimized content box with copy-to-clipboard button
- **AI-enabled cards:** Title Tag, Meta Description, H1, SERP Preview, Image Analysis (per-image alt text)
- **Status bubble:** "Optimizing with AI..." shown during processing

---

## PDF Report Generator

- **Trigger:** Green "Print Report" button in sidebar after successful audit
- **Tech:** Dompdf v3.1.4 via Composer (`composer.json`), CSS 2.1 only (no flexbox/grid)
- **Flow:** JS POSTs `auditResultCache` as JSON → `pdf-report/generate-report.php` validates + renders HTML → Dompdf converts → PDF streams as download
- **Filename:** `SEO-Report-{hostname}-{YYYY-MM-DD}.pdf`
- **Security:** `isRemoteEnabled=false`, `setChroot(projectRoot)`, 2MB payload limit, `htmlspecialchars` on all output

### Report Layout

1. **Header** — Logo (`Logo-white-on-black.jpg`), title, scanned URL, date
2. **Score Summary** — Overall 0-100 score (ok=100pts, warning=50pts, bad=0pts, info=excluded) + pass/warn/fail/info counters in single row
3. **SEO Findings** — Grouped into 4 categories with category headings + mini-summaries:
   - On-Page SEO (5 modules)
   - Technical SEO (9 modules)
   - Usability & Performance (4 modules)
   - Discovery & Social (5 modules)
4. **WordPress Section** (page break, conditional) — Detection + Plugin table (sorted, with inline CVE rows) + Theme info + Vulnerability detail blocks with NVD links
5. **Footer** — Timestamp + "Powered by Hello Plugins"

### Report Files

| File | Purpose |
|------|---------|
| `pdf-report/generate-report.php` | Endpoint: receive JSON, validate, render, output PDF |
| `pdf-report/report-template.php` | Master HTML assembly, calls all section renderers |
| `pdf-report/report-styles.php` | CSS 2.1 stylesheet (A4 portrait, 20mm margins) |
| `pdf-report/report-sections/header-section.php` | Logo + title + URL + date |
| `pdf-report/report-sections/score-summary-section.php` | Overall score + counters |
| `pdf-report/report-sections/seo-findings-section.php` | 4-category grouped SEO modules |
| `pdf-report/report-sections/wp-detection-section.php` | WordPress detection card |
| `pdf-report/report-sections/wp-plugins-section.php` | Plugin table + inline vulns |
| `pdf-report/report-sections/wp-theme-section.php` | Theme info + vulns |
| `pdf-report/report-sections/vulnerability-details-section.php` | Shared CVE block renderer |
| `pdf-report/report-sections/footer-section.php` | Timestamp + branding |

---

## Utility Layer

| File | Functions | Purpose |
|------|-----------|---------|
| `utils/fetcher.php` | `fetchUrlContent()`, `parseHttpHeaders()` | cURL HTTP client with redirect following, timing capture, header parsing |
| `utils/wp-api-client.php` | `fetchPluginInfoFromWordPressOrg()`, `fetchThemeInfoFromWordPressOrg()`, `fetchLatestWordPressCoreVersion()`, `fetchPluginVulnerabilities()`, `fetchThemeVulnerabilities()`, `fetchCoreVulnerabilities()`, `checkVersionAffected()` | WordPress.org + WPVulnerability.net API integrations |

---

## Configuration

| File | Purpose |
|------|---------|
| `config.php` | Empty stub (unused) |
| `config/ai-api-connection.php` | OpenAI API key + `callOpenAI()` + `getOpenAIAnalysisJson()` |
| `composer.json` | Dompdf dependency |
| `.gitignore` | Excludes vendor/, logs, API keys, temp files |

---

## Frontend Architecture

### Main Script: `scripts-n-css/app.js` (~46 KB)

**Key Functions:**
- `resetUI()` — Clears results, removes optimize/print buttons
- `showError(message)` — Error display
- `displayResults(analysisData)` — Tab creation, card rendering, animation trigger
- `renderCardsIntoContainer(renderOrder, rendererMap, auditData, container)` — Shared rendering loop
- `requestPdfReport(buttonElement)` — PDF blob download via fetch
- `requestContentOptimization()` — AI optimization panel submission
- `showOptimizationPanel()` / `hideOptimizationPanel()` — Slide-in panel
- `createExternalToolLinks(effectiveUrl)` — Dynamic tool links
- `triggerCardFadeInAnimation()` — Sequential card fade-in
- `updateStatusBubble(message)` / `hideStatusBubble()` — Status notifications

**Global State:** `auditResultCache` — stores full audit response for AI + PDF features

### Renderer Modules (25 files in `scripts-n-css/modules/`)

Each exports a single function: `export function renderXxxxxCard(data) → HTMLElement|null`

### Stylesheet: `scripts-n-css/styles.css` (~27 KB)

**Major sections:** Layout (2-column sticky sidebar), dark theme (#1f1f1f bg), accordion cards, status badges, SERP preview, image grid, tab navigation, AI panel (slide-in), status bubble, WP plugin table, CVSS colors, responsive breakpoints (1200/992/768/480px)

---

## HTML Structure: `index.php`

- Left column (`#left-column`, 320px sticky): `#analyzeForm` (URL input + Analyze button), external tool links section
- Right column (`#right-column`, flex: 1): `#loadingIndicator`, `#errorDisplay`, `#resultsArea`
- AI optimization panel (`#optimizePanel`): slide-in overlay with keyword/content inputs
- Status bubble: fixed bottom-center notification

---

## File Inventory

| Layer | Files | ~Size |
|---|---|---|
| PHP analyzer modules | 25 | 170 KB |
| JS renderer modules | 25 | 124 KB |
| Main app.js | 1 | 46 KB |
| CSS | 1 | 27 KB |
| PDF report system | 11 | 35 KB |
| Utilities | 2 | 19 KB |
| AI module | 1 | 2 KB |
| Config | 2 | 2 KB |
| HTML entry point | 1 | 3 KB |
| **Total code files** | **69** | **~428 KB** |

---

## Known Issues / Tech Debt

1. **API key in source** — OpenAI key in `config/ai-api-connection.php` (should use env vars)
2. **Empty config.php** — Dead file, unused
3. **No CSRF protection** on forms
4. **AI optimization limited** — Only generates title tags currently, despite UI collecting keywords + body text
5. **No caching** — Every scan re-fetches everything, no result persistence
6. **No authentication** — Open endpoint, anyone can scan
7. **Single-page app with no routing** — Everything in one HTML file
8. **Debug logs in production** — console.log statements throughout app.js
9. **Logo is white-on-black** — Doesn't render well on white PDF background
10. **Word doc todo** — Task tracking in .docx instead of issue tracker
