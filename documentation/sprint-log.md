# Sprint Log — Hello SEO Analyzer v2

> Living document tracking sprint progress across all phases.
> Updated after each sprint completion.

---

## Phase 1 — MVP: "One User, One Scan, One Report"

**Goal:** A working SaaS product — sign up, scan a website, get a professional report.
**Revenue target:** Free + Pro ($49/mo) plans live, accepting payments.

### Sprint 1: Foundation
**Status:** COMPLETE
**Date completed:** 2026-02-07

**Delivered:**
- Laravel 12.50.0 project scaffolded on `v2-rebuild` branch
- 15 database migrations (users, orgs, org_user, plans, projects, scans, scan_module_results, coupons, subscription_usage, audit_logs, notifications, cache, jobs, sessions, personal_access_tokens)
- 5 PHP enums (OrganizationRole, ScanStatus, ModuleStatus, ScanSchedule, DiscountType)
- 9 Eloquent models with relationships and casts (User, Organization, Plan, Project, Scan, ScanModuleResult, Coupon, SubscriptionUsage, AuditLog)
- Auth system via Breeze (Blade) — registration, login, logout, password reset, email verification
- Sanctum API token support
- Auto org creation listener (CreateOrganizationForNewUser)
- Database seeders — Free/Pro/Agency plans + super admin (somdow@gmail.com) + free test user (freeaccount@gmail.com)
- Blade layout shell — Stripe/Linear-inspired design with Tailwind v4 theme tokens
- Web + API route structure with placeholder groups
- Vite build pipeline — Tailwind v4 + Alpine.js

**Deferred:**
- Filament admin panel — PHP 8.5 not supported by openspout dependency. Revisit when ecosystem catches up or move to Sprint 6.

**Tech notes:**
- Using SQLite temporarily (switching to MySQL when Laragon is fully configured)
- Tailwind v4 requires `@import "tailwindcss"` + `@theme` block (no tailwind.config.js)
- Breeze install overwrites CSS with v3 directives — must restore v4 setup after install
- 25 tests passing

---

### Sprint 2: Scan Engine Port
**Status:** COMPLETE
**Date completed:** 2026-02-07

**Delivered:**

*Architecture (4 files):*
- `AnalyzerInterface` contract — `moduleKey()`, `label()`, `category()`, `weight()`, `analyze(ScanContext): AnalysisResult`
- 3 readonly DTOs — `ScanContext` (shared page data), `AnalysisResult` (module output), `FetchResult` (HTTP response)

*Infrastructure Services (6 files):*
- `HttpFetcher` — Laravel HTTP client wrapper with `fetchPage()` (15s) + `fetchResource()` (5s)
- `HtmlParser` — DOMDocument + DOMXPath loader, extracts all shared metadata (title, meta, headings, viewport, robots, canonical, lang)
- `ScanOrchestrator` — phased execution: fetch URL, parse HTML, run analyzers in dependency order, store results, calculate score
- `ScoreCalculator` — weighted scoring: ok=1.0, warning=0.5, bad=0.0, info=excluded, result 0-100
- `ModuleRegistry` — holds all 26 analyzer instances, provides phase-grouped access and label maps
- `WordPressApiClient` — WordPress.org Plugin/Theme/Core APIs + WPVulnerability.net integration

*26 Analyzer Modules (20 SEO + 3 WordPress + 3 Utility):*
- SEO: TitleTag, MetaDescription, H1Tag, H2H6Tags, CanonicalTag, HtmlLang, RobotsMeta, NoindexCheck, LinkAnalysis, SocialTags, PerformanceHints, ImageAnalysis, DoctypeCharset, Favicon, HttpHeaders, Hreflang, RobotsTxt, SitemapAnalysis, ViewportTag, SchemaOrg
- WordPress: WpDetection, WpPlugins, WpTheme
- Utility: GoogleMapEmbed, WikipediaLinkCheck, SerpPreview

*Scan Execution (2 files):*
- `ProcessScanJob` — queued job with 3 retries, 120s timeout, calls ScanOrchestrator
- `config/scanning.php` — weights, thresholds, timeouts, user-agent (all config-driven)

*Controllers + Requests (4 files):*
- `ProjectController` — full CRUD (index, create, store, show, destroy) with org-scoped authorization
- `ScanController` — trigger scan (POST) + view results (GET) with org membership authorization
- `StoreProjectRequest` — validates name + URL with uniqueness per org
- `TriggerScanRequest` — validates scan limits and project ownership

*UI — Views + Components (8 files):*
- Projects: index (table with scores), create (form), show (scan history + danger zone)
- Scans: show (two-column layout — left sidebar nav + right content panel with module cards)
- Components: `module-card` (expandable findings/recommendations), `status-badge` (emerald/amber/red/blue dot badges), `primary-button` (supports `<a>` + `<button>`), `danger-button`

*UI Design — App-wide consistency pass:*
- Two-column scan results layout inspired by WP Engine dashboard
- Alpine.js sidebar navigation with `x-data`, `x-show`, `x-cloak`
- Score overview cards with SVG ring chart, pass/warning/fail counters
- Design system applied across all views: dashboard, projects (index/show), profile, scan results
- Stripe/Linear-inspired: `#FAFAFA` bg, `#FFFFFF` surface, `#4F46E5` accent, Tailwind native status colors
- `max-w-7xl mx-auto` centered content constraint on all pages
- DRY refactors: `scoreColorClass()`/`scoreStrokeClass()` on Scan model, reusable `<x-primary-button>` component

*Routes:*
- `GET /projects` — project list
- `GET /projects/create` — new project form
- `POST /projects` — store project
- `GET /projects/{project}` — project detail + scan history
- `DELETE /projects/{project}` — delete project
- `POST /projects/{project}/scan` — trigger scan
- `GET /scans/{scan}` — scan results

