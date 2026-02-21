# The Masterplan — Hello SEO Analyzer v2

> Complete product roadmap, phased delivery plan, and feature specification.
> Companion document to `the-blueprint.md` (v1 feature catalog).
>
> **Created:** February 6, 2026
> **Status:** Phase 1 Complete (Sprints 1-9 delivered)
> **Last Updated:** February 10, 2026

---

## Product Vision

**Hello SEO Analyzer v2** — the E-E-A-T-era SEO audit tool that tells business owners (or their agencies) exactly what Google thinks of their site in 2026, with AI-powered recommendations they can actually act on.

### Core Differentiators

1. **E-E-A-T analysis** — something the big tools barely touch
2. **AI-powered qualitative assessment** — not just "missing meta description" but "here's a better one"
3. **Business-owner readable reports** — executive summary, priority actions, plain English
4. **Agency-ready** — white-label, branded, batch scanning, lead generation
5. **Multi-user at accessible pricing** — team access at $49/mo vs WooRank's $200+ Enterprise-only

### Target Users

- **Agencies** — scan prospect sites, generate branded reports, close deals
- **Freelance SEO consultants** — deliver professional audits to clients
- **Business owners** — understand their own site's SEO health without hiring an expert
- **Marketing teams** — track SEO performance over time across multiple properties

---

## Technology Stack

| Layer | Technology | Why |
|---|---|---|
| **Framework** | Laravel | Queue system, Eloquent ORM, Sanctum auth, Cashier billing, Blade templates, ecosystem |
| **Database** | SQLite (dev) → MySQL (production) | SQLite for rapid local development; MySQL for production deployment |
| **Cache/Queue** | Redis | Async scan jobs, result caching, rate limiting, sessions |
| **Admin Panel** | Laravel Filament | Auto-generated admin CRUD, dashboards, custom actions |
| **Payments** | Stripe via Laravel Cashier | Subscriptions, trials, coupons, invoices, webhooks |
| **AI** | Model-agnostic gateway (OpenAI, Claude, etc.) | Interface abstraction, swap providers via config |
| **PDF** | Dompdf | Existing integration, CSS 2.1, proven in v1 |
| **Email** | Postmark / Resend / SES | Transactional email deliverability |
| **File Storage** | S3 or equivalent | PDF reports, uploaded logos (white-label) |
| **Hosting** | Laravel Forge + DigitalOcean/Hetzner | Managed deployment, SSL, queue workers, scheduler |
| **Error Tracking** | Sentry / Flare | Production error monitoring |
| **Queue Monitoring** | Laravel Horizon | Redis queue dashboard, job throughput, failures |
| **CSS Framework** | Tailwind CSS | Utility-first, purged in production, design token mapping |
| **Frontend JS** | Alpine.js | Reactive UI (dropdowns, modals, tabs) without SPA complexity |
| **Charts** | Chart.js or ApexCharts | Score trend lines, lightweight, no D3 overkill |
| **Build Tool** | Vite (Laravel default) | Asset bundling, Tailwind purge, hot reload in dev |

---

## Architecture Overview

### Module Pattern (carried from v1, enhanced)

```
AnalyzerInterface
    ├── analyze(ScanContext): AnalysisResult
    │
    ├── TitleTagAnalyzer
    ├── MetaDescriptionAnalyzer
    ├── EeatAuthorAnalyzer
    ├── ContentReadabilityAnalyzer
    ├── ... (one class per module)
```

Each analyzer returns: `{ status, findings: [{ type, message }], recommendations: [string] }`

### Data Flow

```
User submits URL
    → ScanController creates ProcessScanJob
    → Job dispatched to Redis queue
    → ScanOrchestrator runs:
        ├── MiniCrawler fetches homepage + trust pages
        ├── Each Analyzer runs against collected page data
        ├── AiGateway generates summaries + suggestions
        └── Results stored in database
    → Frontend polls for results (progressive loading)
    → User views dashboard or generates PDF report
```

### Database Schema (Core)

```
users
    ├── id, name, email, password, email_verified_at
    ├── is_super_admin (boolean)
    └── timestamps

organizations
    ├── id, name, slug, logo_path
    ├── stripe_id, pm_type, pm_last_four, trial_ends_at (Cashier)
    └── timestamps

organization_user (pivot)
    ├── organization_id, user_id
    └── role (owner | admin | member | viewer)

plans
    ├── id, name, slug, description
    ├── stripe_monthly_price_id, stripe_annual_price_id
    ├── price_monthly, price_annual (display values)
    ├── max_users, max_projects, max_scans_per_month
    ├── max_competitors, scan_history_days
    ├── ai_tier (1 | 2 | 3)
    ├── feature_flags (JSON: white_label, api_access, scheduled_scans, leadgen)
    ├── is_public, sort_order
    └── timestamps

projects
    ├── id, organization_id, name, url
    ├── scan_schedule (null | weekly | monthly)
    └── timestamps

scans
    ├── id, project_id, triggered_by (user_id | scheduler)
    ├── status (pending | running | completed | failed)
    ├── overall_score, scan_duration_ms
    ├── is_wordpress (boolean)
    └── timestamps

scan_module_results
    ├── id, scan_id, module_key
    ├── status (ok | warning | bad | info)
    ├── findings (JSON), recommendations (JSON)
    └── ai_summary, ai_suggestion (nullable)

coupons
    ├── id, code, stripe_coupon_id
    ├── discount_type (percent | fixed | free_months)
    ├── discount_value
    ├── applicable_plan_ids (JSON, null = all)
    ├── max_redemptions, times_redeemed
    ├── expires_at, is_active
    └── timestamps

subscription_usage
    ├── id, organization_id
    ├── period_start, period_end
    ├── scans_used, ai_calls_used, api_calls_used
    └── timestamps

notifications
    ├── id, user_id, type, data (JSON)
    ├── read_at
    └── timestamps
```

---

## Pricing Model

