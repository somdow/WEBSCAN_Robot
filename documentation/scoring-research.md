# SEO Scoring Weight Research (February 2026)

Research conducted across SEMrush, Ahrefs, Screaming Frog, Google Lighthouse, WooRank, and Moz to validate and rebalance module scoring weights.

---

## Cross-Tool Severity Comparison

| Module | SEMrush | Ahrefs | Screaming Frog | Lighthouse |
|--------|---------|--------|----------------|-----------|
| Title Tag missing | **ERROR** | **ERROR** | **HIGH** | Equal (7.1%) |
| Meta Description missing | WARNING | **ERROR** | **LOW** | Equal (7.1%) |
| H1 Tag missing | WARNING | Warning (no score impact) | MEDIUM | Not checked |
| Viewport missing | **ERROR** | Not classified | Not classified | Equal (7.1%) |
| Canonical broken | **ERROR** | **ERROR** | **HIGH** | Equal (7.1%) |
| Redirect Chain | **ERROR** | **ERROR** | HIGH | Not checked |
| Schema invalid | **ERROR** | Validation findings | **HIGH** | Not scored |
| Noindex present | Warning/Notice | Warning (no score impact) | Warning HIGH | Equal (7.1%) |
| Image Alt missing | Warning | Warning (no score impact) | **LOW** | Equal (7.1%) |
| Robots.txt issues | **ERROR** | Warning | Medium | Equal (7.1%) |
| Content Keywords | Not checked | Not checked | Not checked | Not checked |
| Content Readability | Warning | Not checked | Medium | Not checked |
| Blacklist/Malware | Not checked | Not checked | Not checked | Not checked |

---

## Tool-by-Tool Methodology

### SEMrush Site Audit

- **Score formula:** `Health = f(checks_passed / total_checks_performed)` — proprietary weighted formula
- **Errors** (red) have MORE weight than warnings. Exact multiplier not disclosed.
- **Warnings** (orange) have medium weight.
- **Notices** (gray) have ZERO impact on score.
- **140+ checks** across 8 thematic reports.
- Frequency-based scoring (% of pages affected), not raw count.
- Fixing ALL instances of one issue type > fixing scattered individual issues.

**SEMrush ERROR-level issues (43 types):**
- Missing title tags, duplicate titles, duplicate content
- Broken internal links/images, broken JS/CSS
- Missing viewport tag, missing viewport width
- Broken/multiple canonical tags
- Redirect chains and loops
- Invalid structured data
- Robots.txt/sitemap format errors
- Hreflang conflicts
- SSL/HTTPS errors, mixed content
- 4XX/5XX status codes
- Slow page speed, oversized HTML

**SEMrush WARNING-level issues (48 types):**
- Missing meta descriptions (NOT error)
- Missing H1 tag (NOT error)
- Title too long/short
- Missing image alt text
- Low word count, low text-to-HTML ratio
- Temporary redirects (302/307)
- Missing doctype/charset
- Noindex pages
- Too many on-page links

**SEMrush NOTICE-level (no score impact):**
- Permanent redirects (301s)
- Links with no anchor text
- Orphaned pages

### Ahrefs Site Audit

- **Score formula:** `Health = (URLs without Errors / Total URLs) x 100`
- **Simplest formula** — binary error/not-error per URL.
- Only **ERROR-level** issues reduce the score.
- **Warnings and Notices have ZERO impact** on the health score.
- No individual weights per issue type — an error is an error.
- Users can customize severity levels per project.
- **170+ pre-defined issues.**

**Ahrefs ERROR-level (affects score):**
- Missing/empty title tag
- Missing/empty meta description
- 4XX/5XX pages
- Redirect chain, redirect loop, broken redirect
- Canonical points to 4XX/5XX/redirect
- Duplicate pages without canonical
- Noindex page receives organic traffic (NOT noindex alone)
- Orphan page (no internal links)
- Broken internal links/images
- HTTPS linking to HTTP
- Hreflang errors

**Ahrefs WARNING-level (no score impact):**
- Missing/empty H1 tag
- Noindex page (alone, without traffic)
- Missing image alt text
- 302 redirects
- CSS/JS issues

**Ahrefs prevalence data (1,002,165 domains):**
- 80.4% missing image alt text
- 72.9% missing meta description
- 59.5% missing H1 tag
- 68.5% title doesn't match SERP
- 63.2% title too long
- 66.2% only one dofollow internal link