**Tech notes:**
- Phase execution order: (1) robotsTxt + wpDetection, (2) sitemapAnalysis, (3) DOM-only SEO analyzers, (4) WordPress modules (if WP detected), (5) utility analyzers
- Each analyzer wrapped in try-catch — one broken module never kills the scan
- ScanContext is readonly — analyzers cannot mutate shared state; rebuilt between phases
- WP modules only scored when `isWordPress = true`
- 25 tests passing, Vite build clean
- ~50 new files total

---

### Sprint 3: New Modules
**Status:** COMPLETE
**Date completed:** 2026-02-07

**Delivered:**

*E-E-A-T Analyzers (4 modules):*
- `EatAuthorAnalyzer` — author name, bio, author page link, credentials detection
- `EatTrustPagesAnalyzer` — About/Contact page existence and content quality via mini-crawl (fetches up to 6 trust pages from nav/footer links)
- `EatPrivacyTermsAnalyzer` — Privacy Policy, Terms of Service, Cookie Policy detection
- `EatBusinessSchemaAnalyzer` — Organization/LocalBusiness JSON-LD with address, phone, founding date validation

*Content Analysis Analyzers (3 modules):*
- `ContentReadabilityAnalyzer` — word count, Flesch-Kincaid readability, thin content detection, sentence length analysis
- `ContentKeywordsAnalyzer` — multi-keyword presence in title, H1, first paragraph, URL, meta description
- `ContentDuplicateAnalyzer` — meta description matching first paragraph, identical title/H1, duplicate signal detection

*Additional SEO Analyzers (4 modules):*
- `UrlStructureAnalyzer` — URL length, path depth, special characters, config-driven thresholds
- `BreadcrumbAnalyzer` — breadcrumb nav detection, BreadcrumbList schema validation
- `SemanticHtmlAnalyzer` — semantic element usage (nav, main, article, section, aside, footer, header)
- `RedirectChainAnalyzer` — redirect chain length, redirect loop detection, HTTP→HTTPS redirect checks

*Shared Infrastructure:*
- `ExtractsPageContent` trait — reusable content extraction logic shared across content analyzers

**Tech notes:**
- 11 new analyzer modules (26 → 37 total)
- Mini-crawl for trust pages: fetches homepage nav/footer links, follows up to 6 internal pages matching About/Contact/Privacy/Terms patterns
- All new modules follow the same `AnalyzerInterface` contract and phased execution model
- 25 tests passing

---

### Sprint 4: AI Layer
**Status:** COMPLETE
**Date completed:** 2026-02-08

**Delivered:**

*AI Gateway Architecture (6 files):*
- `AiGatewayFactory` — provider-agnostic factory, creates gateway from config or per-user settings
- `OpenAiGateway` — OpenAI API integration (GPT-4o default)
- `AnthropicGateway` — Anthropic Claude API integration
- `GeminiGateway` — Google Gemini API integration
- `HandlesApiErrors` trait — shared error handling across all gateways
- `AiKeyResolver` — resolves API keys from user settings or `.env` fallback

*Prompt Architecture (11 files):*
- `ExecutiveSummaryPrompt` — feeds all module results, generates plain-English overview with multi-keyword SEO guardrails
- `ModuleOptimizationPrompt` — base class for module-specific fix-it prompts
- `ModulePromptFactory` — routes module keys to specific prompt classes
- `BuildsPageContext` trait — enriches prompts with page title, URL, meta description, heading structure
- `FormatsScanData` trait — formats scan results for AI consumption
- 6 module-specific prompts: `TitleTagPrompt`, `MetaDescriptionPrompt`, `H1TagPrompt`, `ContentKeywordsPrompt`, `ContentReadabilityPrompt`, `ImageAnalysisPrompt`

*AI Optimization (2 files):*
- `OnDemandAiOptimizer` — on-demand AI optimization for individual module results
- `AiOptimizationController` — handles AI optimization requests from scan results UI

*User AI Settings (2 files):*
- `AiSettingsController` — settings page for users to configure their own AI provider/key
- AI settings Blade view — provider selection, API key input, model override

*Pro Feature Lock:*
- AI features gated behind plan tiers — Free users see locked state with upgrade prompts
- `ai_eligible` computed per scan based on organization plan
- Blade components show lock icon + "Upgrade to Pro" on Free tier
- Executive summary and fix-it buttons conditionally rendered

*WordPress Detail Display (5 Blade components):*
- `vulnerability-row` — shared CVE row with CVSS color coding and fix status
- `version-status-badge` — pill badge for plugin/theme version status (current/outdated/unknown/premium)
- `plugin-detail-table` — expandable plugin table with version comparison, sorted by severity
- `theme-detail-card` — theme info card with optional vulnerability details
- `core-vulnerability-table` — WordPress core vulnerability detail table

*Multi-Keyword Support:*
- `target_keywords` stored as JSON array on projects
- Comma-separated keyword input in project forms
- All content/keyword analyzers check against full keyword array
- AI prompts receive keyword context for targeted recommendations

*Research-Backed Scoring Weights:*
- Module weights revised based on Google API leak (14K+ attributes), Lighthouse v12, First Page Sage 15-year study, Semrush 2024 (300K SERPs), Backlinko (11.8M results)
- 6-tier weight system: Critical Indexability, Content & Relevance, Technical Foundation, E-E-A-T & Trust, Supporting Factors, WordPress-Specific
- Info-only modules (weight 0): socialTags, favicon, googleMapEmbed, wikipediaLinkCheck, wpDetection, serpPreview

*Code Quality Audit Fixes:*
- `ProjectPolicy` — centralized authorization replacing 4 duplicate auth checks across controllers
- `User::belongsToOrganization()` — single source of truth for org membership
- `DashboardService` — extracted DB queries from DashboardController
- `ProjectService` — extracted parseKeywords/normalizeUrl from ProjectController
- `ProjectFormRequest` — abstract base class deduplicating Store/Update validation rules
- `ModuleRegistry::resolveCategory()` — dynamic category resolution replacing 43-line hardcoded map
- All controllers use `Gate::authorize()` + explicit `array()` syntax

**Routes added:**
- `GET /settings/ai` — AI settings page
- `POST /settings/ai` — save AI settings
- `POST /scans/{scan}/ai-optimize/{moduleKey}` — trigger AI optimization for a module

