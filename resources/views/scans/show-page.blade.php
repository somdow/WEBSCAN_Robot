<x-app-layout>
	<x-slot name="header">
		<div class="flex items-start justify-between gap-4">
			<x-scan.page-header
				:projectName="$scan->project->name"
				subtitle="Page Analysis"
				:url="$scanPage->url"
				:urlDisplay="$scanPage->url"
				:keywords="$scan->project->target_keywords ?? array()"
				:breadcrumbItems="array(
					array('label' => 'Home', 'url' => route('dashboard')),
					array('label' => 'Projects', 'url' => route('projects.index')),
					array('label' => $scan->project->name, 'url' => route('projects.show', $scan->project)),
					array('label' => 'Site Crawl Report', 'url' => route('projects.show', array('project' => $scan->project, 'scan' => $scan))),
					array('label' => $scanPage->url, 'externalUrl' => $scanPage->url),
				)"
			/>
			<div class="mt-6 flex flex-col items-end gap-3">
				@if($scanPage->page_score !== null)
					@php
						$circumference = config("scan-ui.score_ring_circumference");
						$scoreBgClass = match (true) {
							$scanPage->page_score >= 80 => "bg-emerald-100 border-emerald-300",
							$scanPage->page_score >= 50 => "bg-amber-100 border-amber-300",
							default => "bg-red-100 border-red-300",
						};
					@endphp
					<div class="rounded-xl border {{ $scoreBgClass }} p-5 shadow-card" style="width: 300px;">
						<div class="mx-auto relative" style="height: 130px; width: 130px;">
							<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80">
								<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
								<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round" class="{{ $scanPage->scoreStrokeClass() }}" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference * (1 - $scanPage->page_score / 100) }}" />
							</svg>
							<div class="absolute inset-0 flex flex-col items-center justify-center">
								<span class="text-2xl font-extrabold {{ $scanPage->scoreColorClass() }}">{{ $scanPage->page_score }}</span>
								<span class="text-[10px] font-medium" style="color: #555;">/ 100</span>
								<span class="text-[9px] font-semibold uppercase tracking-wider mt-0.5" style="color: #555;">Page Score</span>
							</div>
						</div>
					</div>
				@endif
			</div>
		</div>
	</x-slot>

	@if($scan->isComplete() && $groupedResults->isNotEmpty())
		@php
			$passedCount = $statusCounts["ok"] ?? 0;
			$warningCount = $statusCounts["warning"] ?? 0;
			$failedCount = $statusCounts["bad"] ?? 0;
			$infoCount = $statusCounts["info"] ?? 0;
			$totalModules = $passedCount + $warningCount + $failedCount + $infoCount;

			$sidebarGroups = config("scan-ui.sidebar_groups");
			$categoryIcons = config("scan-ui.category_icons");
			$categoryDescriptions = config("scan-ui.category_descriptions");
			$statusFilterConfig = config("scan-ui.status_filters");

			/* Extract SERP Preview module from Extras so it gets its own sidebar panel */
			$serpPreview = app(\App\Services\Scanning\ScanViewDataService::class)->extractSerpPreview($groupedResults);
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

		<div x-data="{ activeCategory: '{{ $defaultCategory }}', statusFilter: '' }">

		{{-- Filter cards --}}
		@if($scanPage->page_score !== null)
			<x-scan.status-filter-cards :passedCount="$passedCount" :warningCount="$warningCount" :failedCount="$failedCount" class="mb-12 grid-cols-1 sm:grid-cols-3" />
		@endif

		{{-- Sidebar + Module Cards --}}
			{{-- Category view (default — 2-column sidebar + content) --}}
			<div x-show="!statusFilter" class="flex flex-row-reverse min-h-[500px] gap-12">
				{{-- Left sidebar --}}
				<div class="w-72 shrink-0">
					<nav class="sticky top-6 max-h-[calc(100vh-3rem)] overflow-y-auto pb-3">
						@foreach($sidebarGroups as $groupName => $groupData)
							@php
								$groupHasResults = false;
								foreach ($groupData["categories"] as $cat) {
									if ($groupedResults->has($cat)) {
										$groupHasResults = true;
										break;
									}
								}
							@endphp

							@if($groupHasResults)
								<div class="px-4 pb-1 {{ !$loop->first ? 'mt-3 pt-2' : '' }}">
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
											@click="activeCategory = '{{ $categoryName }}'"
											:class="activeCategory === '{{ $categoryName }}'
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
							clickAction="activeCategory = 'serp-preview'"
						/>
					</nav>
				</div>

				{{-- Right content panel --}}
				<div class="flex-1 min-w-0 overflow-visible">
					{{-- SERP Preview panel --}}
					@if($serpPreviewResult && $serpData)
						<div x-show="activeCategory === 'serp-preview'" x-cloak>
							<x-scan.serp-preview-panel :serpData="$serpData" :moduleResult="$serpPreviewResult" />
						</div>
					@endif

					@foreach($groupedResults as $categoryName => $moduleResults)
						<div x-show="activeCategory === '{{ $categoryName }}'" x-cloak>
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
									<x-scan.module-card :moduleResult="$moduleResult" :moduleLabels="$moduleLabels" :aiAvailable="$aiAvailable" :hasApiKey="$hasApiKey" :scan="$scan" />
								@endforeach
							</div>
						</div>
					@endforeach
				</div>
			</div>

			{{-- Status filter view (1-column — replaces sidebar layout when a filter card is clicked) --}}
			@foreach($statusGroupedResults as $statusValue => $statusModuleResults)
				@php $filterConfig = $statusFilterConfig[$statusValue] ?? null; @endphp
				@if($filterConfig)
					<div x-show="statusFilter === '{{ $statusValue }}'" x-cloak>
						<div class="mb-6 flex items-center gap-3">
							<svg class="h-6 w-6 {{ $filterConfig['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="{{ $filterConfig['iconPath'] }}" />
							</svg>
							<h2 class="text-[1.8rem] font-bold leading-tight tracking-tight text-text-primary">{{ $filterConfig["label"] }}</h2>
							<span class="rounded-full {{ $filterConfig['bgColor'] }} {{ $filterConfig['borderColor'] }} border px-2.5 py-0.5 text-xs font-medium text-text-secondary">{{ $statusModuleResults->count() }} {{ $statusModuleResults->count() === 1 ? "module" : "modules" }}</span>
							<x-scan.clear-filter-button />
						</div>

						<div class="space-y-3">
							@foreach($statusModuleResults as $moduleResult)
								<x-scan.module-card :moduleResult="$moduleResult" :moduleLabels="$moduleLabels" :aiAvailable="$aiAvailable" :hasApiKey="$hasApiKey" :scan="$scan" />
							@endforeach
						</div>
					</div>
				@endif
			@endforeach
		</div>
	@elseif($scan->isComplete())
		<div class="rounded-lg border {{ $scanPage->error_message ? 'border-red-200 bg-red-50' : 'border-border bg-surface' }} px-6 py-12 text-center shadow-card">
			@if($scanPage->error_message)
				<svg class="mx-auto h-10 w-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
				</svg>
				<p class="mt-3 text-sm font-medium text-red-800">This page could not be analyzed</p>
				<p class="mt-1.5 text-sm text-red-600">{{ $scanPage->error_message }}</p>
			@else
				<p class="text-sm text-text-secondary">No module results were generated for this page.</p>
			@endif
		</div>
	@endif

	{{-- AI modal --}}
	@if(!$hasApiKey)
		<x-configure-api-key-modal />
	@endif
</x-app-layout>