| | Free | Pro ($49/mo) | Agency ($149/mo) | Enterprise (Custom) |
|---|---|---|---|---|
| **Users** | 1 | 3 | 10 | Unlimited |
| **Projects** | 1 | 5 | 25 | Unlimited |
| **Scans/month** | 10 | 100 | 500 | Unlimited |
| **AI Features** | Tier 1 (summaries) | Tier 1 + 2 (fix-it) | All tiers | All + custom |
| **PDF Reports** | Branded | Branded | White-label | White-label |
| **Scan History** | 7 days | 90 days | Unlimited | Unlimited |
| **Competitor Compare** | No | 1 | 5 | Unlimited |
| **Recurring Scans** | No | No | Weekly/Monthly | Custom |
| **API Access** | No | No | Yes | Yes |
| **LeadGen Widget** | No | No | Yes | Yes |
| **Support** | Community | Email | Priority | Dedicated + phone |

**Annual billing:** 20-25% discount on Pro and Agency.

**Trial model:** No card for Free tier (forever-free). Card required for 14-day Pro or Agency trial.

---

## Phased Delivery Plan

### Phase 1 — MVP: "One User, One Scan, One Report"

**Goal:** A working SaaS product that someone can sign up for, scan a website, and get a professional report. Replaces the prototype with a real product foundation.

**Revenue target:** Free + Pro ($49/mo) plans live, accepting payments.

#### Scan Engine

- Port all 23 existing SEO modules to Laravel Analyzer classes
- Port all 3 WordPress scanner modules
- Smart mini-crawl (homepage + 3-5 trust pages via nav/footer link extraction)
- Progressive scan loading (results stream to frontend as modules complete)
- Scan result caching (same URL within 1 hour serves cached results)
- Async scanning via Laravel queue (user gets immediate response, polls for results)

#### New Analyzer Modules (5-8)

| Module | Category | What It Checks |
|---|---|---|
| Author Detection | E-E-A-T | Visible author name, author bio, author page links, credentials |
| Trust Pages | E-E-A-T | About page, Contact page existence and content quality |
| Privacy & Terms | E-E-A-T | Privacy policy, Terms of Service, Cookie policy detection |
| Business Schema | E-E-A-T | Organization/LocalBusiness JSON-LD with address, phone, founding date |
| Content Readability | Content | Word count, Flesch-Kincaid readability score, thin content detection |
| Keyword Presence | Content | Target keyword in title, H1, first paragraph, URL, meta description |
| Duplicate Content Signals | Content | Meta description matching first paragraph, identical title/H1 |

#### AI Layer

- `AiGateway` interface + `OpenAiGateway` implementation
- Model/provider configurable via `.env` (`AI_PROVIDER=openai`, `AI_MODEL=gpt-4`)
- Tier 1: AI executive summary (feed all module results, get plain-English overview)
- Tier 2: AI fix-it suggestions (optimized title, meta description, H1, image alt text)
- Prompt templates stored in dedicated `Prompts/` directory

#### User System

- Email/password registration + login via Laravel Sanctum
- Email verification + password reset (Laravel built-in)
- Remember me tokens
- User profile settings (name, email, password change)
- Organization auto-created on signup (org of one)
- Single role for Phase 1: Owner
- Rate limiting on login attempts

#### Billing (Stripe + Laravel Cashier)

- Stripe Checkout for payment collection (hosted — we never touch card data)
- Two plans: Free (no card) and Pro ($49/mo, card + 14-day trial)
- Subscription on Organization model (not User)
- Automatic monthly charging + Stripe retry logic on failure
- Webhook handler for subscription events
- Billing settings page (current plan, payment method, invoices, cancel)
- Upgrade/downgrade flow with proration
- Basic coupon code redemption at checkout

#### Super Admin Panel (Laravel Filament)

- Separate auth guard with `is_super_admin` check
- Dashboard home: total users, active subscriptions, scans today, MRR
- Plan management: full CRUD, adjust all limits and feature flags, changes apply instantly
- User management: search, view, suspend, delete, override email verification
- Organization management: view, change plan manually, adjust per-org limits
- Coupon management: create/edit codes, set discount/expiry/limits, view redemptions
- Trial management: grant/extend trial for specific org
- Audit log: every admin action logged (who, what, when, old value, new value)

#### Reports (PDF)

- Ported from v1 with Dompdf, enhanced structure
- Category-grouped findings (7 categories with new E-E-A-T and Content groups)
- Overall 0-100 score with color grade
- AI executive summary section at top of report
- Priority-ranked findings (most impactful issues first)
- Per-module AI recommendations where available
- Filename: `SEO-Report-{hostname}-{YYYY-MM-DD}.pdf`

#### Frontend (Tailwind CSS + Alpine.js + Blade)

- Stripe/Linear-inspired design: clean, minimal, professional
- Tailwind CSS utility-first styling with custom color tokens
- Alpine.js for reactive UI (expand/collapse, modals, dropdowns, tabs)
- Laravel Blade server-rendered templates (no SPA)
- Vite for asset bundling + Tailwind purge
- Top nav + optional sidebar layout, max-width centered container
- Dashboard: stat cards + project list with score deltas
- Scan results: score hero → category grid → quick wins → detailed findings
- Module detail: inline expand with AI recommendation box
- SERP preview: visual Google-style snippet rendering
- Progressive result loading with real-time scan status
- Responsive: mobile-first, works on desktop/tablet/mobile
- Chart.js for score trend lines on project detail page
- Empty states with guided CTAs on all pages
- 12 pages for Phase 1 (see UI/Frontend Design System section)

#### Infrastructure

- Laravel project with SQLite (dev) / MySQL (production)
- Laravel Forge deployment to DigitalOcean/Hetzner
- Redis queue worker for async scans
- Laravel Scheduler for recurring jobs
- GitHub repo with CI (automated tests on push)
- Staging environment mirroring production
- SSL via Forge/Let's Encrypt
- `.env` for all secrets (API keys, Stripe keys, database credentials)
- Error tracking via Sentry or Flare
- Structured logging (scan events, errors, timing)

#### Onboarding

- Guided first-scan flow ("Let's scan your first website")
- Empty state designs for all pages
- Post-scan value reveal (score + executive summary + quick wins)

#### Legal

- Terms of Service page
- Privacy Policy page
- Cookie consent banner
- Acceptable Use Policy (scan only sites you own or have permission)

#### Sprint Breakdown (Phase 1)