### Screaming Frog SEO Spider

- **No health score** — uses Issue Type x Priority matrix.
- **300+ issues** across 24 categories.
- Classification: Issue/Warning/Opportunity x High/Medium/Low.

**HIGH priority issues:**
- Missing title (Issue HIGH)
- Multiple/conflicting canonicals (Issue HIGH)
- Exact duplicate content (Issue HIGH)
- 4XX/5XX responses (Issue HIGH)
- Redirect loops (Issue HIGH)
- Structured data validation errors (Issue HIGH)
- Mixed content / HTTP URLs (Issue HIGH)
- Hreflang errors (Issue HIGH)

**MEDIUM priority issues:**
- Missing H1 (Issue MEDIUM)
- Multiple meta descriptions (Issue MEDIUM)
- Missing canonical (Warning MEDIUM)
- Spelling/grammar errors (Issue MEDIUM)
- Non-indexable URLs in sitemap (Issue MEDIUM)
- High crawl depth (Opportunity MEDIUM)

**LOW priority issues:**
- Missing meta description (Opportunity LOW — notable!)
- Missing image alt text (Opportunity LOW)
- URL formatting issues (LOW)
- Security headers (LOW)
- H2 heading issues (LOW)

### Google Lighthouse SEO Audit

- **14 automated audits**, all **equally weighted** (~7.1% each).
- Pass/fail with no partial credit.
- Failing 1 audit = ~92 score (13/14).

**The 14 audits:**
1. Viewport meta tag
2. Title element present
3. Meta description present
4. HTTP status code (200)
5. Not blocked by noindex
6. Descriptive link text
7. Crawlable links (valid href)
8. Robots.txt valid
9. Image alt text
10. Hreflang valid
11. Canonical valid
12. Font size (12px+ for 60%+ text)
13. No plugins (Flash/Java)
14. Tap targets appropriately sized

**Structured data** is an additional unscored manual check.

### WooRank

- **Proprietary algorithm** — specific weights NOT publicly disclosed.
- **~70 data points** scored across 3 pillars:
  - **Accessibility** — can search engines and users reach the page?
  - **Readability** — can content be correctly interpreted?
  - **Quality** — does it provide a good user experience?
- Score bands: 70-100 (green/good), 41-69 (yellow/improve), 0-40 (red/significant issues)
- **9 dashboard sections:** SEO, Mobile, Usability, Technologies, Backlinks, Social, Local, Visitors, Security
- Color-coded severity: Red (critical), Yellow (improve), Green (passed)
- Page-level reviews use 30 criteria producing letter grades (A+ through E)

### Moz Pro Site Crawl

- Uses **5 category groups** instead of traditional severity tiers.
- **Only server errors (5XX), client errors (4XX), and redirects to 4XX are "Critical."**
- Everything else is a "Warning" including metadata issues.
- Prioritization by: prevalence weighting, Page Authority, crawl depth.
- No numerical scores per issue.

---

## Industry Consensus: SEO Factor Importance Tiers

**TIER 1 — Critical / Crawlability Blockers (universally highest):**
- Server errors (5XX) — blocks crawling entirely
- Client errors (4XX) / broken links — wastes crawl budget
- Redirect loops — infinite loops block indexing
- Missing title tags — directly affects SERP display and rankings
- Duplicate content — dilutes ranking signals
- SSL/HTTPS errors — security ranking signal
- Broken canonical tags — causes duplicate content confusion

**TIER 2 — High Impact / On-Page Fundamentals:**
- Missing/broken canonical tags
- Hreflang errors
- Structured data errors — prevents rich results
- Sitemap errors — impairs crawl efficiency
- Missing viewport meta tag — mobile-first indexing

**TIER 3 — Medium Impact / Optimization:**
- Missing/duplicate meta descriptions — affects CTR, not rankings directly
- Missing/multiple H1 tags — content hierarchy signal
- Title length issues
- Slow page speed — tiebreaker ranking signal
- High crawl depth (3+ clicks)
- Near-duplicate content
- Redirect chains (not loops)
- Low word count / thin content

**TIER 4 — Low Impact / Best Practices:**
- Missing image alt text — accessibility + image search
- URL formatting issues
- Missing security headers
- Non-descriptive anchor text
- H2 heading issues
- Unminified/uncompressed assets

