# Prompt: Rebalance SEO Module Scoring Weights

## Task

Update the module scoring weights in `config/scanning.php` to rebalance the SEO scoring system. The current weights are too generous — sites missing critical elements like H1 tags and meta descriptions score 60-75 when they should score 30-35. The rebalance is backed by cross-tool research (SEMrush, Ahrefs, Screaming Frog, Lighthouse) documented in `documentation/scoring-research.md`.

## File to Modify

`config/scanning.php` — the `"weights"` array (lines 78-156).

## Exact Weight Changes

Apply these changes to the `"weights"` array. Every module not listed below stays at its current value.

```
titleTag:          12 → 15   (ERROR in SEMrush + Ahrefs — universally critical)
contentKeywords:   10 → 12   (relevance is Google's #1 ranking factor)
h1Tag:              9 → 10   (WARNING in SEMrush/Ahrefs — important but not critical)
metaDescription:    5 →  8   (Ahrefs=ERROR, SEMrush=WARNING — affects CTR)
contentReadability: 8 →  9   (content quality = 23% of algo)
contentDuplicate:   7 →  8   (SEMrush=ERROR for duplicates)
redirectChain:      8 →  7   (slight reduction, still high)
sitemapAnalysis:    5 →  6   (SEMrush=ERROR for sitemap issues)
schemaOrg:          3 →  5   (SEMrush=ERROR, SF=HIGH — underweighted)
linkAnalysis:       6 →  7   (internal linking consensus Tier 2-3)
h2h6Tags:           4 →  5   (content structure signal)
urlStructure:       4 →  5   (SF=MEDIUM, clean URLs matter)
performanceHints:   8 →  6   (CWV is tiebreaker per Google, not primary)
eatTrustPages:      7 →  8   (real effort signal, E-E-A-T)
noindexCheck:      12 →  4   (WARNING in SEMrush + Ahrefs — baseline expectation)
blacklistCheck:    10 →  3   (not checked by any major tool — baseline)
viewportTag:        8 →  5   (SEMrush=ERROR but every template has it)
robotsMeta:         7 →  3   (WARNING — not blocking yourself is default)
robotsTxt:          6 →  5   (slight reduction)
htmlLang:           4 →  2   (basic HTML attribute)
doctypeCharset:     3 →  2   (HTML5 boilerplate)
```

## Modules That Stay the Same (no changes)

```
canonicalTag:       8  (keep — ERROR in SEMrush/Ahrefs)
imageAnalysis:      6  (keep — WARNING across tools)
httpHeaders:        5  (keep)
eatAuthor:          7  (keep)
eatBusinessSchema:  5  (keep)
eatPrivacyTerms:    4  (keep)
breadcrumbs:        3  (keep)
semanticHtml:       3  (keep)
hreflang:           3  (keep)
wpPlugins:          7  (keep)
wpTheme:            5  (keep)
socialTags:         0  (keep — info-only)
favicon:            0  (keep — info-only)
googleMapEmbed:     0  (keep — info-only)
analyticsDetection: 0  (keep — info-only)
wpDetection:        0  (keep — info-only)
serpPreview:        0  (keep — info-only)
```

## Update the Comments

Reorganize the tier comments to reflect the new philosophy. The tiers should be restructured as:

- **Tier 1: Content Fundamentals** — what makes or breaks SEO (titleTag 15, contentKeywords 12, h1Tag 10, contentReadability 9, metaDescription 8, contentDuplicate 8)
- **Tier 2: Technical Essentials** — confirmed ranking signals that require active configuration (canonicalTag 8, redirectChain 7, sitemapAnalysis 6, httpHeaders 5, robotsTxt 5, urlStructure 5)
- **Tier 3: E-E-A-T & Trust** — quality/trustworthiness signals (eatTrustPages 8, eatAuthor 7, eatBusinessSchema 5, eatPrivacyTerms 4)
- **Tier 4: Supporting Factors** — optimization opportunities (linkAnalysis 7, imageAnalysis 6, performanceHints 6, h2h6Tags 5, schemaOrg 5, viewportTag 5, breadcrumbs 3, semanticHtml 3, hreflang 3)
- **Tier 5: Baseline Expectations** — should pass on any functioning site (noindexCheck 4, blacklistCheck 3, robotsMeta 3, htmlLang 2, doctypeCharset 2)
- **Tier 6: WordPress-Specific** — unchanged (wpPlugins 7, wpTheme 5)
- **Info-Only** — unchanged (all weight 0 modules)

Update the block comment above the weights array to mention this rebalance is backed by cross-tool research (SEMrush, Ahrefs, Screaming Frog, Lighthouse) — reference `documentation/scoring-research.md` for methodology.

## Rules

- Only modify `config/scanning.php`
- Do not change any other files
- Do not modify the `"thresholds"` or `"crawl"` sections
- Use `array()` syntax (not `[]`) per project coding standards
- Use tab indentation (not spaces)
- Keep inline comments concise — one line per module explaining the weight rationale
- After making changes, run `php artisan config:clear` to clear cached config