| Sprint | Focus | Deliverables |
|---|---|---|
| 1 | Foundation | Laravel project, database schema, migrations, auth system, org model |
| 2 | Scan Engine Port | All 23 SEO analyzers + 3 WP analyzers as Laravel classes, ScanOrchestrator |
| 3 | New Modules | E-E-A-T modules, content analysis modules, mini-crawl infrastructure |
| 4 | AI Layer | AiGateway, executive summary, fix-it suggestions, prompt templates |
| 5 | Billing | Stripe integration, Cashier setup, plans, checkout flow, webhooks |
| 6 | Admin Panel | Filament setup, plan/user/org/coupon management, dashboard metrics |
| 7 | Frontend | Dashboard UI, scan results view, PDF report generation, responsive design |
| 8 | Polish | Onboarding flow, email templates, testing, deployment, legal pages |

---

### Phase 2 — Teams & Tracking: "Agency Ready"

**Goal:** Multi-user team support, multiple projects, recurring scans, score history. Agencies can manage client sites and deliver ongoing value.

**Revenue target:** Agency plan ($149/mo) live.

#### New Analyzer Modules

| Module | Category | What It Checks |
|---|---|---|
| Schema Validation | Structured Data | FAQ, Breadcrumb, Product, Review schema against Google required fields |
| Broken Link Detection | Link Quality | Outbound links checked for 404/error responses |
| Anchor Text Analysis | Link Quality | "click here" vs descriptive anchors, distribution analysis |
| Rel Attribute Audit | Link Quality | nofollow, sponsored, ugc usage appropriateness |
| Image Optimization | Performance | File size, WebP/AVIF detection, lazy loading, filename relevance |
| Core Web Vitals | Performance | LCP, INP, CLS via PageSpeed Insights API integration |

#### User System Enhancements

- Team invitation flow (email invite → accept → join org with role)
- Roles: Owner, Admin, Member, Viewer
- Role-based access via Laravel Policies on all models
- Client viewer role (agencies invite clients for read-only report access)
- Team management page (invite, change roles, remove members)

#### Billing Enhancements

- Agency plan: $149/mo (10 users, 25 projects, 500 scans, white-label, API)
- Annual billing option with 20-25% discount
- Plan limit enforcement with soft limits + upgrade prompts
- Usage tracking dashboard ("45 of 100 scans used this month")
- Grandfathering: existing subscribers keep old price on price increases

#### Admin Panel Enhancements

- Organization membership views (team rosters, roles)
- Per-org limit overrides (give specific org extra scans without changing plan)
- User impersonation with full audit logging
- Coupon analytics (redemption rates, revenue impact)
- AI cost tracking per organization
- Email send log

#### Projects & History

- Multiple projects per org (based on plan tier)
- Scan history per project with paginated list
- Score trending over time (line chart visualization)
- Module-level trend tracking (technical SEO improved, content declined)
- Current vs previous scan comparison (delta indicators: +5, -3)

#### Recurring Scans

- Weekly or monthly auto-scan per project (Agency tier)
- Laravel Scheduler triggers `ProcessScheduledScansJob`
- Scan summary email: "Your 5 sites: 3 improved, 1 declined, 1 unchanged"
- Admin can enable/disable scheduled scanning globally

#### Reports Enhanced

- White-label PDF reports (Agency tier): custom logo, company name, brand colors
- One-page executive summary option (score + top 5 issues + contact info)
- Comparison report section (this scan vs last scan, with deltas)
- Quick wins section ("fix these 3 things for the biggest score improvement")

#### Notifications

- In-app notification center (bell icon with dropdown)
- Per-user email notification preferences
- Notification types: scan complete, score change, vulnerability found, plan limit warning, team member joined
- Laravel Notifications with database + email channels

#### Email System

- Full transactional email suite via Postmark/Resend:
  - Welcome, email verification, password reset
  - Team invitation, member joined
  - Scan complete, weekly/monthly summary
  - Trial ending (3 days, 1 day), trial expired
  - Payment failed, payment received
  - Plan changed, account cancelled
- Admin toggle for each email type (on/off)

---

### Phase 3 — Competitive Edge: "Intelligence Layer"

**Goal:** Competitor comparison, deep AI analysis, public API, and LeadGen widget. Features that create competitive moats and justify premium pricing.

**Revenue target:** Enterprise plan (custom) live. API and LeadGen driving agency retention.

#### Competitor Analysis

- Scan your site vs 1-5 competitors (based on plan tier)
- Side-by-side score comparison dashboard
- Per-module comparison (your title tag vs their title tag)
- AI-powered gap analysis ("competitor covers these topics, you don't")
- Competitor comparison section in PDF reports

#### AI Layer — Tier 3

- Deep content evaluation (is this helpful content or AI slop?)
- E-E-A-T qualitative scoring (AI reads author bio and evaluates expertise signals)
- Search intent matching (does page content match what searchers want?)
- Content gap analysis (suggest missing topics, questions to answer, related keywords)
- Claude/Anthropic gateway implementation (model flexibility in production)
- AI cost optimization: prompt caching, batching, token management
- Per-module plain-English explanations for non-technical users

#### Public API (Agency+ Tiers)

- RESTful API: `/api/v1/scan`, `/api/v1/scan/{id}`, `/api/v1/projects`, etc.
- API key management in user dashboard (create, revoke, regenerate)
- Auto-generated OpenAPI documentation via Scramble
- Rate limiting with `X-RateLimit-Remaining` headers
- Webhook system: register callback URLs, receive events (scan.completed, vulnerability.found)
- Usage metering: API calls tracked per billing period

#### LeadGen Widget (Agency+ Tiers)

- Embeddable JavaScript snippet for agency websites
- Customizable appearance: colors, branding, fields displayed
- Visitor enters URL + email → receives mini-audit results
- Lead captured: email, URL, mini-score stored and emailed to agency
- Widget management dashboard: embed code, appearance settings, lead list
- Rate limited per organization API key

#### Reports Enhanced

- Competitor comparison section in PDF
- AI-generated content recommendations section
- Industry benchmark context ("you score better than 73% of similar sites")

#### Admin Panel Enhancements

- API usage monitoring per organization
- LeadGen widget analytics (leads captured, conversion rates)
- Competitor scan cost tracking
- Benchmark data management (anonymized aggregates)

---

### Phase 4 — Scale: "Platform"

**Goal:** Enterprise features, third-party integrations, full site crawling, and infrastructure for large-scale usage.

**Revenue target:** Enterprise accounts with annual contracts.