**Tech notes:**
- 3 AI providers implemented (OpenAI, Anthropic, Gemini) — switchable via config or per-user settings
- Multimodal image optimization: sends page screenshots to AI for visual analysis
- AI prompts include page context (title, URL, headings, meta) for targeted recommendations
- SEO guardrails: AI never suggests keyword stuffing, cloaking, or manipulative tactics
- WordPress detail components wired into module-card via conditional includes for wpPlugins/wpTheme/wpDetection
- WpThemeAnalyzer updated to store vulnerability details as data findings
- 25 tests passing, Vite build clean

---

### Sprint 5: Billing
**Status:** COMPLETE
**Date completed:** 2026-02-08

**Delivered:**

*Stripe Integration (7 files):*
- Laravel Cashier installed with `Organization` as billable model (`Cashier::useCustomerModel`)
- 5 Cashier migrations: customer_columns, subscriptions, subscription_items, meter_id, meter_event_name
- `config/cashier.php` published and configured
- Stripe env vars added to `.env.example`

*BillingService (1 file):*
- `isStripeConfigured()` — gates all live Stripe calls; without keys, UI still works with graceful fallback
- `createCheckoutSession()` — builds Stripe Checkout URL with coupon support
- `swapSubscription()` — upgrade/downgrade with proration
- `cancelSubscription()` / `resumeSubscription()` — cancel at period end / resume during grace period
- `syncPlanFromStripe()` — maps Stripe price ID back to local `plan_id`
- `resolveCurrentUsage()` — get-or-create current billing period usage record
- `canCreateProject()` / `canTriggerScan()` — plan limit checks
- `downgradeToFree()` — resets org to Free plan
- `validateCoupon()` — validates coupon via existing Coupon model

*Controllers + Requests (5 files):*
- `BillingController` — index (portal), checkout, success, changePlan, cancel, resume, redirectToCustomerPortal, downloadInvoice
- `PricingController` — public pricing page (invokable, no auth required)
- `StripeWebhookController` — extends Cashier webhook handler; overrides subscription updated/deleted/payment failed
- `CreateCheckoutRequest` / `ChangePlanRequest` — validation for plan_id, billing_cycle, coupon_code

*Middleware (1 file):*
- `EnforcePlanLimits` — parameterized middleware (`enforce.plan:projects`, `enforce.plan:scans`)
- Applied to project creation and scan triggering routes

*Billing Views (7 files):*
- `billing/index.blade.php` — billing portal with 5 stacked card partials
- `billing/partials/current-plan.blade.php` — plan name, price, usage bars (projects + scans)
- `billing/partials/upgrade-options.blade.php` — available plans with monthly/annual toggle (Alpine.js), owner-only
- `billing/partials/payment-method.blade.php` — card brand/last4, update via Stripe Portal
- `billing/partials/invoices.blade.php` — invoice table with download links
- `billing/partials/cancel-subscription.blade.php` — danger zone with cancel/resume
- `billing/success.blade.php` — post-checkout confirmation

*Pricing Page (1 file):*
- `pricing.blade.php` — public 3-card layout (Free/Pro/Agency), monthly/annual toggle, "Most Popular" badge, enterprise CTA

*Organization Model Enhancements:*
- Added Cashier `Billable` trait
- `hasActiveSubscription()`, `billingCycle()`, `subscriptionOnGracePeriod()` helpers
- Removed `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` from `$fillable` (Cashier manages these)

*SubscriptionUsage Model Enhancements:*
- `scopeForCurrentPeriod()` — query scope for current billing period
- Static `resolveCurrentPeriod()` — firstOrCreate for current month

*Placeholder Link Wiring:*
- Sidebar billing link → `route('billing.index')` with active state
- Dashboard upgrade CTA → `route('pricing')`
- Upgrade modal → `route('pricing')`
- AI settings form → `route('pricing')`

*Layout Fix for Public Pages:*
- Wrapped sidebar, mobile overlay, and hamburger in `@auth`/`@endauth` guards
- Conditional left padding: `auth()->check() ? 'lg:pl-60' : ''`

*Scan Results Sidebar Fix:*
- Fixed "WordPress Security" → "WordPress" category mismatch (3 modules were invisible)
- Added "Local SEO" (2 modules) and "Utility" (1 module) to SEO Audit sidebar group
- All 37 modules now visible in scan results

*Code Quality Audit Fixes:*
- Extracted `ensureStripeConfigured()` private method (DRY — was repeated 3 times)
- Replaced all `$exception->getMessage()` user exposure with generic messages + `Log::error()` server-side
- Added `Log::warning()` for silent invoice retrieval failures

*Tests (4 files, 40 new tests):*
- `BillingPageTest` — 12 tests: portal access (owner/member/guest), plan details, upgrade options visibility, owner-only enforcement, Stripe-not-configured error, validation
- `PricingPageTest` — 7 tests: guest/auth access, all plans shown, annual pricing, features, enterprise CTA, "Most Popular" badge
- `PlanEnforcementTest` — 5 tests: project limits (free/pro), scan limits (exceeded/within)
- `BillingServiceTest` — 16 tests: isStripeConfigured, canCreateProject (free/pro limits), canTriggerScan, resolveCurrentUsage, downgradeToFree, org model helpers

**Routes added:**
- `GET /pricing` — public pricing page
- `GET /billing` — billing portal
- `POST /billing/checkout` — create Stripe Checkout session
- `GET /billing/success` — post-checkout success
- `POST /billing/change-plan` — swap plan
- `POST /billing/cancel` — cancel subscription
- `POST /billing/resume` — resume subscription
- `GET /billing/portal` — redirect to Stripe Customer Portal
- `GET /billing/invoices/{invoiceId}` — download invoice PDF
- `POST /stripe/webhook` — Stripe webhook handler (CSRF excluded)

