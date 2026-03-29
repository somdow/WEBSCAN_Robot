{{-- Shared scan results body — used by both projects/show and scans/show --}}
{{-- Expects: $scan, $scanViewData, $hasApiKey --}}
{{-- Optional: $activeScan (non-null when a new scan is running — shows progress overlay) --}}
{{-- Optional: $project, $canAddPages, $additionalPages, $maxAdditionalPages, $displayScan (for Pages tab) --}}
{{-- Optional: $showCompetitorsTab, $competitors, $maxCompetitors (for Competitors tab) --}}

@php
	$canAddPages = $canAddPages ?? false;
	$additionalPages = $additionalPages ?? collect();
	$maxAdditionalPages = $maxAdditionalPages ?? 0;
	$displayScan = $displayScan ?? $scan;
	$project = $project ?? ($displayScan?->project ?? null);
	$showPagesTab = $canAddPages && $project && $project->ownScans()->where("status", \App\Enums\ScanStatus::Completed->value)->exists();
	$showCompetitorsTab = $showCompetitorsTab ?? false;
	$competitors = $competitors ?? collect();
	$maxCompetitors = $maxCompetitors ?? 0;
@endphp

@php $activeScan = $activeScan ?? null; @endphp

{{-- Progress overlay — server-activated when scan running, or AJAX-activated via scan-started event --}}
@if($activeScan && !$activeScan->isComplete())
	<x-scan.progress-bar :scan="$activeScan" />
@else
	<x-scan.progress-bar />
@endif

{{-- Blocked scan — bot protection prevented the scan --}}
@if($scan && $scan->status === \App\Enums\ScanStatus::Blocked)
	<div class="rounded-lg border border-orange-200 bg-orange-50 px-6 py-8 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100">
				<svg class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
				</svg>
			</div>
			<div>
				<h3 class="text-base font-semibold text-orange-900">Scan Blocked by Bot Protection</h3>
				<p class="mt-1 text-sm text-orange-700">This website's hosting has bot protection that prevented our scanner from accessing the page content.</p>

				@if($scan->progress_label)
					<div class="mt-3 rounded-md bg-orange-100 px-3 py-2">
						<p class="text-xs font-medium text-orange-800">
							<span class="font-semibold">Detected:</span> {{ $scan->progress_label }}
						</p>
					</div>
				@endif

				<div class="mt-4 text-sm text-orange-600">
					<p class="font-medium">What you can do:</p>
					<ul class="mt-1.5 list-inside list-disc space-y-1 text-orange-600/90">
						<li>Ask the site owner to whitelist our scanner's IP address</li>
						<li>Try scanning again later — some protections are temporary</li>
						<li>Contact support if this site should be scannable</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
@endif

{{-- Failed scan — connection error (SSL, DNS, timeout, etc.) --}}
@if($scan && $scan->status === \App\Enums\ScanStatus::Failed)
	<div class="rounded-lg border border-red-200 bg-red-50 px-6 py-8 shadow-card">
		<div class="flex items-start gap-4">
			<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
				<svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
				</svg>
			</div>
			<div>
				<h3 class="text-base font-semibold text-red-900">Scan Failed</h3>
				<p class="mt-1 text-sm text-red-700">We were unable to complete the scan due to a connection issue with this website.</p>

				@if($scan->progress_label)
					<div class="mt-3 rounded-md bg-red-100 px-3 py-2">
						<p class="text-xs font-medium text-red-800">
							<span class="font-semibold">Details:</span> {{ $scan->progress_label }}
						</p>
					</div>
				@endif

				<div class="mt-4 text-sm text-red-600">
					<p class="font-medium">What you can do:</p>
					<ul class="mt-1.5 list-inside list-disc space-y-1 text-red-600/90">
						<li>Verify the URL is correct and the website is accessible in your browser</li>
						<li>Check if the site has a valid SSL certificate</li>
						<li>Try scanning again later — the issue may be temporary</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
@endif