#### Enterprise Features

- SSO / SAML authentication
- Custom data retention policies per org
- Dedicated support tier with SLA guarantees
- Multi-language report support
- Custom module configuration (enable/disable specific checks per org)
- Bulk scan import (CSV of URLs → batch process)

#### Third-Party Integrations

- Google Search Console (pull real ranking data, crawl errors, search queries)
- Google Analytics (correlate SEO changes with traffic impact)
- Zapier integration (webhook-based, connects to 5,000+ tools)
- Slack / Microsoft Teams notifications
- CRM integrations: HubSpot, Salesforce (push LeadGen leads directly)

#### Full Site Crawler

- Crawl hundreds/thousands of pages per site
- Site architecture analysis (crawl depth, internal link graph)
- Orphan page detection (pages with no internal links)
- Crawl budget optimization recommendations
- Broken internal link detection at scale
- Duplicate content detection across pages
- Visual site map / link graph

#### Compliance & Scale

- GDPR: full data export workflow + account deletion workflow
- SOC 2 compliance preparation
- Multi-region deployment (EU data residency option)
- Auto-scaling infrastructure (Laravel Vapor or Kubernetes)
- Advanced monitoring, alerting, and SLA dashboards
- Database read replicas for reporting queries

#### Advanced Analytics

- Product analytics dashboard (feature usage, user funnels, cohort analysis)
- Industry benchmark database (anonymized aggregate across all users)
- Churn prediction (flag at-risk accounts based on usage patterns)
- Revenue forecasting in admin panel

---

## Module Inventory (All Phases)

### Carried from v1 (23 SEO + 3 WordPress)

| # | Key | Module | Category |
|---|---|---|---|
| 1 | titleTag | Title Tag | On-Page SEO |
| 2 | metaDescription | Meta Description | On-Page SEO |
| 3 | h1Tag | H1 Heading | On-Page SEO |
| 4 | h2h6Tags | H2-H6 Headings | On-Page SEO |
| 5 | serpPreview | SERP Preview | On-Page SEO |
| 6 | canonicalTag | Canonical Tag | Technical SEO |
| 7 | robotsMeta | Robots Meta Tag | Technical SEO |
| 8 | robotsTxt | robots.txt | Technical SEO |
| 9 | sitemapAnalysis | XML Sitemap | Technical SEO |
| 10 | noindexCheck | Noindex Check | Technical SEO |
| 11 | doctypeCharset | Doctype & Charset | Technical SEO |
| 12 | httpHeaders | HTTP Headers | Technical SEO |
| 13 | htmlLang | HTML Lang Attribute | Technical SEO |
| 14 | hreflang | Hreflang Tags | Technical SEO |
| 15 | performanceHints | Performance Hints | Usability & Performance |
| 16 | viewportTag | Viewport Tag | Usability & Performance |
| 17 | imageAnalysis | Image Analysis | Usability & Performance |
| 18 | favicon | Favicon | Usability & Performance |
| 19 | socialTags | Social / Open Graph Tags | Discovery & Social |
| 20 | schemaOrg | Schema.org Structured Data | Discovery & Social |
| 21 | linkAnalysis | Link Analysis | Discovery & Social |
| 22 | googleMapEmbed | Google Map Embed | Discovery & Social |
| 23 | wikipediaLinkCheck | Wikipedia Links | Discovery & Social |
| 24 | wpDetection | WordPress Detection | WordPress Security |
| 25 | wpPlugins | WordPress Plugins | WordPress Security |
| 26 | wpTheme | WordPress Theme | WordPress Security |

### New in Phase 1 (5-8 modules)

| # | Key | Module | Category |
|---|---|---|---|
| 27 | eatAuthor | Author Detection | E-E-A-T |
| 28 | eatTrustPages | Trust Pages (About/Contact) | E-E-A-T |
| 29 | eatPrivacyTerms | Privacy & Terms Detection | E-E-A-T |
| 30 | eatBusinessSchema | Business Schema Validation | E-E-A-T |
| 31 | contentReadability | Content Readability | Content Analysis |
| 32 | contentKeywords | Keyword Presence Check | Content Analysis |
| 33 | contentDuplicate | Duplicate Content Signals | Content Analysis |

### New in Phase 2 (6 modules)

| # | Key | Module | Category |
|---|---|---|---|
| 34 | schemaValidation | Schema Deep Validation | Structured Data |
| 35 | brokenLinks | Broken Link Detection | Link Quality |
| 36 | anchorText | Anchor Text Analysis | Link Quality |
| 37 | relAttributes | Rel Attribute Audit | Link Quality |
| 38 | imageOptimization | Image Optimization | Usability & Performance |
| 39 | coreWebVitals | Core Web Vitals (PSI API) | Usability & Performance |

### New in Phase 3 (AI-powered, not traditional modules)

| # | Key | Module | Category |
|---|---|---|---|
| 40 | aiContentQuality | Content Quality Evaluation | AI Analysis |
| 41 | aiEeatScoring | E-E-A-T Deep Scoring | AI Analysis |
| 42 | aiSearchIntent | Search Intent Matching | AI Analysis |
| 43 | aiContentGaps | Content Gap Analysis | AI Analysis |
| 44 | competitorCompare | Competitor Comparison | Competitive Intelligence |

**Total: 44 modules at full buildout (Phase 1-3)**

---

## Report Categories (v2)

| Category | Phase 1 Modules | Phase 2 Additions | Phase 3 Additions |
|---|---|---|---|
| **On-Page SEO** | titleTag, metaDescription, h1Tag, h2h6Tags, serpPreview | — | — |
| **Content Analysis** | contentReadability, contentKeywords, contentDuplicate | — | aiContentQuality, aiContentGaps |
| **E-E-A-T Signals** | eatAuthor, eatTrustPages, eatPrivacyTerms, eatBusinessSchema | — | aiEeatScoring |
| **Technical SEO** | canonicalTag, robotsMeta, robotsTxt, sitemapAnalysis, noindexCheck, doctypeCharset, httpHeaders, htmlLang, hreflang | — | — |
| **Structured Data** | schemaOrg | schemaValidation | — |
| **Usability & Performance** | performanceHints, viewportTag, imageAnalysis, favicon | imageOptimization, coreWebVitals | — |
| **Link Quality** | linkAnalysis | brokenLinks, anchorText, relAttributes | — |
| **Discovery & Social** | socialTags, googleMapEmbed, wikipediaLinkCheck | — | — |
| **WordPress Security** | wpDetection, wpPlugins, wpTheme | — | — |
| **Competitive Intelligence** | — | — | competitorCompare, aiSearchIntent |

