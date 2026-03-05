{{-- Two-column sidebar + results layout for single-page scans --}}
{{-- Expects parent x-data scope with: activeCategory, statusFilter --}}
{{-- Expects variables: $groupedResults, $statusGroupedResults, $moduleLabels, $hasApiKey, $scan --}}

@php
	$sidebarGroups = config("scan-ui.sidebar_groups");

	$categoryIcons = config("scan-ui.category_icons");
	$categoryDescriptions = config("scan-ui.category_descriptions");
	$statusFilterConfig = config("scan-ui.status_filters");

	/* Extract SERP Preview module from Extras so it gets its own sidebar panel */
	$serpPreview = app(\App\Services\Scanning\ScanViewDataService::class)->extractSerpPreview($groupedResults, $statusGroupedResults);
	$serpPreviewResult = $serpPreview["result"];
	$serpData = $serpPreview["data"];

	$availableCategories = $groupedResults->keys()->toArray();
	$defaultCategory = "";
	foreach ($sidebarGroups as $groupData) {
		foreach ($groupData["categories"] as $cat) {
			if (in_array($cat, $availableCategories, true)) {
				$defaultCategory = $cat;
				break 2;
			}
		}
	}
@endphp

<div id="results-panel" class="overflow-visible scroll-mt-4">
	<div class="flex flex-row-reverse min-h-[500px] gap-12">
		{{-- Left sidebar --}}
		<div class="w-72 shrink-0">
			<nav class="sticky top-6 max-h-[calc(100vh-3rem)] overflow-y-auto pb-3">
				@foreach($sidebarGroups as $groupName => $groupData)
					@php
						$groupHasResults = false;
						foreach ($groupData["categories"] as $cat) {
							if ($groupedResults->has($cat)) { $groupHasResults = true; break; }
						}
					@endphp

					@if($groupHasResults)
						<div class="px-4 pb-1 {{ !$loop->first ? 'mt-3 pt-2' : 'pt-1' }}">
							<div class="flex items-center gap-2">
								<svg class="h-4 w-4 text-text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="{{ $groupData['icon'] }}" />
								</svg>
								<span class="text-[18px] font-bold uppercase tracking-widest text-text-primary">{{ $groupName }}</span>
							</div>
						</div>

						@foreach($groupData["categories"] as $categoryName)
							@if($groupedResults->has($categoryName))
								@php
									$categoryModules = $groupedResults->get($categoryName);
									$categoryTotal = $categoryModules->count();
									$categoryPassed = $categoryModules->filter(fn($r) => $r->status->value === "ok")->count();
									$categoryIcon = $categoryIcons[$categoryName] ?? "";
									$isExtras = $categoryName === "Extras";
									$passRate = $categoryTotal > 0 ? $categoryPassed / $categoryTotal : 0;
									$scoreColor = $isExtras
										? "bg-blue-50 text-blue-600"
										: match (true) {
											$passRate >= 1.0 => "bg-emerald-100 text-emerald-700",
											$passRate > 0.5 => "bg-amber-100 text-amber-700",
											default => "bg-red-100 text-red-700",
										};
									$categoryHasAi = $categoryModules->contains(
										fn($r) => \App\Services\Ai\Prompts\ModulePromptFactory::isEligible($r->module_key)
									);
								@endphp
								<button
									@click="statusFilter = ''; activeCategory = '{{ $categoryName }}'; document.getElementById('results-panel').scrollIntoView({ behavior: 'smooth' })"
									:class="!statusFilter && activeCategory === '{{ $categoryName }}'
										? 'bg-gray-300 text-text-primary font-medium'
										: 'text-text-secondary hover:bg-gray-50 hover:text-text-primary'"
									class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg px-5 py-2.5 text-left text-sm transition-colors duration-150"
								>
									<div class="flex items-center gap-2.5 min-w-0">
										<svg class="h-4 w-4 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="{{ $categoryIcon }}" />
										</svg>
										<span class="truncate">{{ $categoryName }}</span>
										@if($categoryHasAi)
											<svg class="h-3 w-3 shrink-0 text-ai" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" title="AI optimization available">
												<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
											</svg>
										@endif
									</div>
									<span class="shrink-0 rounded-full {{ $scoreColor }} px-2 py-0.5 text-[11px] font-medium transition-colors">{{ $isExtras ? "Info" : "{$categoryPassed}/{$categoryTotal}" }}</span>
								</button>
							@endif
						@endforeach

					@endif
				@endforeach

				<x-scan.serp-sidebar-button
					:serpPreviewResult="$serpPreviewResult"
					:serpData="$serpData"
					:groupedResults="$groupedResults"
					:sidebarGroups="$sidebarGroups"
					clickAction="statusFilter = ''; activeCategory = 'serp-preview'; document.getElementById('results-panel').scrollIntoView({ behavior: 'smooth' })"
					activeExpression="!statusFilter && activeCategory === 'serp-preview'"
				/>
			</nav>
		</div>

		{{-- Right content panel --}}
		<div class="flex-1 min-w-0 overflow-visible">
			{{-- SERP Preview panel --}}
			@if($serpPreviewResult && $serpData)
				<div x-show="!statusFilter && activeCategory === 'serp-preview'" x-cloak>
					<x-scan.serp-preview-panel :serpData="$serpData" :moduleResult="$serpPreviewResult" />
				</div>
			@endif

			{{-- Category view (default) --}}
			@foreach($groupedResults as $categoryName => $moduleResults)
				<div x-show="!statusFilter && activeCategory === '{{ $categoryName }}'" x-cloak>
					<div class="mb-6">
						<div class="flex items-center gap-3">
							<svg class="h-6 w-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="{{ $categoryIcons[$categoryName] ?? '' }}" />
							</svg>
							<h2 class="text-[1.8rem] font-bold leading-tight tracking-tight text-text-primary">{{ $categoryName }}</h2>
							<span class="rounded-full bg-border/60 px-2.5 py-0.5 text-xs font-medium text-text-tertiary">{{ $moduleResults->count() }} {{ $moduleResults->count() === 1 ? "module" : "modules" }}</span>
						</div>
						@if(!empty($categoryDescriptions[$categoryName]))
							<p class="mt-1.5 text-sm text-text-secondary" style="padding-left: 38px;">{{ $categoryDescriptions[$categoryName] }}</p>
						@endif
					</div>

					<div class="space-y-3">
						@foreach($moduleResults as $moduleResult)
							<x-scan.module-card :moduleResult="$moduleResult" :moduleLabels="$moduleLabels":hasApiKey="$hasApiKey" :scan="$scan" />
						@endforeach
					</div>
				</div>
			@endforeach

			{{-- Status filter view --}}
			@foreach($statusGroupedResults as $statusValue => $moduleResults)
				@php $filterConfig = $statusFilterConfig[$statusValue] ?? null; @endphp
				@if($filterConfig)
					<div x-show="statusFilter === '{{ $statusValue }}'" x-cloak>
						<div class="mb-6 flex items-center gap-3">
							<svg class="h-6 w-6 {{ $filterConfig['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="{{ $filterConfig['iconPath'] }}" />
							</svg>
							<h2 class="text-[1.8rem] font-bold leading-tight tracking-tight text-text-primary">{{ $filterConfig["label"] }}</h2>
							<span class="rounded-full {{ $filterConfig['bgColor'] }} {{ $filterConfig['borderColor'] }} border px-2.5 py-0.5 text-xs font-medium text-text-secondary">{{ $moduleResults->count() }} {{ $moduleResults->count() === 1 ? "module" : "modules" }}</span>
							<x-scan.clear-filter-button afterClear="activeCategory = '{{ $defaultCategory }}'; $nextTick(() => document.getElementById('results-panel').scrollIntoView({ behavior: 'smooth' }))" />
						</div>

						<div class="space-y-3">
							@foreach($moduleResults as $moduleResult)
								<x-scan.module-card :moduleResult="$moduleResult" :moduleLabels="$moduleLabels":hasApiKey="$hasApiKey" :scan="$scan" />
							@endforeach
						</div>
					</div>
				@endif
			@endforeach
		</div>
	</div>
</div>