{{-- Completed scan results (blurred when a new scan is running on top) --}}
@if($scan && $scan->status === \App\Enums\ScanStatus::Completed)
	@php $scanIsRunning = $activeScan && !$activeScan->isComplete(); @endphp
	<div
		x-data="{ blurred: {{ $scanIsRunning ? 'true' : 'false' }} }"
		x-on:scan-started.window="blurred = true"
		class="transition-all duration-500"
		x-bind:class="blurred && 'pointer-events-none select-none opacity-40 blur-sm'"
	>
	@php
		$isSinglePage = $scanViewData && ($scanViewData['type'] ?? '') === 'single';
	@endphp

	@php
		$pagesJsonData = $showPagesTab
			? $additionalPages->map(function ($page) {
				return array(
					"id" => $page->id,
					"uuid" => $page->uuid,
					"url" => $page->url,
					"page_score" => $page->page_score,
					"source" => $page->source,
					"analysis_status" => $page->analysis_status ?? "completed",
					"error_message" => $page->error_message,
					"scan_page_url" => $page->scan ? route("scans.show-page", array($page->scan, $page)) : null,
					"scanned_at" => $page->updated_at?->toIso8601String(),
					"_rescanning" => false,
					"_isNew" => false,
				);
			})->values()
			: collect();
	@endphp
	<div
		x-data="scanResultsManager()"
		x-on:toolkit-tab-change.window="scoreTab = $event.detail.tab; statusFilter = ''"
	>
		@if(!($hideToolkitNav ?? false))
		{{-- Score category tabs — pill/segment control --}}
		<p class="mb-1.5 text-xs font-medium uppercase tracking-wider text-text-tertiary" x-cloak>Webscan Toolkit</p>
		<div class="mb-6 flex flex-wrap items-center gap-1.5 rounded-xl bg-gray-300 p-1.5" x-cloak>
			<button
				@click="scoreTab = 'all'; statusFilter = ''; $dispatch('score-tab-changed', { slide: 0 }); (() => { const u = new URL(window.location); u.searchParams.delete('tab'); history.pushState(null, '', u); })()"
				:class="scoreTab === 'all' ? 'bg-orange-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-800 hover:bg-white/40'"
				class="rounded-lg px-5 py-2 text-sm outline-none transition-all"
			>Overview <span x-show="liveOverallScore !== null" class="ml-0.5 text-[10px] opacity-75">&middot; <span x-text="liveOverallScore"></span></span></button>
			<button
				@click="scoreTab = 'seo'; statusFilter = ''; $dispatch('score-tab-changed', { slide: 1 }); (() => { const u = new URL(window.location); u.searchParams.set('tab', 'seo'); history.pushState(null, '', u); })()"
				:class="scoreTab === 'seo' ? 'bg-orange-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-800 hover:bg-white/40'"
				class="rounded-lg px-5 py-2 text-sm outline-none transition-all"
			>SEO Analysis <span x-show="liveSeoScore !== null" class="ml-0.5 text-[10px] opacity-75">&middot; <span x-text="liveSeoScore"></span></span></button>
			<button
				@click="scoreTab = 'technical'; statusFilter = ''; $dispatch('score-tab-changed', { slide: 2 }); (() => { const u = new URL(window.location); u.searchParams.set('tab', 'technical'); history.pushState(null, '', u); })()"
				:class="scoreTab === 'technical' ? 'bg-orange-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-800 hover:bg-white/40'"
				class="rounded-lg px-5 py-2 text-sm outline-none transition-all"
			>Site Health <span x-show="liveHealthScore !== null" class="ml-0.5 text-[10px] opacity-75">&middot; <span x-text="liveHealthScore"></span></span></button>
			@if($showCompetitorsTab)
				<button
					@click="scoreTab = 'competitors'; statusFilter = ''; (() => { const u = new URL(window.location); u.searchParams.set('tab', 'competitors'); history.pushState(null, '', u); })()"
					:class="scoreTab === 'competitors' ? 'bg-orange-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-800 hover:bg-white/40'"
					class="rounded-lg px-5 py-2 text-sm outline-none transition-all"
				>Competitors</button>
			@endif
			@if($showPagesTab)
				<button
					@click="scoreTab = 'pagesList'; statusFilter = ''; (() => { const u = new URL(window.location); u.searchParams.set('tab', 'pagesList'); history.pushState(null, '', u); })()"
					:class="scoreTab === 'pagesList' ? 'bg-orange-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-800 hover:bg-white/40'"
					class="rounded-lg px-5 py-2 text-sm outline-none transition-all"
				>Page Explorer</button>
			@endif
			<span class="ml-auto"></span>
			@include("scans.partials.ai-summary-header-button", array("scan" => $scan))
		</div>
		@endif

		{{-- Floating cards: score + pass/warn/fail (Overview tab only) --}}
		<div class="mb-8" x-show="scoreTab === 'all'" x-cloak>
			@include("projects.partials.site-statistics", array(
				"scan" => $scan,
				"scanViewData" => $scanViewData,
			))
		</div>

		{{-- Tab description --}}
		<div class="mb-8 flex justify-center" x-cloak>
		<div class="inline-flex items-start gap-2.5 rounded-lg border border-amber-300 bg-amber-100 px-4 py-3">
			<svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
			</svg>
			<p class="text-[13px] leading-relaxed text-amber-900">
				<span x-show="scoreTab === 'all' && (!pages || pages.length === 0)">Here's your overall health check based on a homepage-only analysis.@if($showPagesTab) This is a great starting point, <a @click.prevent="scoreTab = 'pagesList'; statusFilter = ''" href="#" class="font-bold text-amber-700 underline underline-offset-2 hover:text-amber-900">add more pages</a> to broaden your sample size and get a more accurate, site-wide picture.@else This is a great starting point for understanding your site's SEO health.@endif</span>
				<span x-show="scoreTab === 'all' && pages && pages.length > 0">Your scores are based on the homepage plus <strong x-text="pages.length"></strong> additional <span x-text="pages.length === 1 ? 'page' : 'pages'"></span>. The more pages you analyze, the more reliable your scores become. @if($showPagesTab)<a @click.prevent="scoreTab = 'pagesList'; statusFilter = ''" href="#" class="font-bold text-amber-700 underline underline-offset-2 hover:text-amber-900">Add more pages</a> for even better accuracy.@endif</span>
				<span x-show="scoreTab === 'seo'">Everything that shapes how search engines see your site — page titles, content quality, linking structure, and trust signals like E-E-A-T. Address these areas to boost your organic visibility.</span>
				<span x-show="scoreTab === 'technical'">The technical foundation behind your site — page speed, security headers, mobile-friendliness, and server reliability. A strong technical base helps both rankings and user experience.</span>
				@if($showCompetitorsTab)
					<span x-show="scoreTab === 'competitors'">See how your site stacks up against the competition. Compare overall scores, category breakdowns, and individual checks to find where you're winning — and where there's room to pull ahead.</span>
				@endif
				@if($showPagesTab)
					<span x-show="scoreTab === 'pagesList'">All the pages you've analyzed so far, with individual scores and statuses. Add pages manually or discover them automatically to broaden your analysis.</span>
				@endif
			</p>
		</div>
		</div>

		{{-- AI Executive Summary (Overview tab only) --}}
		<div x-show="scoreTab === 'all'" x-cloak>
			@if($scanViewData)
				@include("scans.partials.ai-executive-summary", array("scan" => $scan))
			@endif
		</div>

		{{-- Tab: All (Overview) --}}
		<div x-show="scoreTab === 'all'">
			@if($isSinglePage)
				@include("scans.partials.single-page-results", array(
					"groupedResults" => $scanViewData["groupedResults"],
					"statusGroupedResults" => $scanViewData["statusGroupedResults"],
					"moduleLabels" => $scanViewData["moduleLabels"],
					"scan" => $scan,
				))
			@endif
		</div>

		{{-- Tab: SEO Analysis --}}
		<div x-show="scoreTab === 'seo'" x-cloak>
			@include("scans.partials.seo-content-results", array(
				"scan" => $scan,
				"scanViewData" => $scanViewData,
				"hasApiKey" => $hasApiKey,
			))
		</div>

		{{-- Tab: Site Health --}}
		<div x-show="scoreTab === 'technical'" x-cloak>
			@include("scans.partials.site-health-results", array(
				"scan" => $scan,
				"scanViewData" => $scanViewData,
				"hasApiKey" => $hasApiKey,
			))
		</div>

		{{-- Tab: Competitors --}}
		@if($showCompetitorsTab)
			<div x-show="scoreTab === 'competitors'" x-cloak>
				@include("scans.partials.competitors-tab", array(
					"project" => $project,
					"competitors" => $competitors,
					"maxCompetitors" => $maxCompetitors,
					"scan" => $scan,
				))
			</div>
		@endif

		{{-- Tab: Pages (combined list + add/discover) --}}
		@if($showPagesTab)
			<div x-show="scoreTab === 'pagesList'" x-cloak>
				@include("scans.partials.pages-list", array(
					"project" => $project,
					"maxAdditionalPages" => $maxAdditionalPages,
				))
			</div>
		@endif

		@if($showPagesTab || $showCompetitorsTab)
			@include("scans.partials.pages-manager-script", array(
				"project" => $project,
				"pagesJsonData" => $pagesJsonData,
				"isSinglePage" => $isSinglePage,
				"scanViewData" => $scanViewData,
			))
		@else
			<script>
			function scanResultsManager() {
				return {
					scanning: false,
					activeCategory: @json($isSinglePage ? ($scanViewData["groupedResults"]->keys()->first() ?? "") : ""),
					statusFilter: '',
					activeSection: '',
					searchQuery: '',
					scoreTab: new URLSearchParams(window.location.search).get('tab') || 'all',
					healthSection: '',
					seoSection: '',
					pages: [],
					liveOverallScore: @json($scan->overall_score ?? null),
					liveSeoScore: @json($scan->seo_score ?? null),
					liveHealthScore: @json($scan->health_score ?? null),
					scoreColorClass(score) {
						if (score === null) return 'text-gray-400';
						if (score >= 80) return 'text-emerald-600';
						if (score >= 50) return 'text-amber-600';
						return 'text-red-600';
					},
					scoreStrokeClass(score) {
						if (score === null) return 'stroke-gray-300';
						if (score >= 80) return 'stroke-emerald-500';
						if (score >= 50) return 'stroke-amber-500';
						return 'stroke-red-500';
					},
					formatScannedDate(isoString) {
						if (!isoString) return '\u2014';
						const d = new Date(isoString);
						return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
					},
				};
			}
			</script>
		@endif
	</div>
	</div>
@elseif($scan && $scan->status === \App\Enums\ScanStatus::Completed)
	<div class="rounded-lg border border-border bg-surface px-6 py-12 text-center shadow-card">
		<p class="text-sm text-text-secondary">No module results were generated for this scan.</p>
	</div>
@endif

{{-- AI modal --}}
@if(!$hasApiKey)
	<x-configure-api-key-modal />
@endif