**Tech notes:**
- Cashier's published migration defaults to `user_id` — must change to `organization_id` since Organization is the billable model
- `assertSeeText()` HTML-encodes the needle via `e()` — use `assertSee($value, escape: false)` for `&` characters
- `Queue::fake()` needed in scan tests to prevent ProcessScanJob from running synchronously
- 65 tests passing (144 assertions), Vite build clean
- ~39 files changed, 2656 insertions

---

### Sprint 6: Admin Panel
**Status:** COMPLETE
**Date completed:** 2026-02-09

**Delivered:**

*Panel Foundation (2 files):*
- Filament v5.2 + Livewire v4.1.3 installed (PHP 8.5 compatibility resolved)
- `AdminPanelProvider` — panel at `/admin`, primary color `#4F46E5`, brand "Hello SEO Admin", login enabled
- Navigation groups: Platform, Content, Billing, Monitoring
- `FilamentUser` interface on User model — `canAccessPanel()` gated by `isSuperAdmin()`

*6 Filament Resources (27 files):*
- `PlanResource` — full CRUD: name, slug, pricing, Stripe price IDs, limits (max_users/projects/scans_per_month), feature flags (branded_pdf, white_label, api_access, scheduled_scans, leadgen), ai_tier badge, sort_order
- `CouponResource` — full CRUD: code (copyable), stripe_coupon_id, discount_type badge, discount_value, applicable_plan_ids (CheckboxList), max_redemptions, times_redeemed, expires_at, is_active toggle
- `UserResource` — full CRUD with gear icon action menu: password management (set on create, optional on edit), super admin toggle, send password reset link, deactivate/reactivate, delete. Filters: super admin, active status, email verified
- `OrganizationResource` — full CRUD with gear icon action menu: slug auto-fills from name on create (disabled on edit), plan select, Stripe fields (read-only, hidden on create), deactivate/reactivate, delete. UsersRelationManager (clickable rows, change role, detach) + ProjectsRelationManager (read-only)
- `ProjectResource` — read-only: name, url, organization, scan_schedule badge, scans_count, target_keywords. ScansRelationManager with status badges
- `ScanResource` — read-only: project, organization, status badge (`ScanStatus->color()`), overall_score, duration, is_wordpress, triggered_by. ModuleResultsRelationManager with ViewAction modal for findings/recommendations JSON

*4 Dashboard Widgets (4 files):*
- `PlatformStatsOverview` — stat cards: Users, Organizations, Projects, Scans (+ "X this month" descriptions)
- `RecentScansTable` — 10 latest scans with project, organization, status badge, score
- `PlanDistributionChart` — doughnut chart of organization count per plan
- `SubscriptionOverview` — active subscriptions, estimated MRR, free accounts, grace period count. Falls back to "Stripe Not Configured" via `BillingService::isStripeConfigured()`

*Audit Log Viewer (3 files):*
- Custom Filament page with `InteractsWithTable` — read-only
- Table: user name, action badge, auditable_type (short class name), auditable_id, ip_address, created_at
- ViewAction modal for old_values/new_values JSON detail
- Filters: user, action, model type, date range

*User/Organization Account Management:*
- `deactivated_at` timestamp added to both users and organizations tables (migration)
- `isActive()`, `deactivate()`, `reactivate()` methods on both models
- Status badge column (Active/Deactivated) on both resource tables
- Active status filter on both resources
- `ActionGroup` gear icon pattern for clean action dropdowns (edit, deactivate/reactivate, delete, password reset)

*Model Enhancements:*
- `Plan::organizations()` HasMany relationship added
- `User` implements `FilamentUser` with `canAccessPanel()` gate
- `is_super_admin` and `deactivated_at` added to User `$fillable`
- `deactivated_at` added to Organization `$fillable`

*7 Model Factories (7 files):*
- `OrganizationFactory`, `PlanFactory`, `ProjectFactory`, `ScanFactory`, `CouponFactory`, `AuditLogFactory` (new)
- `UserFactory` updated: `array()` syntax, `superAdmin()` state

*Tests (6 files, 27 new tests → 92 total):*
- `AdminPanelAccessTest` — super admin access, regular user 403, guest redirect (4 tests)
- `AdminResourceListTest` — each resource list page loads (6 tests)
- `PlanResourceTest` — create, edit, validation (5 tests)
- `CouponResourceTest` — create, edit, validation (4 tests)
- `AuditLogViewerTest` — page loads, entries display, filters (4 tests)
- `DashboardWidgetTest` — widgets render, correct counts (4 tests)

**Tech notes:**
- Filament v5: `Section` is `Filament\Schemas\Components\Section` (NOT `Filament\Forms\Components\Section`)
- Filament v5: Form uses `Schema $schema` not `Form $form`, table uses `recordActions`/`toolbarActions`
- Filament v5: `requiredOn()` doesn't exist — use `->required(fn (string $operation): bool => $operation === "create")`
- Filament v5: List pages need explicit `getHeaderActions()` with `CreateAction::make()` for "New" button
- Filament v5: `canEdit()`/`canDelete()` resource methods don't work for record-level auth — use policies or `->hidden()` on actions
- N+1 fix: SubscriptionOverview eager loads `subscriptions` with `->with(array("plan", "subscriptions"))`
- 92 tests passing (181 assertions), Vite build clean
- ~46 new files, ~8 modified

---

### Sprint 7: Frontend (PDF Reports, Charts, Polish)
**Status:** COMPLETE
**Date completed:** 2026-02-09

**Delivered:**

