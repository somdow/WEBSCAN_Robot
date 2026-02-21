{{-- Competitors tab — add competitors and view side-by-side score comparison --}}
{{-- Expects parent x-data scope with competitor state from scanResultsManager --}}
{{-- Expects variables: $project, $competitors, $maxCompetitors, $scan --}}

@php
	$circumference = config("scan-ui.score_ring_circumference");
	$categoryIcons = config("scan-ui.category_icons");
	$sidebarGroups = config("scan-ui.sidebar_groups");

	$registry = app(\App\Services\Scanning\ModuleRegistry::class);
	$moduleLabels = $registry->labelMap();

	$competitorsJsonData = $competitors->map(function ($competitor) use ($circumference, $registry, $project) {
		$latestScan = $competitor->latestScan;
		$scanCompleted = $latestScan && $latestScan->status === \App\Enums\ScanStatus::Completed;

		$categoryScores = array();
		$moduleStatuses = array();
		if ($scanCompleted) {
			$moduleResults = $latestScan->moduleResults;

			$grouped = $moduleResults->groupBy(fn($result) => $registry->resolveCategory($result->module_key));
			foreach ($grouped as $categoryName => $categoryModules) {
				$total = $categoryModules->count();
				$passed = $categoryModules->filter(fn($r) => $r->status->value === "ok")->count();
				$categoryScores[] = array(
					"name" => $categoryName,
					"passed" => $passed,
					"total" => $total,
				);
			}

			foreach ($moduleResults as $result) {
				$moduleStatuses[$result->module_key] = $result->status->value;
			}
		}

		return array(
			"id" => $competitor->id,
			"uuid" => $competitor->uuid,
			"url" => $competitor->url,
			"name" => $competitor->displayName(),
			"overall_score" => $scanCompleted ? $latestScan->overall_score : null,
			"seo_score" => $scanCompleted ? $latestScan->seo_score : null,
			"health_score" => $scanCompleted ? $latestScan->health_score : null,
			"scan_status" => $latestScan ? $latestScan->status->value : null,
			"scanned_at" => $latestScan?->updated_at?->toIso8601String(),
			"screenshot_url" => $scanCompleted ? $latestScan->getScreenshotUrl() : null,
			"detail_url" => route("competitors.show", array($project, $competitor)),
			"category_scores" => $categoryScores,
			"module_statuses" => $moduleStatuses,
			"_expanded" => false,
			"_rescanning" => false,
			"_removing" => false,
		);
	})->values();

	/* Build "your" module statuses from the project's own scan for comparison */
	$ownModuleStatuses = array();
	if ($scan && $scan->status === \App\Enums\ScanStatus::Completed) {
		foreach ($scan->moduleResults as $result) {
			$ownModuleStatuses[$result->module_key] = $result->status->value;
		}
	}

	/* Build grouped module keys for the per-module comparison section */
	$comparisonGroups = array();
	$allModuleKeys = array_keys($ownModuleStatuses);
	foreach ($allModuleKeys as $moduleKey) {
		$category = $registry->resolveCategory($moduleKey);
		$comparisonGroups[$category][] = $moduleKey;
	}
	ksort($comparisonGroups);
@endphp

<div x-init="
	competitors = {{ Js::from($competitorsJsonData) }};
	maxCompetitors = {{ $maxCompetitors }};
	circumference = {{ $circumference }};
	ownModuleStatuses = {{ Js::from($ownModuleStatuses) }};
	comparisonGroups = {{ Js::from($comparisonGroups) }};
	moduleLabels = {{ Js::from($moduleLabels) }};