---

## AI Integration Architecture

### Gateway Pattern

```
AiGateway (interface)
    ├── analyze(prompt, context): AiResponse
    │
    ├── OpenAiGateway (implements AiGateway)
    ├── ClaudeGateway (implements AiGateway)
    └── ... future providers

Configuration via .env:
    AI_PROVIDER=openai
    AI_MODEL=gpt-4
    AI_API_KEY=sk-...
```

### AI Tiers

| Tier | What It Does | Cost Estimate | Plan Availability |
|---|---|---|---|
| **Tier 1** | Executive summary, plain-English module explanations | ~$0.005/scan | Free (limited), Pro, Agency |
| **Tier 2** | Fix-it suggestions (title, meta, H1, alt text) | ~$0.01-0.02/scan | Pro, Agency |
| **Tier 3** | Deep content evaluation, E-E-A-T scoring, intent matching, gap analysis | ~$0.03-0.05/scan | Agency only |

---

## Super Admin Panel

### Dashboard Metrics

- MRR (Monthly Recurring Revenue)
- Total active subscriptions by plan
- New signups this week/month
- Trial-to-paid conversion rate
- Churn rate
- Total scans today/week/month
- AI API costs this billing period
- Most active organizations

### Management Sections

| Section | Capabilities |
|---|---|
| **Plans** | CRUD plans, adjust all limits + feature flags, changes apply instantly |
| **Users** | Search, view, suspend, delete, override verification, reset password |
| **Organizations** | View, change plan, adjust per-org limits, view usage |
| **Coupons** | Create/edit codes, discount type/value, limits, expiry, redemption tracking |
| **Trials** | Grant trial of any plan to any org, extend existing trials |
| **Impersonation** | Log in as any user for debugging (fully audit-logged) |
| **Audit Log** | Every admin action: who, what, when, old value, new value |
| **Settings** | Default trial length, registration open/closed, AI provider config, maintenance mode |

### Security

- Separate auth guard with mandatory 2FA
- Optional IP whitelist
- Full audit log (non-negotiable)
- Impersonation logging (actions tagged as impersonated, not as user)

---

## Billing System (Stripe + Laravel Cashier)

### Payment Flow

```
User signs up → Free tier (no card)
    │
    ├── Clicks "Upgrade to Pro"
    │       → Stripe Checkout (hosted, secure)
    │       → Card processed → webhook → subscription active
    │
    ├── Monthly: Stripe auto-charges → webhook → confirmed
    │
    ├── Payment fails: Stripe retries (smart logic)
    │       → Dunning emails sent by Stripe
    │       → Final failure webhook → grace period → downgrade to Free
    │
    └── User cancels: access continues until period end → reverts to Free
```

### Subscription on Organization (not User)

- Owner pays, all team members get access
- Employee leaves → loses access, subscription stays with org
- Owner transfers → new owner inherits billing

### Coupon System

- Percentage off, fixed amount off, or free months
- Per-plan restrictions (e.g., only applies to Agency)
- Usage limits (total + per-user)
- Expiration dates
- Tracked: times redeemed, revenue impact

---

## Email System

### Transactional Emails (15+ templates)

| Trigger | Email |
|---|---|
| Signup | Welcome + getting started guide |
| Registration | Email verification |
| Forgot password | Password reset link |
| Team invite | "[Name] invited you to join [Org]" |
| Team join | Member joined notification to owner |
| Scan complete | Results ready (if user navigated away) |
| Recurring scan | Weekly/monthly summary ("3 improved, 1 declined") |
| Trial ending | Warning at 3 days and 1 day before expiry |
| Trial expired | Upgrade CTA |
| Payment failed | Update payment method |
| Payment received | Receipt/confirmation |
| Plan changed | Confirmation of upgrade/downgrade |
| Account cancelled | Win-back offer |
| Vulnerability found | Critical security alert for WordPress sites |

---

## Security Requirements (All Phases)

| Concern | Implementation |
|---|---|
| **Authentication** | Laravel Sanctum, bcrypt passwords, email verification |
| **CSRF** | Laravel middleware (automatic on all forms) |
| **Rate Limiting** | Per IP + per session, configurable per route |
| **Input Validation** | Form Request classes, URL validation + sanitization |
| **SSRF Protection** | Block internal IPs, localhost, cloud metadata endpoints on outbound requests |
| **Output Encoding** | Consistent htmlspecialchars/Blade escaping across all output |
| **SQL Injection** | Eloquent prepared statements (default) |
| **API Keys** | `.env` file, never in source code |
| **Payment Security** | Stripe Checkout (hosted) — PCI SAQ-A, no card data on our servers |
| **Webhook Verification** | Stripe signing secret verification (Cashier automatic) |
| **Admin Panel** | Separate guard, mandatory 2FA, optional IP whitelist, audit log |
| **Scan Abuse** | Rate limiting, ToS restriction, logging, free tier limits |

---

## Legal Requirements

- Terms of Service
- Privacy Policy (GDPR + CCPA compliant)
- Cookie Policy + consent banner
- Acceptable Use Policy
- Data Processing Agreement (Enterprise, on request)

---

## Competitive Positioning vs WooRank

| Feature | WooRank | Hello SEO Analyzer v2 |
|---|---|---|
| **Multi-user** | Enterprise only ($$$) | Pro tier ($49/mo, 3 users) |
| **Free tier** | None (14-day trial only) | Yes, forever-free with limits |
| **E-E-A-T analysis** | Minimal | Deep analysis with AI evaluation |
| **AI recommendations** | None | Per-module fix-it suggestions |
| **AI executive summary** | None | AI-generated plain-English overview |
| **Client viewer role** | None | Viewer role for agency clients |
| **White-label** | $200/mo minimum | $149/mo (Agency tier) |
| **WordPress security** | None | Plugin/theme vulnerability scanning |
| **Content quality** | Basic | AI-powered content evaluation |
| **Price (agency use)** | $200/mo (1 user) | $149/mo (10 users) |

