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

## Current vs Proposed Weights

| Module | Current | Proposed | Evidence |
|--------|---------|----------|----------|
| `titleTag` | 12 | **15** | ERROR in SEMrush + Ahrefs + SF HIGH — universally critical |
| `contentKeywords` | 10 | **12** | Our differentiator; relevance is Google's #1 ranking factor |
| `h1Tag` | 9 | **10** | WARNING in SEMrush/Ahrefs, MEDIUM in SF — important but not critical |
| `metaDescription` | 5 | **8** | Ahrefs=ERROR, SEMrush=WARNING, SF=LOW — split signals, affects CTR |
| `contentReadability` | 8 | **9** | SEMrush WARNING; content quality = 23% of algo (First Page Sage) |
| `contentDuplicate` | 7 | **8** | SEMrush=ERROR for duplicates — indexation confusion |
| `canonicalTag` | 8 | **8** | ERROR in SEMrush/Ahrefs — keep as-is |
| `redirectChain` | 8 | **7** | ERROR everywhere — slight reduction, still high |
| `sitemapAnalysis` | 5 | **6** | SEMrush=ERROR for sitemap issues — active SEO effort |
| `schemaOrg` | 3 | **5** | SEMrush=ERROR, SF=HIGH — currently underweighted |
| `imageAnalysis` | 6 | **6** | WARNING across tools — keep as-is |
| `linkAnalysis` | 6 | **7** | Internal linking is Tier 2-3 industry consensus |
| `h2h6Tags` | 4 | **5** | Content structure signal, heading hierarchy |
| `urlStructure` | 4 | **5** | SF=MEDIUM, clean URLs matter for crawlability |
| `performanceHints` | 8 | **6** | CWV is a "tiebreaker" per Google, not primary factor |
| `eatTrustPages` | 7 | **8** | Real effort signal, E-E-A-T for YMYL |
| `noindexCheck` | 12 | **4** | WARNING in SEMrush + Ahrefs — not having noindex is baseline |
| `blacklistCheck` | 10 | **3** | Not checked by any major tool — not being malware is baseline |
| `viewportTag` | 8 | **5** | SEMrush=ERROR but every modern template has this |
| `robotsMeta` | 7 | **3** | WARNING — not blocking crawlers is default state |
| `htmlLang` | 4 | **2** | Basic HTML attribute, rarely missing |
| `doctypeCharset` | 3 | **2** | HTML5 boilerplate, nearly universal |
| `httpHeaders` | 5 | **5** | No change |
| `robotsTxt` | 6 | **5** | Slight reduction |
| `eatAuthor` | 7 | **7** | No change |
| `eatBusinessSchema` | 5 | **5** | No change |
| `eatPrivacyTerms` | 4 | **4** | No change |
| `breadcrumbs` | 3 | **3** | No change |
| `semanticHtml` | 3 | **3** | No change |
| `hreflang` | 3 | **3** | No change |
| `wpPlugins` | 7 | **7** | No change |
| `wpTheme` | 5 | **5** | No change |

### Score Impact

**Old total weight:** 159 (non-WP)
**New total weight:** ~175 (non-WP)

**"Free points" (pass by default):**
- Old: ~60 points (37.7% of total) — terrible sites score 60-75
- New: ~28 points (16% of total) — terrible sites score ~30-35

**Score distribution shift:**
| Site Quality | Old Score | New Score |
|-------------|----------|-----------|
| Terrible (no H1, no meta, weak title) | 60-75 | ~30-35 |
| Mediocre (basics exist, not optimized) | 75-85 | ~50-55 |
| Good (optimized but gaps remain) | 85-90 | ~70-83 |
| Excellent (fully optimized) | 95-100 | ~88-97 |

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

### Industry Studies
- https://seranking.com/blog/seo-issues/ (418K domain audit study)
- Google API leak (14,000+ ranking attributes, 2024)
- First Page Sage 15-year algorithm study
- Semrush 2024 correlation study (300K SERPs)
- Backlinko 11.8M results study