**Key Google statement:** Content relevance and quality remain the #1 ranking factor. Core Web Vitals act as a tiebreaker between similar-quality pages. "Relevance is still by far much more important" — John Mueller.

---

## Prevalence Data (418K+ domains, SE Ranking)

- 74% missing alt text
- 69% orphan pages
- 68% overly long titles
- 65% missing meta descriptions
- 36% 4XX errors
- 35% slow page speed
- 5% true duplicate content

---

## Industry Tool Scoring Approaches (Mar 2026 Deep Research)

### Scoring Formula Comparison

| Tool | Approach | Mediocre Score |
|------|----------|---------------|
| WooRank | Proprietary additive, ~70 data points, 3 pillars | 40-65 (eCommerce avg: 54.7) |
| SEMrush | Weighted ratio, errors >> warnings, notices=0 | ~70-80 |
| Ahrefs | Binary: URLs without errors / total × 100 | 50-70 |
| Sitechecker | Deduction: start 100, subtract (60pt critical / 40pt warning budget) | 30-59 |
| Seobility | Deduction: start 100, subtract per issue, 200+ signals | 50-65 |
| Ubersuggest | Proprietary, critical >> warning >> recommendation | 55-70 |
| SE Ranking | Category weight × severity × page coverage | 60-79 |
| Raven Tools | Proprietary 0-100, severity-ordered | 40-60 |
| Lighthouse SEO | Equal-weight binary, ~14 audits (~7% each) | ~85 |
| Moz | No composite score — issue triage only | N/A |

### Warning/Error Treatment

| Tool | Errors | Warnings | Notices |
|------|--------|----------|---------|
| Sitechecker | Share 60-point budget | Share 40-point budget | Zero |
| SEMrush | Heavy (undisclosed multiplier) | Medium | Zero |
| Ahrefs | Only thing counted | **Zero** | Zero |
| SE Ranking | Highest multiplier | Medium | Lowest |
| Ubersuggest | Critical — outsized impact | "Won't make or break" | Zero |

### Key Benchmarks (WooRank Index)

- All eCommerce websites: **54.7**
- Shopify websites (global): **66.0**
- Managed SMB sites (Pronto): **61.3**
- Small business best: **low 70s** (none over 75)
- Score bands: 0-40 (poor), 41-69 (improve), 70-100 (good)

---

## Recalibration (Mar 2026)

### Two-Lever Fix

**Lever 1: Warning multiplier 0.50 → 0.25**
Industry evidence: Ahrefs gives warnings 0.0, Sitechecker caps at 40% of smaller budget,
SEMrush weights errors "significantly" more. Our old 0.5 was the most generous in the industry.

**Lever 2: Slash easy-pass module weights**
Modules where default state = pass (redirectChain, httpsRedirect, viewportTag, etc.)
had inflated weights from Round 2 redistribution. Reduced to reflect low differentiation value.

### Final Weights (active in config/scanning.php)

| Module | Weight | Rationale |
|--------|--------|-----------|
| titleTag | 15 | #1 on-page signal, 68% have issues |
| h1Tag | 12 | 59% missing per Ahrefs |
| contentReadability | 12 | Content quality differentiator |
| metaDescription | 10 | 72% missing per Ahrefs |
| contentDuplicate | 8 | Only 5% true duplicates — most pass |
| canonicalTag | 8 | ERROR-level but CMSes auto-add |
| redirectChain | 5 | Default state = no chains |
| httpsRedirect | 5 | Default in 2026 |
| sitemapAnalysis | 6 | CMSes auto-generate |
| duplicateUrl | 4 | Default state fine |
| httpHeaders | 3 | Basic HTTPS default |
| robotsTxt | 4 | Most have basic one |
| urlStructure | 5 | Requires intentional effort |
| eatTrustPages | 4 | Indirect trust signal |
| eatAuthor | 3 | YMYL-dependent |
| eatBusinessSchema | 3 | Not required |
| eatPrivacyTerms | 2 | Indirect only |
| breadcrumbs | 2 | Not for all site types |
| linkAnalysis | 7 | Google-confirmed signal |
| brokenLinks | 8 | ERROR across tools |
| imageAnalysis | 8 | 80% fail (Ahrefs) |
| coreWebVitalsMobile | 6 | Google tiebreaker |
| coreWebVitalsDesktop | 5 | Complements mobile |
| performanceHints | 6 | Tiebreaker, not primary |
| h2h6Tags | 5 | Most CMS sites have headings |
| schemaOrg | 5 | Enables rich results |
| schemaValidation | 5 | Google rich result reqs |
| viewportTag | 3 | Every template has this |
| compressionCheck | 4 | Server optimization |
| accessibilityCheck | 3 | Form labels, ARIA |
| semanticHtml | 3 | Landmarks |
| hreflang | 3 | International only |
| sslCertificate | 4 | Most sites valid; score_cap if broken |
| securityHeaders | 4 | Many sites fail |
| mixedContent | 4 | Most modern sites pass |
| exposedSensitiveFiles | 5 | Compromise risk |
| noindexCheck | 3 | Baseline expectation |
| blacklistCheck | 2 | Not being malware = baseline |
| robotsMeta | 2 | Default state |
| htmlLang | 2 | Rarely missing |
| doctypeCharset | 2 | Nearly universal |
| wpPlugins | 7 | Security liability |
| wpTheme | 5 | Security advisories |

