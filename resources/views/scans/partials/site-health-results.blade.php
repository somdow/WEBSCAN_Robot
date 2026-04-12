{{-- Site Health tab — filtered to infrastructure/platform categories only --}}
{{-- Expects parent x-data scope with: healthSection --}}
{{-- Expects variables: $scan, $scanViewData, $hasApiKey --}}

@php
	$siteHealthCategories = array(
		"Core Web Vitals",
		"Usability & Performance",
		"Security",
		"Analytics",
		"Technology Stack",
		"Utility",
		"Extras",
		"WordPress",
	);

	$allGrouped = $scanViewData["groupedResults"] ?? collect();

	$healthGrouped = $allGrouped->filter(
		fn($modules, $categoryName) => in_array($categoryName, $siteHealthCategories, true)
	);

	$categoryIcons = config("scan-ui.category_icons");
	$categoryDescriptions = config("scan-ui.category_descriptions");
	$moduleLabels = $scanViewData["moduleLabels"] ?? array();

	$defaultHealthSection = $healthGrouped->keys()->first() ?? "";
@endphp

@if($healthGrouped->isEmpty())
	<div class="rounded-lg border border-border bg-surface px-6 py-12 text-center shadow-card">
		<p class="text-sm text-text-secondary">No site health modules found for this scan.</p>
	</div>
@else
	<div x-init="if (!healthSection) healthSection = '{{ $defaultHealthSection }}'">
		<div class="flex flex-row-reverse min-h-[500px] gap-12">
			{{-- Sidebar --}}
			<div class="w-72 shrink-0">
				<nav class="sticky top-6 max-h-[calc(100vh-3rem)] overflow-y-auto pb-3">
					@foreach($healthGrouped as $categoryName => $categoryModules)
						@php
							$categoryTotal = $categoryModules->count();
							$categoryPassed = $categoryModules->filter(fn($r) => $r->status->value === "ok")->count();
							$categoryIcon = $categoryIcons[$categoryName] ?? "";
							$isInfoOnly = in_array($categoryName, array("Extras", "Technology Stack"), true);
							$passRate = $categoryTotal > 0 ? $categoryPassed / $categoryTotal : 0;
							$scoreColor = $isInfoOnly
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
							@click="healthSection = '{{ $categoryName }}'"
							:class="healthSection === '{{ $categoryName }}' ? 'bg-gray-300 text-text-primary font-medium' : 'text-text-secondary hover:bg-gray-50 hover:text-text-primary'"
							class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg px-5 py-2.5 text-left text-sm transition-colors duration-150"
						>
							<div class="flex items-center gap-2.5 min-w-0">
								<svg class="h-4 w-4 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $categoryIcon }}" /></svg>
								<span class="truncate">{{ $categoryName }}</span>
								@if($categoryHasAi)
									<svg class="h-3 w-3 shrink-0 text-ai" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" title="AI optimization available">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
									</svg>
								@endif
							</div>
							<span class="shrink-0 rounded-full {{ $scoreColor }} px-2 py-0.5 text-[11px] font-medium transition-colors">{{ $isInfoOnly ? "Info" : "{$categoryPassed}/{$categoryTotal}" }}</span>
						</button>
					@endforeach
				</nav>
			</div>

			{{-- Content panels --}}
			<div class="flex-1 min-w-0 overflow-visible">
				@foreach($healthGrouped as $categoryName => $moduleResults)
					<div x-show="healthSection === '{{ $categoryName }}'" x-cloak>
						<div class="mb-6">
							<div class="flex items-center gap-3">
								<svg class="h-6 w-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $categoryIcons[$categoryName] ?? '' }}" /></svg>
								<h2 class="text-[1.8rem] font-bold leading-tight tracking-tight text-text-primary">{{ $categoryName }}</h2>
								<span class="rounded-full bg-border/60 px-2.5 py-0.5 text-xs font-medium text-text-tertiary">{{ $moduleResults->count() }} {{ Str::plural("module", $moduleResults->count()) }}</span>
							</div>
							@if(!empty($categoryDescriptions[$categoryName]))
								<p class="mt-1.5 text-sm text-text-secondary" style="padding-left: 38px;">{{ $categoryDescriptions[$categoryName] }}</p>
							@endif
						</div>
						<div class="space-y-3">
							@foreach($moduleResults as $moduleResult)
								<x-scan.module-card :moduleResult="$moduleResult" :moduleLabels="$moduleLabels" :hasApiKey="$hasApiKey" :scan="$scan" />
							@endforeach
						</div>
					</div>
				@endforeach
			</div>
		</div>
	</div>
@endif