---

## UI/Frontend Design System

### Design Philosophy

**Inspiration:** Stripe Dashboard, Linear App — clean, minimal, professional. Whitespace is a feature, not wasted space. Information-dense without being overwhelming. Every element earns its place.

### Color Palette

| Role | Value | Usage |
|---|---|---|
| **Background** | `#FAFAFA` | Page background, breathing room |
| **Surface** | `#FFFFFF` | Cards, panels, content areas |
| **Primary text** | `#111827` | Headlines, important content |
| **Secondary text** | `#6B7280` | Labels, descriptions, metadata |
| **Tertiary text** | `#9CA3AF` | Placeholders, disabled states |
| **Border** | `#E5E7EB` | Card borders, dividers — subtle, not loud |
| **Accent/Primary** | TBD (blue, teal, or indigo) | Buttons, links, active states, score highlights |
| **Accent hover** | Darker shade of accent | Button hover, link hover |
| **Success** | `#10B981` | Passed checks, good scores (80+) |
| **Warning** | `#F59E0B` | Warnings, medium issues (40-79) |
| **Danger** | `#EF4444` | Critical issues, errors, bad scores (<40) |
| **Info** | `#6366F1` | Informational badges, neutral states |

**Rules:** No gradients. Flat design. One subtle shadow level (`shadow-sm`) for elevated cards. No heavy border-radius on everything — use `rounded-lg` on cards, `rounded-md` on buttons, `rounded-full` on avatars/badges only.

### Typography