### Modeled Score Distribution

| Site Quality | Score |
|-------------|-------|
| Terrible (broken basics) | ~34% |
| Mediocre (basics exist, not optimized) | ~55% |
| Good (optimized, some gaps) | ~70% |
| Excellent (fully optimized, few warnings) | ~90% |

Target alignment: WooRank mediocre ~55 ✓, Raven mediocre ~50 ✓, Seobility mediocre ~57 ✓

---

## Sources

### SEMrush
- https://www.semrush.com/kb/114-total-score
- https://www.semrush.com/kb/542-site-audit-issues-list
- https://www.semrush.com/kb/541-site-audit-issues-report
- https://www.semrush.com/kb/540-site-audit-overview
- https://www.semrush.com/kb/959-site-audit-thematic-reports

### Ahrefs
- https://help.ahrefs.com/en/articles/1424673-what-is-health-score-and-how-is-it-calculated-in-ahrefs-site-audit
- https://help.ahrefs.com/en/articles/1420169-how-to-configure-pre-set-issues-within-ahrefs-site-audit
- https://help.ahrefs.com/en/articles/2754356-what-does-title-tag-missing-or-empty-issue-in-site-audit-mean
- https://help.ahrefs.com/en/articles/2764262-h1-tag-missing-or-empty-warning-in-site-audit
- https://help.ahrefs.com/en/articles/2630975-meta-description-tag-missing-or-empty-error-in-site-audit
- https://ahrefs.com/blog/site-audit-study/

### Screaming Frog
- https://www.screamingfrog.co.uk/seo-spider/issues/

### Google Lighthouse
- https://github.com/GoogleChrome/lighthouse/blob/main/docs/scoring.md
- https://www.debugbear.com/blog/lighthouse-seo-score

### WooRank
- https://support.repli360.com/knowledge/what-is-the-woorank-score
- https://support.duda.co/hc/en-us/articles/10385255571479-WooRank

### Moz
- https://moz.com/site-crawl
- https://marketingtoolpro.com/2025/07/moz-site-crawl-review/

### Sitechecker
- https://help.sitechecker.pro/article/81-how-is-website-score-calculated
- https://help.sitechecker.pro/article/18-how-is-page-score-calculated

### Seobility
- https://www.seobility.net/en/seocheck/
- https://www.seobility.net/en/blog/how-to-do-an-seo-audit/

### Ubersuggest
- https://ubersuggest.zendesk.com/hc/en-us/articles/4405452089115
- https://ubersuggest.zendesk.com/hc/en-us/articles/4405452107803

### SE Ranking
- https://seranking.com/blog/new-website-audit/
- https://help.seranking.com/hc/en-us/articles/16332611958044

### Raven Tools
- https://raventools.com/site-auditor/
- https://raven.zendesk.com/hc/en-us/articles/202346870

### Industry Studies
- https://seranking.com/blog/seo-issues/ (418K domain audit study)
- Google API leak (14,000+ ranking attributes, 2024)
- First Page Sage 15-year algorithm study
- Semrush 2024 correlation study (300K SERPs)
- Backlinko 11.8M results study
- WooRank Index: https://index.woorank.com/en/reviews (live score averages)
- Pronto Marketing SMB study: https://www.prontomarketing.com/blog/how-does-your-site-score/