*PDF Report System (3 files):*
- `PdfReportService` — builds report data (module results grouped by category via `ModuleRegistry`, status counts, executive summary) and generates PDF via dompdf
- `ScanReportController` — authorizes via `Gate::authorize("access", $scan->project)`, verifies scan is completed, delegates to service
- `reports/scan-pdf.blade.php` — standalone HTML with inline CSS (dompdf doesn't support Tailwind). Cover page with brand, project metadata, color-coded score box, status summary counts. Conditional AI executive summary page. Module results grouped by category with status badges, findings, recommendations, AI suggestions. Footer with page numbers via dompdf PHP script
- PDF reports available to all plans (no feature gating)
- Filename format: `seo-report-{domain}-{Y-m-d}.pdf`

*Score Trend Chart (1 file):*
- `<x-score-trend-chart :scans="$scans" />` Blade component with Alpine.js + Chart.js
- Line chart: indigo (#4F46E5) line, smooth tension (0.3), gradient fill
- Points color-coded per score: green (≥80), amber (≥50), red (<50)
- X-axis: scan dates as "MMM d", Y-axis: 0-100
- Filters to completed scans with scores, sorts ascending, limits to 20
- Shown on project detail when ≥2 completed scans; single-scan hint when 1 scan

*Cross-Project Scans Page (1 file):*
- `ScanController::index()` — queries all scans across org's projects with pagination
- `scans/index.blade.php` — table with project name, URL domain, status badge, score, duration, triggered by, date
- Empty state with CTA to create project
- Sidebar "Scans" link wired to `route("scans.index")` with active state
- "Reports" placeholder removed from sidebar

*Dashboard Enhancements (3 files):*
- `DashboardService::recentScans()` — fetches recent completed scans with project relationship
- `DashboardService::gettingStartedStatus()` — returns `hasProject`, `hasCompletedScan` booleans
- Dashboard view: dynamic recent scans table with "View all →" link (replaces hardcoded empty state)
- Progressive getting started checklist: completed steps get green checkmarks, next step gets accent border + CTA, cards hidden once all steps done

*Responsive Polish (5 files):*
- Project index: hidden URL and Last Scan columns on mobile (`hidden sm:table-cell`)
- Project show: added flex-wrap to header, hid Duration column on mobile
- Scan results: added flex-wrap to header and action buttons container
- Scans index: URL/Duration/Triggered By columns hidden on mobile
- Billing toggle: added `min-h-[44px]` for 44px touch targets

*Dependencies:*
- `barryvdh/laravel-dompdf` v3.1.1 — PDF generation
- `chart.js` — score trend charts (tree-shaken: LineController, PointElement, CategoryScale, LinearScale, Tooltip, Filler)
- Chart.js registered globally via `window.Chart` in `resources/js/app.js`

*Tests (4 files, 17 new tests → 109 total):*
- `ScanReportTest` — auth required, project access check, completed scan required, PDF downloads with correct content type, correct filename (5 tests)
- `ProjectShowTest` — page loads, chart shown with ≥2 scans, chart hidden with 1 scan, chart hidden with 0 scans (4 tests)
- `ScansIndexTest` — auth required, page loads, empty state, lists scans, org scoping (5 tests)
- `DashboardEnhancementTest` — empty state for new user, recent scans shown, getting started hidden when complete (3 tests)

**Routes added:**
- `GET /scans` — cross-project scans index
- `GET /scans/{scan}/pdf` — download PDF report

**Tech notes:**
- dompdf doesn't support Tailwind/Flexbox — PDF template uses inline CSS with traditional layout
- Chart.js tree-shaken imports keep bundle at 236.53 KB JS total
- `Js::from()` Blade helper passes PHP arrays to Alpine.js/Chart.js safely
- 109 tests passing (222 assertions), Vite build clean
- ~10 new files, ~16 modified

---

### Sprint 8: Polish (Onboarding, Emails, Legal, Tests, Deployment)
**Status:** COMPLETE
**Date completed:** 2026-02-09

**Delivered:**

*Legal Pages (6 files):*
- `LegalController` — 3 methods: `termsOfService()`, `privacyPolicy()`, `acceptableUse()`
- `legal/terms.blade.php` — Terms of Service (11 sections: acceptance, description, accounts, billing, permitted use, IP, privacy, liability, termination, changes, contact)
- `legal/privacy.blade.php` — Privacy Policy (10 sections: collection, usage, storage, third-party, cookies, retention, rights, children, changes, contact)
- `legal/acceptable-use.blade.php` — Acceptable Use Policy (7 sections: purpose, authorized scanning, prohibited activities, rate limits, AI usage, reporting, enforcement)
- `<x-footer-links>` component — Terms, Privacy, Acceptable Use links + copyright, included on welcome, pricing, and legal pages
- Routes: `GET /terms`, `GET /privacy`, `GET /acceptable-use` (public, no auth required)

*Email Notifications (4 files):*
- `WelcomeNotification` — queued MailMessage sent on registration: greeting, app intro, "Go to Dashboard" button
- `ScanCompleteNotification` — queued MailMessage sent when scan finishes: project name, score, "View Results" button, PDF mention
- Fired from `CreateOrganizationForNewUser` listener (welcome) and `ScanOrchestrator::finalizeScan()` (scan complete, wrapped in try-catch)
- Both implement `ShouldQueue` for async delivery

*Onboarding Wizard (3 files):*
- `OnboardingController::store()` — validates name/url/keywords, calls `ProjectService::createProject()`, optionally dispatches `ProcessScanJob`, returns JSON `{success, redirect}`
- `<x-onboarding-wizard>` — Alpine.js multi-step overlay (4 steps: welcome intro → project details → keywords → confirm & launch). Progress dots, client-side validation, fetch POST, dismiss to localStorage
- Dashboard includes wizard when `!gettingStarted["hasProject"]`
- Route: `POST /onboarding` with `enforce.plan:projects` middleware

*Thorough Test Coverage (10 files, 43 new tests → 152 total):*
- `ProjectCrudTest` — index auth, lists projects, org scoping, create loads, store creates, validation, update, destroy (8 tests)
- `ProjectAuthorizationTest` — view/edit/update/delete/scan forbidden for other org (5 tests)
- `ScanTriggerTest` — auth, creates pending scan, dispatches job, redirects, project access (5 tests)
- `ScanResultsTest` — auth, loads for owner, project access, displays score (4 tests)
- `AiEndpointTest` — status JSON, project access, optimize requires pro, summary requires pro, requires completed scan (5 tests)
- `AiSettingsTest` — auth, requires pro, pro user can save (3 tests)
- `BillingActionsTest` — index loads, change plan requires owner, cancel/resume require owner, portal without Stripe (5 tests)
- `LegalPageTest` — terms/privacy/acceptable use all load (3 tests)
- `OnboardingTest` — creates project, validates, triggers scan (3 tests)
- `RegistrationTest` — added org creation + welcome notification tests (2 new tests)

*Production Deployment (3 files):*
- `.env.production.example` — annotated production config: MySQL, SMTP, Stripe live keys, AI keys, security settings (`APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`)
- `.github/workflows/ci.yml` — GitHub Actions: PHPUnit (PHP 8.4, SQLite) + Vite build (Node 22), triggers on push/PR to master
- `DEPLOYMENT.md` — production checklist: server requirements, initial setup, Nginx config, Supervisor queue worker, cron scheduler, Stripe webhook URL, deployment update commands, troubleshooting

*Additional:*
- `ScanModuleResultFactory` — model factory for AI endpoint tests
- PDF tests: added `ini_set("memory_limit", "256M")` for dompdf memory requirements

**Routes added:**
- `GET /terms` — Terms of Service
- `GET /privacy` — Privacy Policy
- `GET /acceptable-use` — Acceptable Use Policy
- `POST /onboarding` — onboarding wizard submission

**Tech notes:**
- Welcome page is standalone HTML (not `<x-app-layout>`) — footer links use matching colors/dark mode classes
- Onboarding wizard uses `fetch()` POST with JSON response, `localStorage` for dismiss persistence
- `Queue::fake()` / `Notification::fake()` patterns used for async test isolation
- 141 tests passing (286 assertions), 11 pre-existing Filament admin failures (PHP 8.5 compat)
- Vite build: 236.53 KB JS, 76.62 KB CSS
- ~21 new files, ~8 modified

---

### Sprint 9: Multi-Page Crawl Scanning
**Status:** COMPLETE
**Date completed:** 2026-02-09

**Delivered:**

*Multi-Page Crawling (5 files):*
- `AnalyzerScope` enum — classifies modules as `SiteWide` (6) or `PerPage` (31), determines which run once vs per-page
- `SiteCrawlerService` — wraps spatie/crawler with `SameDomainHtmlProfile` to stay within the target domain
- `ScanPageCollector` — `CrawlObserver` subclass that collects crawled pages into `ScanPage` records, stores HTML in memory
- `SameDomainHtmlProfile` — restricts crawling to same-domain HTML URLs (skips PDFs, images, external links)
- `ScanPage` model — stores per-page data: url, http_status_code, content_type, is_homepage, crawl_depth, page_score, error_message

*ScanOrchestrator Branching:*
- `executeSinglePageScan()` — original single-page flow (Free plan or single URL)
- `executeCrawlScan()` — multi-page flow: crawl site → run per-page analyzers on each page → run site-wide analyzers once → aggregate scores
- Plan-configurable page limits: Free: 1 page, Pro: 25 pages, Agency: 100 pages
- `ScoreCalculator` extended with `calculateCrawlScore()` — weighted aggregate (70% average page score + 30% site-wide score)

*Page Analysis Service (1 file):*
- `PageAnalysisService` — runs per-page analyzers against individual `ScanPage` records, stores results with `scan_page_id` foreign key
- Reuses `ScanContext` pipeline per page for consistent analysis

*Crawl Insights (1 file):*
- `CrawlInsightsService` — generates cross-page analysis: common issues (>50% of pages), top priority fixes (by frequency × severity), quick stats (avg score, best/worst pages, status code breakdown)

*Shared View Infrastructure (1 file + 8 partials):*
- `ScanViewDataService` — unified data preparation for both `ProjectController` and `ScanController`, branches by scan type (single vs crawl)
- `scan-results-body.blade.php` — shared partial used by both `projects/show` and `scans/show`
- `single-page-results.blade.php` — two-column sidebar + module cards layout for single-page scans
- `crawl-results-layout.blade.php` — crawl scan layout with site-wide results, cross-page issues, crawled pages table
- `crawl-quick-stats.blade.php` — average score, best/worst pages, status code breakdown
- `crawl-pages-table.blade.php` — paginated table of crawled pages with score, status, depth
- `crawl-cross-page-issues.blade.php` — common issues across pages with frequency indicators
- `crawl-score-distribution.blade.php` — page score distribution chart
- `ai-executive-summary.blade.php` — extracted into shared partial

*Combined Project Page:*
- `projects/{id}` merged scan results directly into project page — accordion-based layout with Site Statistics section
- `site-statistics.blade.php` — score ring + pass/warn/fail counters + trend chart + scan history in 4-column grid
- `<x-accordion-section>` — reusable collapsible section component (Alpine.js `x-show`, no `x-collapse`)
- `<x-ai-bulk-optimize-toolbar>` — reusable AI bulk optimization component

*Config (2 files):*
- `config/scan-ui.php` — centralized UI configuration: sidebar groups, category icons, category descriptions, status filters, score ring circumference
- `config/scanning.php` — added `max_pages_per_plan` and `default_crawl_depth` settings

*Database (3 migrations):*
- `create_scan_pages_table` — url, http_status_code, content_type, is_homepage, crawl_depth, page_score, scan_duration_ms, error_message
- `add_scan_page_id_to_scan_module_results_table` — nullable foreign key (NULL = site-wide, set = per-page)
- `add_crawl_columns_to_scans_and_plans` — pages_crawled, scan_type on scans; max_pages_per_scan on plans

*Admin Panel:*
- `PlanResource` — added max_pages_per_scan field
- `ScanResource` — added pages_crawled, scan_type columns

*WordPress Detection Improvements (2 files):*
- `WhatCmsClient` — WhatCMS.org Tech API integration as primary CMS detection method
- `WpDetectionAnalyzer` — dual detection: WhatCMS.org API (primary) + HTML signals (fallback), OR merge strategy ensures no false negatives
- WordPress modules (wpDetection, wpPlugins, wpTheme) filtered from scan results when site is not WordPress, preventing false score penalties

*Detection Method Tracking (1 migration):*
- `detection_method` column on scans table — stores how WordPress was detected (whatcms_api, html_signals, rss_feed)
- Admin Scans table shows color-coded Detection badge replacing boolean WP icon

*PDF Reports:*
- `pdf-module-result.blade.php` — extracted partial for module result rendering in PDF
- PDF template updated to handle crawl scan data

*Dependencies:*
- `spatie/crawler` — multi-page site crawling

*Tests (1 file, 24 new tests → 176 total):*
- `CrawlScanTest` — 24 tests: single-page execution, multi-page crawl with plan limits, site-wide vs per-page analyzer classification, page score calculation, aggregate scoring, crawl depth tracking, ScanPage model, cross-page insights, crawl view rendering

**Routes added:**
- `GET /scans/{scan}/page/{scanPage}` — individual page results within a crawl scan

**Tech notes:**
- `scan_page_id` is nullable on module results — NULL = site-wide analyzer, set = per-page analyzer
- ScanController@store redirects to `projects.show` (not `scans.show`)
- Sidebar groups config: `config('scan-ui.sidebar_groups')` — crawl views filter with `crawl => false`
- Score ring circumference: `config('scan-ui.score_ring_circumference')` — never hardcode `2 * M_PI * 34`
- 176 tests passing (11 pre-existing Filament failures from PHP 8.5 compat)
- ~56 files changed, ~4400 insertions

---

### Post-Sprint Polish
**Status:** ONGOING
**Date started:** 2026-02-09

> Iterative improvements, refactors, and bug fixes applied after Sprint 9.

**Project Page Redesign:**
- Score ring + pass/warn/fail merged into single carousel card with dot navigation and left/right arrows (Alpine.js slides)
- Single-page scans: carousel slides trigger `statusFilter` in parent scope
- Crawl scans: page score distribution moves into freed column slot
- Grid adapts: 3-col (single-page) or 4-col (crawl) on desktop with fixed height row (`lg:h-52`)
- Header restructured: "Project Name — Account Overview", URL on own line, status badge underneath
- Danger Zone moved from project show to edit page with Alpine.js inline confirmation

**Unified Scan Views:**
- `scans/show-crawl.blade.php` deleted — `scans/show.blade.php` now handles both single-page and crawl scans via shared `scan-results-body` partial
- AI summary trigger button extracted into `ai-summary-header-button.blade.php` shared partial
- Site-wide/single-page scope banners with amber/gray styling
- Crawl scans default to Crawled Pages section instead of SEO Audit

**SitemapAnalysis Analyzer (1 new module):**
- `SitemapAnalysisAnalyzer` — cross-references sitemap URLs against crawled pages
- Sitemap detail table component with expand/collapse for URL lists
- Enriched by `ScanOrchestrator` post-crawl for sitemap-vs-crawled comparison

**Cross-Page Issues:**
- Made clickable/expandable with affected page lists and progress bars
- Frequency indicators show what percentage of pages are affected

**Module Reorganization:**
- Deleted `WikipediaLinkCheckAnalyzer` (no SEO evidence backing it) — 37 → 36 modules
- Recategorized zero-weight modules (favicon, socialTags, serpPreview, googleMapEmbed) to "Extras" category
- Sidebar group renames: "E-E-A-T Signals" + "Content Analysis" → "Quality & Trust", "WordPress" → "Platform", new "Informational" group for Extras
- "Extras" sidebar items show "Info" badge instead of pass/fail ratio

**AI Executive Summary:**
- Made collapsible with localStorage persistence for expand/collapse state
- Moved trigger button to header nav area

**Plan-Configurable Crawl Depth:**
- `max_crawl_depth` column added to plans table
- Filament admin field for per-plan crawl depth limits

**Crawl Scan AI Context Fix:**
- `BuildsPageContext` now filters sibling results by `scan_page_id` so per-page AI optimization gets context from the correct page instead of mixed cross-page data

**Bulk Optimizer Removal:**
- `<x-ai-bulk-optimize-toolbar>` deleted — unreliable at scale, costly API usage
- Replaced with per-module AI optimization only

**Chart.js Fix:**
- Destroy existing chart instance before re-initialization to prevent canvas reuse errors

**Toast Notifications:**
- Moved to bottom-right position

**AI Suggestion Panel Enhancements:**
- Original vs optimized comparison with character counts
- Re-optimize button moved inside AI suggestion panel, right-aligned

**Module Card UI Overhaul:**
- Finding items: colored mini-cards with status-based left borders and background tints (emerald/amber/red/blue) replacing flat `divide-y` list
- Text bumped from `text-[13px]` to `text-sm` (14px)
- New `<x-scan.heading-tree>` component — visual heading hierarchy with colored tag pills (H2/H3/H4), indented rows, hidden heading indicator
- New `<x-scan.keyword-checklist>` component — visual location grid with green/red marks for keyword presence
- Link stats: inline stat badges for Internal/External/Nofollow counts above findings

**H2-H6 Tags Analyzer Rewrite:**
- 7 accumulating checks: excessive headings (>30), heading-to-content ratio, duplicate detection with examples, skipped levels, empty headings, flat hierarchy, hidden heading reporting
- `HtmlParser::isElementHidden()` — walks DOM ancestors checking `aria-hidden`, inline `display:none`/`visibility:hidden`

**Full SEO Module Audit (7 analyzers improved):**
- `HtmlParser` — image-only heading detection (`imageOnly`/`imageAlt` flags), title tag counting
- `H1TagAnalyzer` — hidden H1 exclusion, image-only H1 with/without alt text, length validation
- `H2H6TagsAnalyzer` — sequential heading jump detection, image-only heading reporting, heading length checks
- `ImageAnalysisAnalyzer` — CLS dimension check, lazy loading audit, modern format detection, decomposed into focused methods
- `TitleTagAnalyzer` — multiple `<title>` tag detection
- `LinkAnalysisAnalyzer` — generic anchor text patterns ("click here", "read more"), empty anchor text with image-link exemption
- `PerformanceHintsAnalyzer` — render-blocking script detection (scripts in `<head>` without `async`/`defer`)
- `heading-tree.blade.php` — visual indicators for image-only and empty headings

**WordPress Detail Component Refactors:**
- Plugin detail table, theme detail card, core vulnerability table redesigned
- Sitemap detail table component added

**Sidebar Polish:**
- Group headers standardized to 18px, category buttons and descriptions to 14px
- AI sparkle icons added to sidebar categories that have AI-eligible modules
- Right-pane H2 headers enlarged to 2.5rem, descriptions darkened for readability

**Scan History Table:**
- Left padding added to date link, score column center-aligned

---

### Sprint 10: Bot Protection Detection & Zyte API Fallback
**Status:** COMPLETE
**Date completed:** 2026-02-15

**Delivered:**

*Bot Protection Detection (1 file):*
- `ResponseValidator` — detects challenge pages from SiteGround (sgcaptcha), Cloudflare (JS challenge, Turnstile), Sucuri (firewall), and generic DDoS protection services. Checks content size (>1KB), HTML structure (title + links), meta-refresh redirects to captcha URLs, and 11 challenge markers
- `isRealHtmlPage()` — boolean check for real vs challenge content
- `getBlockReason()` — human-readable block reason for logging and UI display

*Zyte API Integration (2 files):*
- `ZyteFetcher` — Zyte API client with browser rendering (`browserHtml: true`), returns same `FetchResult` DTO as `HttpFetcher`. Also has `fetchResource()` for cheaper HTTP-only mode (robots.txt, sitemaps). API key managed via admin Site Settings table (not .env)
- `ZyteCrawlerService` — BFS link-following crawler using Zyte for bot-protected sites. Produces identical `ScanPageCollector` output as `SiteCrawlerService`. Link extraction via Symfony DomCrawler (same parser as spatie/crawler), URL deduplication via `UrlNormalizer`, depth tracking, configurable delay between requests

*ScanOrchestrator Fallback Flow:*
- Homepage fetch: Guzzle (free) → validate via `ResponseValidator` → if blocked & Zyte available → Zyte ($0.001/page) → validate → if still blocked → mark scan "blocked"
- Post-crawl validation: after spatie/crawler finishes, `validateCrawlResults()` checks if collected pages are challenge HTML or all-errored. If crawler was blocked and Zyte is available, deletes garbage ScanPage records and re-crawls entire site via `ZyteCrawlerService`
- Two detection paths: homepage block detection (in `fetchAndParse()`) and crawler block detection (in `validateCrawlResults()` — addresses SiteGround letting first request through but blocking subsequent crawler requests)

*Scan Status: Blocked (4 files):*
- `ScanStatus::Blocked` enum case — terminal state for bot-protection blocked scans
- Status badge: orange/amber styling (`bg-orange-50 text-orange-700`)
- Progress bar: handles blocked completion with auto-reload
- Blocked scan explanation card in scan results body — shows block reason from `progress_label`

*Fetcher Tracking (3 files):*
- `fetcher_used` column on scans table — tracks "guzzle" or "zyte" per scan
- Migration with backfill: existing scans set to "guzzle"
- All finalization paths set `fetcher_used`: `finalizeScan()`, `finalizeCrawlScan()`, `markFailed()`, `markBlocked()`

*Admin Panel (3 files):*
- `ScanResource` — new "Fetcher" badge column (gray for Guzzle, blue for Zyte API), "Detection" renamed to "CMS Detection", `Blocked` added to status filter
- `ListScans` — `maxContentWidth = Width::Full` for wider table
- `SiteSettings` — Zyte API key (password/revealable) and Enable Zyte Fallback toggle under External APIs section

*ScanPageCollector Enhancement:*
- `injectPage()` method — allows `ZyteCrawlerService` to inject pre-fetched pages directly into the collector without going through spatie/crawler's `CrawlObserver` interface

**Tech notes:**
- Zyte config stored in `settings` table (admin-managed via Site Settings page), NOT in `.env`/config. Keys: `zyte_api_key`, `zyte_enabled`. `ZyteFetcher` reads via `Setting::getValue()`
- SiteGround bot protection behavior: first HTTP request often succeeds (homepage), subsequent requests get blocked (cURL error 61). This is why post-crawl validation is essential — `fetchAndParse()` alone doesn't catch this pattern
- `ScanPageCollector` lives in `App\Services\Crawling` namespace — must be explicitly imported in `ScanOrchestrator` (in `App\Services\Scanning` namespace)
- Queue workers cache PHP code in memory — must run `php artisan queue:restart` after code changes
- 186 tests passing (11 pre-existing Filament failures from PHP 8.5 compat)
- ~20 files changed, ~1116 insertions

---

## Final Phase — "Ship It"

**Goal:** Complete the product with competitor analysis, team collaboration, white-label reports, and remaining polish. Everything needed to go live.
**Branch:** `final-phase`
**Status:** IN PROGRESS
**Date started:** 2026-02-19

### Sprint Items

| # | Feature | Status | Description |
|---|---------|--------|-------------|
| 1 | Competitor Analysis | COMPLETE | Scan vs 1-5 competitors, side-by-side score comparison, per-module diffs, detail view, Filament admin |
| 2 | Team System | IN PROGRESS | Invite via email magic link, Owner/Member roles, org switcher, shared projects/scans/usage |
| 3 | White-label PDF | NOT STARTED | Agency tier: custom logo, company name, brand colors on PDF reports |
| 4 | Broken Links module | COMPLETE | BrokenLinksAnalyzer — HTTP-probes up to 50 outbound links per page, reports 404/410/5xx/unreachable. Weight 6, Discovery & Social category |
| 5 | Schema Validation module | COMPLETE | SchemaValidationAnalyzer — validates JSON-LD and Microdata fields against Google rich result requirements for 10 schema types. Weight 5, Graphs, Schema & Links category |
| 6 | Transactional Emails | NOT STARTED | Trial ending/expired, payment failed/received, plan changed, team invite/joined |
| 7 | In-App Notifications | NOT STARTED | Notification bell/inbox in header. Events: scan complete, team invite received, member joined, payment events. Database-backed with read/unread state. |