- **Font:** System font stack — `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
- **No custom font loading** — fastest possible render, native OS feel
- **Scale:** Tailwind defaults — `text-sm` for body, `text-lg`/`text-xl` for headings, `text-3xl`+ for score numbers
- **Weight:** `font-normal` for body, `font-medium` for labels, `font-semibold` for headings
- **Monospace:** `font-mono` for technical values (URLs, HTTP headers, schema types)

### Layout Shell

```
┌──────────────────────────────────────────────────────────────┐
│  [Logo]   Dashboard  Projects  Reports  Settings    [Avatar] │
├──────────┬───────────────────────────────────────────────────┤
│          │                                                   │
│ Optional │   Page content                                    │
│ sidebar  │   Max-width container (~1200px)                   │
│ for sub- │   Generous padding (px-6 py-8)                    │
│ navigation│                                                  │
│          │                                                   │
├──────────┴───────────────────────────────────────────────────┤
│  Minimal footer (legal links, copyright)                     │
└──────────────────────────────────────────────────────────────┘
```

**Navigation:** Top horizontal nav bar for primary sections. Optional left sidebar for sub-navigation within sections (e.g., Settings → Profile / Billing / Team). Sidebar collapses on mobile to hamburger menu.

**Container:** Centered max-width (~1200px) with `px-6` horizontal padding. Content never stretches full-width on large screens.

**Responsive breakpoints:** Mobile-first. Tailwind defaults (`sm:640px`, `md:768px`, `lg:1024px`, `xl:1280px`).

### Key Screens

#### Dashboard (Home)

The first screen after login. Answers: "How are my sites doing?"

```
┌─────────────────────────────────────────────────────────┐
│  Good morning, Steven.                                  │
│                                                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐│
│  │ 5        │  │ 47       │  │ 72       │  │ 3       ││
│  │ Projects │  │ Scans    │  │ Avg Score│  │ Alerts  ││
│  │ active   │  │ this mo  │  │          │  │         ││
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘│
│                                                         │
│  Your Projects                            [+ New Project]│
│  ┌─────────────────────────────────────────────────────┐│
│  │ ● example.com        Score: 78  ▲+3  Last: 2h ago →││
│  ├─────────────────────────────────────────────────────┤│
│  │ ● clientsite.io      Score: 54  ▼-2  Last: 3d ago →││
│  ├─────────────────────────────────────────────────────┤│
│  │ ● myblog.dev         Score: 91  ━ 0  Last: 1w ago →││
│  └─────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────┘
```

- **4 stat cards** at top — large numbers, minimal labels, no icons
- **Project list** — clean table rows, score with colored number + delta arrow
- **No charts on dashboard** — charts live inside project detail pages
- **Empty state** (new user): "Let's scan your first website" with prominent CTA

#### Scan Results Page

The money page. Information-dense but scannable.

```
┌─────────────────────────────────────────────────────────┐
│  ← Back to Project                    [⟳ Rescan] [📄 PDF]│
│                                                         │
│  example.com                                            │
│  ┌─────────────────────────────────────────────────────┐│
│  │    72          8 Passed  5 Warnings  3 Issues       ││
│  │  Overall       ●●●●●●●●  ●●●●●      ●●●           ││
│  └─────────────────────────────────────────────────────┘│
│                                                         │
│  Category Overview                                      │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌─────────┐│
│  │ On-Page   │ │ E-E-A-T   │ │ Technical │ │ Content ││
│  │ 4/5 pass  │ │ 2/4 pass  │ │ 8/9 pass  │ │ 2/3 pass││
│  └───────────┘ └───────────┘ └───────────┘ └─────────┘│
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌─────────┐│
│  │ Struct.   │ │ Usability │ │ Links     │ │ Discov. ││
│  │ 1/2 pass  │ │ 3/4 pass  │ │ 1/3 pass  │ │ 3/3 pass││
│  └───────────┘ └───────────┘ └───────────┘ └─────────┘│
│                                                         │
│  ⚡ Quick Wins (AI-Generated)                           │
│  ┌─────────────────────────────────────────────────────┐│
│  │ 1. Add a meta description (+8 pts)                  ││
│  │ 2. Add author bios to blog posts (+5 pts)           ││
│  │ 3. Fix 2 broken outbound links (+3 pts)             ││
│  └─────────────────────────────────────────────────────┘│
│                                                         │
│  Detailed Findings                                      │
│  ┌─────────────────────────────────────────────────────┐│
│  │ ON-PAGE SEO                                         ││
│  │  ✓ Title Tag          "Best Running Sh..."       →  ││
│  │  ✓ Meta Description   "Shop the latest..."       →  ││
│  │  ✓ H1 Heading         "Running Shoes"            →  ││
│  │  ✗ SERP Preview       Title truncated at 23ch    →  ││
│  ├─────────────────────────────────────────────────────┤│
│  │ E-E-A-T SIGNALS                                     ││
│  │  ✓ Trust Pages        About + Contact found      →  ││
│  │  ✗ Author Info        No author detected         →  ││
│  │  ✗ Business Schema    Missing Organization       →  ││
│  └─────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────┘
```

- **Score hero** at top — large colored number, pass/warning/issue counters
- **Category grid** — 8 clickable boxes, pass ratio per category. Click jumps to section
- **Quick Wins** — AI-generated, priority-ranked, estimated point impact
- **Detailed findings** — grouped by category, one row per module, expand arrow for details
- **Progressive loading** — results appear as each module completes during scan

#### Module Detail (Inline Expand)

Clicking a module row expands it inline with smooth animation:

```
│  ✗ Author Info      No author detected              ▼  │
│  ┌───────────────────────────────────────────────────┐  │
│  │  Findings:                                        │  │
│  │  No author name, bio, or author page link was     │  │
│  │  detected on this page.                           │  │
│  │                                                   │  │
│  │  Why it matters:                                  │  │
│  │  Google's E-E-A-T guidelines prioritize content   │  │
│  │  from identifiable experts. Pages without clear   │  │
│  │  authorship may rank lower for YMYL topics.       │  │
│  │                                                   │  │
│  │  ┌─────────────────────────────────────────────┐  │  │
│  │  │ 🤖 AI Recommendation                       │  │  │
│  │  │                                             │  │  │
│  │  │ Add an author byline with a link to an      │  │  │
│  │  │ author bio page. Include credentials,       │  │  │
│  │  │ relevant experience, and links to published  │  │  │
│  │  │ work or social profiles.                    │  │  │
│  │  └─────────────────────────────────────────────┘  │  │
│  │                                                   │  │
│  │  Recommendations:                                 │  │
│  │  • Add visible author name near content           │  │
│  │  • Create dedicated author bio pages              │  │
│  │  • Include author credentials and expertise       │  │
│  └───────────────────────────────────────────────────┘  │
```

- **Findings** — what was detected (factual)
- **Why it matters** — plain-English explanation for non-technical users
- **AI Recommendation** — visually distinct box with subtle accent background tint
- **Recommendations** — bullet list of actionable steps
- **No page reload** — Alpine.js handles expand/collapse with `x-show` + transition

#### SERP Preview (Visual)

Rendered as an actual Google-style search result snippet within the scan results:

```
│  ┌─────────────────────────────────────────────────┐  │
│  │  example.com › running-shoes                    │  │
│  │  Best Running Shoes for 2026 | Example Store    │  │
│  │  Shop the latest running shoes from top brands. │  │
│  │  Free shipping on orders over $50. Expert...    │  │
│  └─────────────────────────────────────────────────┘  │
```

Business owners instantly understand how their site looks on Google. More impactful than any data table.

#### Project Detail Page

Scan history and score trends for a single project:

```
┌─────────────────────────────────────────────────────────┐
│  example.com                          [⟳ Scan Now] [⚙]  │
│  Last scan: Feb 6, 2026 · Score: 78                     │
│                                                         │
│  Score History                                          │
│  ┌─────────────────────────────────────────────────────┐│
│  │  90 ┤                                               ││
│  │  80 ┤              ●───────●───●                    ││
│  │  70 ┤        ●────●                                 ││
│  │  60 ┤  ●────●                                       ││
│  │  50 ┤                                               ││
│  │     └──Jan──────Feb──────Mar──────Apr──────────     ││
│  └─────────────────────────────────────────────────────┘│
│                                                         │
│  Scan History                                           │
│  ┌─────────────────────────────────────────────────────┐│
│  │  Feb 6, 2026   Score: 78  ▲+3   12.4s   [View →]   ││
│  │  Jan 30, 2026  Score: 75  ▲+5   11.8s   [View →]   ││
│  │  Jan 23, 2026  Score: 70  ▼-2   13.1s   [View →]   ││
│  │  Jan 16, 2026  Score: 72  ━ 0   12.7s   [View →]   ││
│  └─────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────┘
```

- **Score trend chart** — line chart via Chart.js, colored by grade zones
- **Scan history table** — date, score, delta, duration, link to full results
- **Settings gear** — scan schedule, project name, delete project

#### Settings Pages

Clean form layouts following Stripe's settings pattern:

```
Settings
├── Profile         Name, email, avatar, password change
├── Billing         Current plan, payment method, invoices, upgrade/downgrade
├── Team            Member list, invite, roles, remove (Phase 2)
├── Organization    Org name, logo upload (for white-label), slug
└── Notifications   Email preferences, alert toggles (Phase 2)
```

Each settings page: left label column, right input column. Save button at bottom. No clutter.

#### Pricing Page (Public)

```
┌─────────────────────────────────────────────────────────┐
│              Pick the plan that fits your needs          │
│                                                         │
│  ┌─────────────┐  ┌───────────────┐  ┌───────────────┐ │
│  │   Free      │  │   Pro         │  │   Agency      │ │
│  │   $0/mo     │  │   $49/mo      │  │   $149/mo     │ │
│  │             │  │               │  │               │ │
│  │ 1 user      │  │ 3 users       │  │ 10 users      │ │
│  │ 1 project   │  │ 5 projects    │  │ 25 projects   │ │
│  │ 10 scans/mo │  │ 100 scans/mo  │  │ 500 scans/mo  │ │
│  │ Basic AI    │  │ Full AI       │  │ Full AI       │ │
│  │             │  │               │  │ White-label   │ │
│  │             │  │               │  │ API access    │ │
│  │             │  │               │  │               │ │
│  │ [Get Started]│ │ [Start Trial] │  │ [Start Trial] │ │
│  └─────────────┘  └───────────────┘  └───────────────┘ │
│                                                         │
│  Need more? Enterprise →                                │
│                                                         │
│  [Monthly / Annual toggle — save 20%]                   │
└─────────────────────────────────────────────────────────┘
```

- **3 cards side by side** — no feature matrix with 50 rows
- **5-6 key differences** highlighted per tier
- **Monthly/annual toggle** at top
- **Enterprise link** to contact form, not a 4th card
- **Pro card slightly elevated** or outlined (recommended plan visual)

### Conversion Patterns

**Gated feature visibility:** Free users see AI recommendation boxes grayed out with a lock icon and "Upgrade to Pro to unlock AI suggestions." Show the value, gate the access.

**Upgrade prompts on limit hits:** "You've used 10 of 10 scans this month. Upgrade to Pro for 100 scans/month." Contextual, not naggy. Appears when they actually hit the wall.

**Score as the hero:** The overall score number appears prominently on dashboard cards, scan results, PDF reports, and email summaries. It's the single number that gets shared: "We scored a 72." "After your work we went to 89."

### Empty States

Every page that can be empty gets a purposeful empty state:

| Page | Empty State Message | CTA |
|---|---|---|
| Dashboard (no projects) | "Let's scan your first website" | [Add Your Website] |
| Project (no scans) | "Ready to analyze? Run your first scan." | [Scan Now] |
| Team (solo user) | "Invite your team to collaborate" | [Invite Member] |
| Scan History (no history) | "No previous scans yet" | [Run a Scan] |
| Notifications (empty) | "You're all caught up" | — |

### Component Library

Reusable UI components built with Tailwind + Alpine:

| Component | Usage |
|---|---|
| **StatCard** | Dashboard metric boxes (number + label) |
| **ProjectRow** | Project list item (name, score, delta, last scan) |
| **ScoreCircle** | Large score number with color grade ring |
| **CategoryBox** | Category summary tile (name, pass ratio) |
| **FindingRow** | Module result row (status icon, name, summary, expand arrow) |
| **FindingDetail** | Expanded module detail (findings, explanation, AI recommendation) |
| **QuickWinCard** | Priority action item (numbered, estimated impact) |
| **StatusBadge** | Colored pill badge (passed, warning, issue, info) |
| **EmptyState** | Illustration + message + CTA button |
| **Modal** | Confirmation dialogs, invite forms (Alpine.js `x-data`) |
| **Dropdown** | Nav menus, user menu, action menus |
| **DataTable** | Sortable, filterable table (scan history, team list) |
| **TrendChart** | Score-over-time line chart (Chart.js wrapper) |
| **SerpPreview** | Google SERP result visual mockup |

### Pages (Phase 1)

| Page | Route | Description |
|---|---|---|
| Landing / Pricing | `/` | Public marketing + pricing cards |
| Login | `/login` | Email/password auth |
| Register | `/register` | Signup + org creation |
| Forgot Password | `/forgot-password` | Password reset request |
| Dashboard | `/dashboard` | Project list + stat cards |
| New Project | `/projects/create` | URL input + first scan trigger |
| Project Detail | `/projects/{id}` | Scan history + trend chart |
| Scan Results | `/scans/{id}` | Score + categories + findings + AI |
| Settings: Profile | `/settings/profile` | Name, email, password |
| Settings: Billing | `/settings/billing` | Plan, payment method, invoices |
| Settings: Organization | `/settings/organization` | Org name, logo |
| PDF Report | `/scans/{id}/report` | Triggers PDF download |

**12 pages total for Phase 1.** Focused and lean.

### Design Principles (Summary)

1. **Whitespace is a feature** — never cram. Let content breathe
2. **Score is the hero** — large, colored, prominent everywhere
3. **Quick Wins above detail** — lead with actionable priorities
4. **Progressive disclosure** — summary first, expand for details
5. **Show locked features** — free users see grayed-out Pro features (conversion driver)
6. **Empty states guide action** — every empty page tells you what to do next
7. **SERP preview is visual** — render it like Google, not like a data table
8. **Consistency** — same component patterns across every page
9. **Responsive by default** — mobile-first Tailwind, works on all devices
10. **Fast** — no custom fonts, no heavy JS frameworks, server-rendered pages

---

## Phase Summary

| Phase | Name | Core Value | Plans | Key Deliverables |
|---|---|---|---|---|
| **1** | MVP | Sign up, scan, report | Free + Pro | Laravel foundation, 48 modules, AI Tier 1+2, billing, admin panel, bot protection, homepage-first scanning |
| **Final** | Ship It | Teams, competitors, polish | + Agency | Competitor analysis, team system, white-label PDF, broken links + schema validation modules, transactional emails |

> **Phases 3 & 4 from original roadmap removed.** Features like public API, LeadGen widget, SSO/SAML, third-party integrations, and full site crawler are deferred to post-launch — build when demand proves it.

---

## Known Decisions

1. **Framework:** Laravel 12.x
2. **Database:** SQLite (development) → MySQL (production)
3. **Payments:** Stripe via Laravel Cashier
4. **Admin Panel:** Laravel Filament v5.2 (resolved — PHP 8.5 compatibility fixed, 6 resources + 4 dashboard widgets + audit log)
5. **AI Provider:** Model-agnostic gateway — OpenAI (GPT-4o), Anthropic (Claude), Google (Gemini) all implemented
6. **Hosting:** Laravel Forge + DigitalOcean/Hetzner (planned)
7. **Frontend:** Tailwind CSS v4 + Alpine.js + Laravel Blade (no SPA framework)
8. **Design Language:** Stripe/Linear-inspired — `#FAFAFA` bg, `#FFFFFF` surface, `#4F46E5` accent
9. **Charts:** Chart.js for score trend visualizations
10. **Build Tool:** Vite (Laravel default bundler)
11. **Mini-crawl in Phase 1:** Yes — homepage + trust pages (up to 6 extra HTTP requests)
12. **Subscription model:** Billed to Organization, not User
13. **Free tier:** Yes, forever-free with 10 scans/month (no card required)
14. **Auth:** Laravel Breeze (Blade) + Sanctum for API tokens
15. **Scoring:** Research-backed weighted scoring — 6 tiers based on Google API leak, Lighthouse, First Page Sage, Semrush, Backlinko studies

---

## Open Questions

1. ~~Exact pricing for Pro and Agency~~ **RESOLVED:** Pro $49/mo, Agency $149/mo
2. Domain name and branding for v2
3. Whether to keep "Hello SEO Analyzer" name or rebrand
4. ~~Specific AI model selection~~ **RESOLVED:** OpenAI GPT-4o primary; Anthropic Claude and Google Gemini also supported via gateway pattern
5. Target launch date for Phase 1
6. ~~Solo developer or team for build?~~ **RESOLVED:** Solo developer with AI assistant