">
	{{-- Add Competitor form --}}
	<div class="mb-6 rounded-lg border border-border bg-surface px-5 py-4 shadow-card">
		<div class="flex items-center justify-between gap-4">
			<div class="min-w-0">
				<h3 class="text-sm font-semibold text-text-primary">Add Competitor</h3>
				<p class="mt-0.5 text-xs text-text-tertiary">
					<span x-text="competitors.length"></span> of <span x-text="maxCompetitors"></span> competitors used
				</p>
			</div>
			<button
				x-show="!showCompetitorInput && competitors.length < maxCompetitors"
				@click="showCompetitorInput = true"
				class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-medium text-white shadow-sm transition-colors hover:bg-indigo-700"
			>
				<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
				</svg>
				Add
			</button>
		</div>

		{{-- URL input --}}
		<div x-show="showCompetitorInput" x-cloak class="mt-3">
			<form @submit.prevent="submitCompetitor()" class="flex gap-2">
				<input
					type="url"
					x-model="newCompetitorUrl"
					placeholder="https://competitor.com"
					class="flex-1 rounded-lg border border-border bg-white px-3 py-2 text-sm text-text-primary placeholder-text-tertiary outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400"
					required
				/>
				<button
					type="submit"
					:disabled="isAddingCompetitor || !newCompetitorUrl.trim()"
					class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:opacity-50"
				>
					<template x-if="isAddingCompetitor">
						<svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
						</svg>
					</template>
					<span x-text="isAddingCompetitor ? 'Scanning...' : 'Scan'"></span>
				</button>
				<button
					type="button"
					@click="showCompetitorInput = false; newCompetitorUrl = ''; competitorError = ''"
					class="rounded-lg px-3 py-2 text-xs text-text-secondary hover:bg-gray-100"
				>Cancel</button>
			</form>
			<p x-show="competitorError" x-text="competitorError" x-cloak class="mt-2 text-xs text-red-600"></p>
		</div>

		{{-- Plan limit reached --}}
		<div x-show="competitors.length >= maxCompetitors && !showCompetitorInput" x-cloak class="mt-2">
			<p class="text-xs text-amber-600">Competitor limit reached. Upgrade your plan for more.</p>
		</div>
	</div>

	{{-- Empty state --}}
	<div x-show="competitors.length === 0" x-cloak class="rounded-lg border border-border bg-surface px-6 py-12 text-center shadow-card">
		<svg class="mx-auto h-10 w-10 text-text-tertiary/40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
		</svg>
		<p class="mt-3 text-sm font-medium text-text-secondary">No competitors yet</p>
		<p class="mt-1 text-xs text-text-tertiary">Add a competitor URL above to see how your site compares.</p>
	</div>

	{{-- Competitor cards --}}
	<h3 x-show="competitors.length > 0" class="mb-3 text-xs font-semibold uppercase tracking-wider text-text-tertiary">Competitor List</h3>
	<div class="space-y-3">
		<template x-for="competitor in competitors" :key="competitor.id">
			<div class="group overflow-visible rounded-lg border border-border/80 border-l-4 bg-surface shadow-card transition-all duration-200 hover:shadow-card-hover scroll-mt-4"
				:class="{
					'border-l-emerald-400': competitor.overall_score !== null && competitor.overall_score >= 80,
					'border-l-amber-400': competitor.overall_score !== null && competitor.overall_score >= 50 && competitor.overall_score < 80,
					'border-l-red-400': competitor.overall_score !== null && competitor.overall_score < 50,
					'border-l-gray-300': competitor.overall_score === null,
				}"
			>
				{{-- Clickable header --}}
				<button
					@click="competitor._expanded = !competitor._expanded"
					class="flex w-full cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left transition-colors duration-150 hover:bg-gray-50/50"
				>
					<div class="flex min-w-0 flex-1 items-center gap-3">
						{{-- Screenshot thumbnail --}}
						<div class="hidden h-10 w-16 shrink-0 overflow-hidden rounded border border-gray-200 bg-gray-100 sm:block">
							<template x-if="competitor.screenshot_url">
								<img :src="competitor.screenshot_url" :alt="competitor.name" class="h-full w-full object-cover object-top" />
							</template>
							<template x-if="!competitor.screenshot_url">
								<div class="flex h-full w-full items-center justify-center">
									<svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
									</svg>
								</div>
							</template>
						</div>
						<div class="min-w-0">
							<h3 class="text-[16px] font-semibold tracking-tight text-text-primary" x-text="competitor.name"></h3>
							<p class="mt-0.5 truncate text-[13px] leading-relaxed text-text-tertiary" x-text="competitor.url"></p>
						</div>
					</div>

					{{-- Right: score rings + status badges + chevron --}}
					<div class="flex shrink-0 items-center gap-4">
						{{-- Status badges (when scan not completed) --}}
						<template x-if="competitor.scan_status === 'pending' || competitor.scan_status === 'running'">
							<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-medium text-blue-700">
								<svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
								</svg>
								Scanning
							</span>
						</template>
						<template x-if="competitor.scan_status === 'blocked'">
							<span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-[11px] font-medium text-orange-700">Blocked</span>
						</template>
						<template x-if="competitor.scan_status === 'failed'">
							<span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-medium text-red-700">Failed</span>
						</template>

						{{-- Score rings (only when scan completed) --}}
						<template x-if="competitor.overall_score !== null">
							<div class="hidden items-center gap-3 sm:flex">
								{{-- Overall --}}
								<div class="flex flex-col items-center">
									<div class="relative h-10 w-10">
										<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
											<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
											<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round"
												:class="scoreStrokeClass(competitor.overall_score)"
												:stroke-dasharray="circumference"
												:stroke-dashoffset="circumference * (1 - competitor.overall_score / 100)"
												style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)"
											/>
										</svg>
										<span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold" :class="scoreColorClass(competitor.overall_score)" x-text="competitor.overall_score"></span>
									</div>
									<span class="mt-0.5 text-[9px] text-text-tertiary">Overall</span>
								</div>
								{{-- SEO --}}
								<div class="flex flex-col items-center">
									<div class="relative h-10 w-10">
										<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
											<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
											<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round"
												:class="scoreStrokeClass(competitor.seo_score)"
												:stroke-dasharray="circumference"
												:stroke-dashoffset="circumference * (1 - competitor.seo_score / 100)"
												style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)"
											/>
										</svg>
										<span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold" :class="scoreColorClass(competitor.seo_score)" x-text="competitor.seo_score"></span>
									</div>
									<span class="mt-0.5 text-[9px] text-text-tertiary">SEO</span>
								</div>
								{{-- Health --}}
								<div class="flex flex-col items-center">
									<div class="relative h-10 w-10">
										<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
											<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
											<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round"
												:class="scoreStrokeClass(competitor.health_score)"
												:stroke-dasharray="circumference"
												:stroke-dashoffset="circumference * (1 - competitor.health_score / 100)"
												style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)"
											/>
										</svg>
										<span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold" :class="scoreColorClass(competitor.health_score)" x-text="competitor.health_score"></span>
									</div>
									<span class="mt-0.5 text-[9px] text-text-tertiary">Health</span>
								</div>
							</div>
						</template>

						{{-- Chevron --}}
						<svg
							class="h-4 w-4 text-text-tertiary transition-transform duration-200"
							:class="competitor._expanded && 'rotate-180'"
							fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
						>
							<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
						</svg>
					</div>
				</button>

				{{-- Expanded detail panel --}}
				<div x-show="competitor._expanded" x-cloak
				x-transition:enter="transition duration-200 ease-out"
				x-transition:enter-start="opacity-0"
				x-transition:enter-end="opacity-100"
				x-transition:leave="transition duration-150 ease-in"
				x-transition:leave-start="opacity-100"
				x-transition:leave-end="opacity-0"
			>
					<div class="border-t border-border/60 px-5 py-4">
						{{-- Actions bar --}}
						<div class="mb-4 flex items-center justify-between">
							<p class="text-xs text-text-tertiary">
								Last scanned: <span x-text="competitor.scanned_at ? formatScannedDate(competitor.scanned_at) : 'Never'"></span>
							</p>
							<div class="flex items-center gap-2">
								<a
									:href="competitor.detail_url"
									@click.stop
									x-show="competitor.scan_status === 'completed'"
									class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 transition-colors hover:bg-indigo-100"
								>
									<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
										<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
									</svg>
									View Details
								</a>
								<button
									@click.stop="rescanCompetitor(competitor)"
									:disabled="competitor._rescanning || competitor.scan_status === 'running' || competitor.scan_status === 'pending'"
									class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-text-secondary transition-colors hover:bg-gray-200 disabled:opacity-50"
								>
									<svg class="h-3.5 w-3.5" :class="competitor._rescanning && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
									</svg>
									<span x-text="competitor._rescanning ? 'Scanning...' : 'Rescan'"></span>
								</button>
								<button
									@click.stop="removeCompetitor(competitor)"
									:disabled="competitor._removing"
									class="inline-flex items-center gap-1 rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-50"
								>
									<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
									</svg>
									Remove
								</button>
							</div>
						</div>

						{{-- Two-column layout: You vs Them (left) + Category Breakdown (right) --}}
						<template x-if="competitor.overall_score !== null && liveOverallScore !== null">
							<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
								{{-- Left: Score Comparison --}}
								<div class="flex flex-col">
									<h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-text-tertiary">Score Comparison</h4>
									<div class="rounded-lg border border-gray-200 bg-gray-50">
										<table class="w-full">
											<thead class="sticky top-0 bg-gray-100 text-[10px] font-semibold uppercase tracking-wider text-text-tertiary">
												<tr>
													<th class="px-3 py-1.5 text-left">Category</th>
													<th class="px-3 py-1.5 text-center text-indigo-600">You</th>
													<th class="px-3 py-1.5 text-center"></th>
													<th class="px-3 py-1.5 text-center text-gray-500">Them</th>
												</tr>
											</thead>
											<tbody>
												{{-- Overall --}}
												<tr class="border-t border-gray-200/60">
													<td class="px-3 py-1.5 text-[13px] text-text-primary">Overall</td>
													<td class="px-3 py-1.5 text-center text-sm font-bold text-indigo-600" x-text="liveOverallScore"></td>
													<td class="px-3 py-1.5 text-center text-xs font-bold"
														:class="liveOverallScore > competitor.overall_score ? 'text-emerald-600' : (liveOverallScore < competitor.overall_score ? 'text-red-500' : 'text-gray-400')"
														x-text="liveOverallScore > competitor.overall_score ? '+' + (liveOverallScore - competitor.overall_score) : (liveOverallScore < competitor.overall_score ? (liveOverallScore - competitor.overall_score) : '=')"
													></td>
													<td class="px-3 py-1.5 text-center text-sm font-bold" :class="scoreColorClass(competitor.overall_score)" x-text="competitor.overall_score"></td>
												</tr>
												{{-- SEO --}}
												<tr class="border-t border-gray-200/60">
													<td class="px-3 py-1.5 text-[13px] text-text-primary">SEO</td>
													<td class="px-3 py-1.5 text-center text-sm font-bold text-indigo-600" x-text="liveSeoScore ?? '—'"></td>
													<td class="px-3 py-1.5 text-center">
														<template x-if="liveSeoScore !== null && competitor.seo_score !== null">
															<span class="text-xs font-bold"
																:class="liveSeoScore > competitor.seo_score ? 'text-emerald-600' : (liveSeoScore < competitor.seo_score ? 'text-red-500' : 'text-gray-400')"
																x-text="liveSeoScore > competitor.seo_score ? '+' + (liveSeoScore - competitor.seo_score) : (liveSeoScore < competitor.seo_score ? (liveSeoScore - competitor.seo_score) : '=')"
															></span>
														</template>
														<template x-if="liveSeoScore === null || competitor.seo_score === null">
															<span class="text-xs font-bold text-gray-300">—</span>
														</template>
													</td>
													<td class="px-3 py-1.5 text-center text-sm font-bold" :class="scoreColorClass(competitor.seo_score)" x-text="competitor.seo_score ?? '—'"></td>
												</tr>
												{{-- Health --}}
												<tr class="border-t border-gray-200/60">
													<td class="px-3 py-1.5 text-[13px] text-text-primary">Health</td>
													<td class="px-3 py-1.5 text-center text-sm font-bold text-indigo-600" x-text="liveHealthScore ?? '—'"></td>
													<td class="px-3 py-1.5 text-center">
														<template x-if="liveHealthScore !== null && competitor.health_score !== null">
															<span class="text-xs font-bold"
																:class="liveHealthScore > competitor.health_score ? 'text-emerald-600' : (liveHealthScore < competitor.health_score ? 'text-red-500' : 'text-gray-400')"
																x-text="liveHealthScore > competitor.health_score ? '+' + (liveHealthScore - competitor.health_score) : (liveHealthScore < competitor.health_score ? (liveHealthScore - competitor.health_score) : '=')"
															></span>
														</template>
														<template x-if="liveHealthScore === null || competitor.health_score === null">
															<span class="text-xs font-bold text-gray-300">—</span>
														</template>
													</td>
													<td class="px-3 py-1.5 text-center text-sm font-bold" :class="scoreColorClass(competitor.health_score)" x-text="competitor.health_score ?? '—'"></td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>

								{{-- Right: Category Breakdown --}}
								<div x-show="competitor.category_scores.length > 0" class="flex flex-col">
									<h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-text-tertiary">Category Breakdown</h4>
									<div class="max-h-[200px] overflow-y-auto rounded-lg border border-gray-200 bg-gray-50">
										<table class="w-full">
											<thead class="sticky top-0 bg-gray-100 text-[10px] font-semibold uppercase tracking-wider text-text-tertiary">
												<tr>
													<th class="px-3 py-1.5 text-left">Category</th>
													<th class="px-3 py-1.5 text-right">Score</th>
												</tr>
											</thead>
											<tbody>
												<template x-for="(category, idx) in competitor.category_scores" :key="category.name">
													<tr class="border-t border-gray-200/60">
														<td class="px-3 py-1.5 text-[13px] text-text-primary" x-text="category.name"></td>
														<td class="px-3 py-1.5 text-right">
															<div class="flex items-center justify-end gap-2">
																<div class="h-1.5 w-14 overflow-hidden rounded-full bg-gray-200">
																	<div
																		class="h-full rounded-full"
																		:class="{
																			'bg-emerald-500': category.total > 0 && (category.passed / category.total) >= 0.8,
																			'bg-amber-500': category.total > 0 && (category.passed / category.total) >= 0.5 && (category.passed / category.total) < 0.8,
																			'bg-red-500': category.total > 0 && (category.passed / category.total) < 0.5,
																			'bg-gray-300': category.total === 0,
																		}"
																		:style="'width: ' + (category.total > 0 ? Math.round(category.passed / category.total * 100) : 0) + '%'"
																	></div>
																</div>
																<span
																	class="min-w-[2.5rem] text-xs font-medium"
																	:class="{
																		'text-emerald-600': category.total > 0 && (category.passed / category.total) >= 0.8,
																		'text-amber-600': category.total > 0 && (category.passed / category.total) >= 0.5 && (category.passed / category.total) < 0.8,
																		'text-red-600': category.total > 0 && (category.passed / category.total) < 0.5,
																		'text-gray-400': category.total === 0,
																	}"
																	x-text="category.passed + '/' + category.total"
																></span>
															</div>
														</td>
													</tr>
												</template>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</template>

						{{-- Per-module status comparison --}}
						<template x-if="competitor.overall_score !== null && liveOverallScore !== null && Object.keys(competitor.module_statuses).length > 0">
							<div class="mt-5">
								<h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-text-tertiary">Module Comparison</h4>
								<div class="max-h-[300px] overflow-y-auto rounded-lg border border-gray-200 bg-gray-50">
									<table class="w-full">
										<thead class="sticky top-0 bg-gray-100 text-[10px] font-semibold uppercase tracking-wider text-text-tertiary">
											<tr>
												<th class="px-3 py-1.5 text-left">Module</th>
												<th class="px-3 py-1.5 text-center text-indigo-600">You</th>
												<th class="px-3 py-1.5 text-center text-gray-500">Them</th>
											</tr>
										</thead>
										<tbody>
											<template x-for="(modules, category) in comparisonGroups" :key="category">
												<template x-for="(moduleKey, mIdx) in modules" :key="moduleKey">
													<tr class="border-t border-gray-200/60">
														<td class="px-3 py-1.5">
															<template x-if="mIdx === 0">
																<span class="text-[10px] font-semibold uppercase tracking-wider text-text-tertiary" x-text="category"></span>
															</template>
															<div class="text-[13px] text-text-primary" x-text="moduleLabels[moduleKey] || moduleKey"></div>
														</td>
														<td class="px-3 py-1.5 text-center">
															<span class="inline-block h-2.5 w-2.5 rounded-full"
																:class="{
																	'bg-emerald-500': ownModuleStatuses[moduleKey] === 'ok',
																	'bg-amber-500': ownModuleStatuses[moduleKey] === 'warning',
																	'bg-red-500': ownModuleStatuses[moduleKey] === 'bad',
																	'bg-blue-400': ownModuleStatuses[moduleKey] === 'info',
																	'bg-gray-300': !ownModuleStatuses[moduleKey],
																}"
																:title="ownModuleStatuses[moduleKey] || 'N/A'"
															></span>
														</td>
														<td class="px-3 py-1.5 text-center">
															<span class="inline-block h-2.5 w-2.5 rounded-full"
																:class="{
																	'bg-emerald-500': competitor.module_statuses[moduleKey] === 'ok',
																	'bg-amber-500': competitor.module_statuses[moduleKey] === 'warning',
																	'bg-red-500': competitor.module_statuses[moduleKey] === 'bad',
																	'bg-blue-400': competitor.module_statuses[moduleKey] === 'info',
																	'bg-gray-300': !competitor.module_statuses[moduleKey],
																}"
																:title="competitor.module_statuses[moduleKey] || 'N/A'"
															></span>
														</td>
													</tr>
												</template>
											</template>
										</tbody>
									</table>
								</div>
							</div>
						</template>

						{{-- No results message --}}
						<template x-if="competitor.category_scores.length === 0 && competitor.scan_status === 'completed'">
							<p class="text-center text-sm text-text-tertiary">No category data available for this scan.</p>
						</template>

						<template x-if="competitor.scan_status === 'running' || competitor.scan_status === 'pending'">
							<div class="flex items-center justify-center gap-2 py-4">
								<svg class="h-4 w-4 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
								</svg>
								<span class="text-sm text-text-secondary">Scan in progress...</span>
							</div>
						</template>

						<template x-if="competitor.scan_status === 'blocked'">
							<p class="text-center text-sm text-orange-600">This site's bot protection prevented scanning.</p>
						</template>

						<template x-if="competitor.scan_status === 'failed'">
							<p class="text-center text-sm text-red-600">Scan failed due to a connection error. Try rescanning.</p>
						</template>
					</div>
				</div>
			</div>
		</template>
	</div>
</div>
