@props(["moduleResult", "moduleLabels", "aiAvailable" => false, "hasApiKey" => false, "scan" => null])

@php
	$statusValue = $moduleResult->status instanceof \BackedEnum ? $moduleResult->status->value : $moduleResult->status;
	$borderColorMap = array(
		"ok" => "border-l-emerald-400",
		"warning" => "border-l-amber-400",
		"bad" => "border-l-red-400",
		"info" => "border-l-blue-400",
	);
	$borderClass = $borderColorMap[$statusValue] ?? "border-l-gray-300";
	$detailBgMap = array(
		"ok" => "bg-emerald-50",
		"warning" => "bg-amber-50",
		"bad" => "bg-red-50",
		"info" => "bg-blue-50",
	);
	$detailBgClass = $detailBgMap[$statusValue] ?? "bg-gray-50";
	$insightBgMap = array(
		"ok" => "bg-emerald-100",
		"warning" => "bg-amber-100",
		"bad" => "bg-red-100",
		"info" => "bg-blue-100",
	);
	$insightBgClass = $insightBgMap[$statusValue] ?? "bg-gray-100";
	$moduleLabel = $moduleLabels[$moduleResult->module_key] ?? ucwords(str_replace(array("_", "-"), " ", preg_replace("/([a-z])([A-Z])/", "$1 $2", $moduleResult->module_key)));
	$hasRecommendations = !empty($moduleResult->recommendations);
	$visibleFindings = array_filter($moduleResult->findings ?? array(), fn($finding) => ($finding["type"] ?? "") !== "data");
	$findingCount = count($visibleFindings);
	$moduleInsight = config("module-descriptions.{$moduleResult->module_key}");

	$isAiEligible = \App\Services\Ai\Prompts\ModulePromptFactory::isEligible($moduleResult->module_key);

	/* Extract structured data findings by key for WordPress detail components */
	$dataFindings = array();
	foreach ($moduleResult->findings ?? array() as $finding) {
		if (($finding["type"] ?? "") === "data" && isset($finding["key"])) {
			$dataFindings[$finding["key"]] = $finding["value"] ?? null;
		}
	}

	/* Extract a one-line summary from the first meaningful finding */
	$summaryText = "";
	foreach ($visibleFindings as $finding) {
		$summaryText = $finding["message"] ?? "";
		break;
	}
	if (mb_strlen($summaryText) > 120) {
		$summaryText = mb_substr($summaryText, 0, 117) . "...";
	}

	/* AI initial state from server data */
	$initialAiState = $moduleResult->hasAiInsights() ? "success" : "idle";
	$jsonSuggestion = json_encode($moduleResult->ai_suggestion ?? "");

	/* Recommended character limits per module */
	$charLimits = array("titleTag" => 60, "metaDescription" => 160, "h1Tag" => 70);
	$ogTitleLimit = 60;
	$ogDescLimit = 200;

	/* Extract original text for modules that use the OPTIMIZED: format */
	$hasOptimizedFormat = in_array($moduleResult->module_key, array("titleTag", "metaDescription", "h1Tag"), true);
	$originalText = "";
	if ($hasOptimizedFormat) {
		foreach ($moduleResult->findings ?? array() as $finding) {
			if (($finding["type"] ?? "") === "info") {
				$msg = $finding["message"] ?? "";
				if (preg_match('/["""](.+?)["""]/', $msg, $matches)) {
					$originalText = $matches[1];
				}
				break;
			}
		}
	}

	/* Social tags: dual OG_TITLE / OG_DESC before/after display */
	$isSocialTagsOptimized = $moduleResult->module_key === "socialTags";
	$originalOgTitle = "";
	$originalOgDesc = "";
	if ($isSocialTagsOptimized && !empty($dataFindings["socialTagValues"])) {
		$originalOgTitle = $dataFindings["socialTagValues"]["ogTitle"] ?? "";
		$originalOgDesc = $dataFindings["socialTagValues"]["ogDescription"] ?? "";
	}

	/* Current state summaries for enhanced generic display modules */
	$currentStateSummary = "";
	if ($moduleResult->module_key === "schemaOrg" && !empty($dataFindings["schemaTypes"])) {
		$currentStateSummary = implode(", ", $dataFindings["schemaTypes"]);
	} elseif ($moduleResult->module_key === "h2h6Tags" && !empty($dataFindings["headingsList"])) {
		$headingCounts = array();
		foreach ($dataFindings["headingsList"] as $heading) {
			$tag = strtoupper($heading["tag"] ?? "H2");
			$headingCounts[$tag] = ($headingCounts[$tag] ?? 0) + 1;
		}
		$countParts = array();
		foreach ($headingCounts as $tag => $count) {
			$countParts[] = "{$tag}: {$count}";
		}
		$currentStateSummary = implode(", ", $countParts);
	}
@endphp

<div
	class="group overflow-visible rounded-lg border border-border/80 border-l-4 {{ $borderClass }} bg-surface shadow-card transition-all duration-200 hover:shadow-card-hover scroll-mt-4"
	x-data="{
		expanded: false,
		aiState: '{{ $initialAiState }}',
		aiSuggestion: {{ $jsonSuggestion }},
		aiError: null,
		aiReoptimizing: false,
		get parsedPageType() {
			const text = this.aiSuggestion || '';
			const idx = text.indexOf('PAGE_TYPE:');
			if (idx < 0) return '';
			let rest = text.substring(idx + 10);
			rest = rest.replace(/^<\/[^>]+>/, '').trimStart();
			const end = rest.search(/<\/p>|\n/);
			return (end >= 0 ? rest.substring(0, end) : rest).trim();
		},
		async optimizeWithAi() {
			this.aiReoptimizing = this.aiState === 'success';
			this.aiState = 'loading';
			this.aiError = null;
			try {
				const response = await fetch('{{ route("ai.optimize-module", [$scan, $moduleResult]) }}', {
					method: 'POST',
					headers: {
						'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
						'Accept': 'application/json',
					},
				});
				const data = await response.json();
				if (data.success) {
					this.aiSuggestion = data.suggestion;
					this.aiState = 'success';
				} else {
					this.aiError = data.error;
					this.aiState = 'error';
				}
			} catch (err) {
				this.aiError = 'Network error. Please try again.';
				this.aiState = 'error';
			}
			this.aiReoptimizing = false;
		}
	}"
>
	{{-- Clickable header --}}
	<button
		@click="expanded = !expanded; if (expanded) { const cardEl = $root; setTimeout(() => cardEl.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200); }"
		class="flex w-full cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left transition-colors duration-150 hover:bg-gray-50/50"
	>
		<div class="min-w-0 flex-1">
			{{-- Title + badges --}}
			<div class="flex items-center gap-3">
				<h3 class="text-[16px] font-semibold tracking-tight text-text-primary">{{ $moduleLabel }}</h3>
				<x-scan.status-badge :status="$moduleResult->status" />
				<span
					x-show="aiState === 'success'"
					x-cloak
					class="inline-flex items-center gap-1.5 rounded-full bg-ai px-2.5 py-1 text-xs font-medium text-white"
				>
					<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
					</svg>
					AI Optimized
				</span>
			</div>

			{{-- First finding preview (skip for matrix modules where summary is redundant) --}}
			@if($summaryText && $moduleResult->module_key !== "keywordConsistency")
				<p class="mt-1.5 truncate text-[13px] leading-relaxed text-text-tertiary">{{ $summaryText }}</p>
			@endif
		</div>

		{{-- Right: counts + chevron --}}
		<div class="flex shrink-0 items-center gap-4">
			<div class="hidden items-center gap-3 text-xs text-text-tertiary sm:flex">
				@if($findingCount > 0)
					<span class="flex items-center gap-1" title="{{ $findingCount }} {{ $findingCount === 1 ? 'finding' : 'findings' }}">
						<svg class="h-3.5 w-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
						</svg>
						{{ $findingCount }}
					</span>
				@endif
				@if($hasRecommendations)
					<span class="flex items-center gap-1 text-amber-500" title="Has recommendations">
						<svg class="h-3.5 w-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
						</svg>
					</span>
				@endif
				@if($isAiEligible)
					{{-- Outlined sparkle: not yet optimized --}}
					<span x-show="aiState !== 'success'" class="flex items-center text-ai" title="AI optimization available">
						<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
						</svg>
					</span>
					{{-- Filled sparkle: AI optimized --}}
					<span x-show="aiState === 'success'" x-cloak class="flex items-center text-ai" title="AI optimized">
						<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
							<path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258c-.94.236-1.674.97-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.625 2.625 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.625 2.625 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5Z" clip-rule="evenodd" />
						</svg>
					</span>
				@endif
			</div>

			<svg
				:class="expanded ? 'rotate-180' : ''"
				class="h-4 w-4 text-text-tertiary transition-transform duration-200 group-hover:text-text-secondary"
				fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
			>
				<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
			</svg>
		</div>
	</button>

	{{-- robots.txt file URL — always visible --}}
	@if($moduleResult->module_key === "robotsTxt" && !empty($dataFindings["robotsTxtUrl"]))
		<div class="border-t border-border/40 px-5 py-2.5">
			<a href="{{ $dataFindings['robotsTxtUrl'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-sm text-accent transition hover:text-orange-700">
				{{ $dataFindings['robotsTxtUrl'] }}
				<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
			</a>
		</div>
	@endif

	{{-- Expanded detail panel --}}
	<div x-show="expanded" x-collapse>
		<div class="border-t border-border/60 {{ $detailBgClass }} pt-2">
			{{-- Findings --}}
			@php $skipFindingsSection = in_array($moduleResult->module_key, array("wpDetection", "wpPlugins", "wpTheme"), true); @endphp
			@if(!empty($visibleFindings) && !$skipFindingsSection)
				<div class="px-5 pt-4 pb-1">
					<div class="flex items-center gap-2">
						<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
						</svg>
						<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Findings</span>
						<span class="rounded-full bg-border/60 px-2 py-0.5 text-[10px] font-semibold text-text-tertiary">{{ $findingCount }}</span>
					</div>
				</div>
				<div class="space-y-2 px-5 pb-4 pt-2">
					@foreach($visibleFindings as $finding)
						@php
							$findingType = $finding["type"] ?? "info";
							$findingStyleMap = array(
								"ok" => array("icon" => "text-emerald-300", "bg" => "bg-emerald-700", "text" => "text-emerald-50", "path" => "m4.5 12.75 6 6 9-13.5"),
								"warning" => array("icon" => "text-amber-300", "bg" => "bg-amber-600", "text" => "text-amber-50", "path" => "M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"),
								"bad" => array("icon" => "text-red-300", "bg" => "bg-red-700", "text" => "text-red-50", "path" => "M6 18 18 6M6 6l12 12"),
								"info" => array("icon" => "text-slate-400", "bg" => "bg-slate-600", "text" => "text-slate-100", "path" => "m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"),
							);
							$findingStyle = $findingStyleMap[$findingType] ?? $findingStyleMap["info"];
						@endphp
						<div class="flex items-start gap-3 rounded-md {{ $findingStyle['bg'] }} px-4 py-3">
							<svg class="mt-0.5 h-4 w-4 shrink-0 {{ $findingStyle['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="{{ $findingStyle['path'] }}" />
							</svg>
							<span class="text-[13px] leading-relaxed {{ $findingStyle['text'] }}">{{ $finding["message"] ?? "" }}</span>
						</div>
					@endforeach
				</div>
			@endif

			{{-- Module-specific visual displays --}}
			@if($moduleResult->module_key === "h2h6Tags" && !empty($dataFindings["headingsList"]))
				<x-scan.heading-tree :headingsList="$dataFindings['headingsList']" />
			@endif

			@if($moduleResult->module_key === "contentKeywords" && !empty($visibleFindings))
				<x-scan.keyword-checklist :findings="$visibleFindings" />
			@endif

			@if($moduleResult->module_key === "keywordConsistency" && !empty($dataFindings["keywordMatrix"]))
				<x-scan.keyword-matrix :matrix="$dataFindings['keywordMatrix']" />
			@endif

			@if($moduleResult->module_key === "linkAnalysis" && !empty($visibleFindings))
				@php
					$linkStatsFinding = null;
					foreach ($visibleFindings as $lf) {
						if (preg_match('/Total Links:\s*(\d+)\s*\(Internal:\s*(\d+),\s*External:\s*(\d+),\s*Nofollow:\s*(\d+)\)/', $lf["message"] ?? "", $linkMatches)) {
							$linkStatsFinding = array(
								"total" => (int) $linkMatches[1],
								"internal" => (int) $linkMatches[2],
								"external" => (int) $linkMatches[3],
								"nofollow" => (int) $linkMatches[4],
							);
							break;
						}
					}
				@endphp
				@if($linkStatsFinding)
					<div class="border-t border-border/40 px-5 py-4">
						<div class="mb-3 flex items-center gap-2">
							<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
							</svg>
							<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Link Breakdown</span>
						</div>
						<div class="grid grid-cols-3 gap-3">
							<div class="rounded-md px-3 py-2.5 text-center" style="background-color: #973c00">
								<div class="text-lg font-bold text-white">{{ $linkStatsFinding["internal"] }}</div>
								<div class="text-[10px] font-semibold uppercase tracking-wider text-white/80">Internal</div>
							</div>
							<div class="rounded-md px-3 py-2.5 text-center" style="background-color: #973c00">
								<div class="text-lg font-bold text-white">{{ $linkStatsFinding["external"] }}</div>
								<div class="text-[10px] font-semibold uppercase tracking-wider text-white/80">External</div>
							</div>
							<div class="rounded-md px-3 py-2.5 text-center" style="background-color: #973c00">
								<div class="text-lg font-bold text-white">{{ $linkStatsFinding["nofollow"] }}</div>
								<div class="text-[10px] font-semibold uppercase tracking-wider text-white/80">Nofollow</div>
							</div>
						</div>
					</div>
				@endif
			@endif

			@if($moduleResult->module_key === "h1Tag" && !empty($dataFindings["h1Tags"]))
				<div class="border-t border-border/40 px-5 py-4">
					<div class="mb-3 flex items-center gap-2">
						<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M2.243 4.493v7.5l6.004-3.75-6.004-3.75ZM21.757 4.493v7.5l-6.004-3.75 6.004-3.75ZM12 2.25v19.5" />
						</svg>
						<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">H1 Tags Found</span>
						<span class="rounded-full bg-border/60 px-2 py-0.5 text-[10px] font-medium text-text-tertiary">{{ count($dataFindings["h1Tags"]) }}</span>
					</div>
					<div class="space-y-2">
						@foreach($dataFindings["h1Tags"] as $h1Item)
							<div class="flex items-start gap-3 rounded-lg border border-gray-700 bg-gray-800 px-3.5 py-2.5">
								<span class="shrink-0 rounded-md bg-indigo-100 text-indigo-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">H1{{ count($dataFindings["h1Tags"]) > 1 ? " #" . $h1Item["index"] : "" }}</span>
								<span class="text-sm text-white leading-relaxed">{{ $h1Item["text"] }}</span>
								@if($h1Item["imageOnly"])
									<span class="shrink-0 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">Image Only</span>
								@endif
							</div>
						@endforeach
					</div>
				</div>
			@endif

			@if($moduleResult->module_key === "brokenLinks" && !empty($dataFindings["brokenLinks"]))
				@php
					$brokenLinkItems = $dataFindings["brokenLinks"];
					$brokenCount = count(array_filter($brokenLinkItems, fn($item) => $item["severity"] === "broken"));
					$serverErrorCount = count(array_filter($brokenLinkItems, fn($item) => $item["severity"] === "serverError"));
					$unreachableCount = count(array_filter($brokenLinkItems, fn($item) => $item["severity"] === "unreachable"));
				@endphp
				<div class="border-t border-border/40 px-5 py-4">
					<div class="mb-3 flex items-center gap-2">
						<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M13.181 8.68a4.503 4.503 0 0 1 1.903 6.405m-9.768-2.782L3.56 14.06a4.5 4.5 0 0 0 6.364 6.365l3.129-3.129m5.614-5.615 1.757-1.757a4.5 4.5 0 0 0-6.364-6.365l-3.129 3.129m5.614 5.615-2.846 2.846a4.5 4.5 0 0 1-6.364-6.364l2.846-2.846" />
						</svg>
						<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Problem Links</span>
					</div>

					{{-- Summary stat pills --}}
					<div class="mb-4 flex flex-wrap gap-2">
						@if($brokenCount > 0)
							<span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
								<span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
								{{ $brokenCount }} Broken (404/410)
							</span>
						@endif
						@if($serverErrorCount > 0)
							<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
								<span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
								{{ $serverErrorCount }} Server Error
							</span>
						@endif
						@if($unreachableCount > 0)
							<span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
								<span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
								{{ $unreachableCount }} Unreachable
							</span>
						@endif
					</div>

					{{-- Individual broken link rows --}}
					<div class="space-y-2">
						@foreach($brokenLinkItems as $brokenLink)
							@php
								$severityStyles = array(
									"broken" => array("border" => "border-red-200", "bg" => "bg-red-50", "badge" => "bg-red-100 text-red-700", "badgeLabel" => $brokenLink["statusCode"] ? "HTTP " . $brokenLink["statusCode"] : "Broken"),
									"serverError" => array("border" => "border-amber-200", "bg" => "bg-amber-50", "badge" => "bg-amber-100 text-amber-700", "badgeLabel" => $brokenLink["statusCode"] ? "HTTP " . $brokenLink["statusCode"] : "5xx"),
									"unreachable" => array("border" => "border-slate-200", "bg" => "bg-slate-50", "badge" => "bg-slate-100 text-slate-600", "badgeLabel" => "Unreachable"),
								);
								$style = $severityStyles[$brokenLink["severity"]] ?? $severityStyles["unreachable"];
							@endphp
							<div class="flex items-center gap-3 rounded-lg border {{ $style['border'] }} {{ $style['bg'] }} px-3.5 py-2.5">
								<span class="shrink-0 rounded-md {{ $style['badge'] }} px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">{{ $style["badgeLabel"] }}</span>
								<span class="min-w-0 truncate font-mono text-xs text-text-secondary" title="{{ $brokenLink['url'] }}">{{ $brokenLink["url"] }}</span>
							</div>
						@endforeach
					</div>
				</div>
			@endif

			@if($moduleResult->module_key === "schemaValidation" && !empty($dataFindings["schemaValidation"]))
				@php $schemaItems = $dataFindings["schemaValidation"]; @endphp
				<div class="border-t border-border/40 px-5 py-4">
					<div class="mb-3 flex items-center gap-2">
						<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
						</svg>
						<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Schema Validation</span>
					</div>

					<div class="space-y-3">
						@foreach($schemaItems as $schemaItem)
							@php
								$itemStatus = $schemaItem["status"] ?? "ok";
								$statusStyles = array(
									"ok" => array("border" => "border-emerald-200", "bg" => "bg-emerald-50", "badge" => "bg-emerald-100 text-emerald-700", "badgeLabel" => "Pass"),
									"warning" => array("border" => "border-amber-200", "bg" => "bg-amber-50", "badge" => "bg-amber-100 text-amber-700", "badgeLabel" => "Incomplete"),
									"bad" => array("border" => "border-red-200", "bg" => "bg-red-50", "badge" => "bg-red-100 text-red-700", "badgeLabel" => "Failing"),
								);
								$style = $statusStyles[$itemStatus] ?? $statusStyles["warning"];
								$requiredFields = $schemaItem["requiredFields"] ?? array();
								$recommendedFields = $schemaItem["recommendedFields"] ?? array();
								$missingRequired = $schemaItem["missingRequired"] ?? array();
								$missingRecommended = $schemaItem["missingRecommended"] ?? array();
								$nestedIssues = $schemaItem["nestedIssues"] ?? array();
							@endphp
							<div class="rounded-lg border {{ $style['border'] }} {{ $style['bg'] }} overflow-hidden">
								{{-- Header row --}}
								<div class="flex items-center justify-between px-4 py-2.5">
									<div class="flex items-center gap-2.5">
										<span class="rounded-md {{ $style['badge'] }} px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">{{ $style["badgeLabel"] }}</span>
										<span class="text-sm font-semibold text-text-primary">{{ $schemaItem["type"] }}</span>
										<span class="rounded bg-white/60 px-1.5 py-0.5 text-[10px] font-medium text-text-tertiary">{{ $schemaItem["format"] }}</span>
									</div>
									<span class="text-[11px] text-text-secondary">{{ $schemaItem["requiredPresent"] ?? 0 }}/{{ $schemaItem["requiredTotal"] ?? 0 }} required</span>
								</div>

								{{-- Field checklist --}}
								@if(!empty($requiredFields) || !empty($recommendedFields))
									<div class="border-t {{ $style['border'] }} bg-white/40 px-4 py-3">
										@if(!empty($requiredFields))
											<div class="mb-2 text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Required Fields</div>
											<div class="mb-3 flex flex-wrap gap-x-4 gap-y-1.5">
												@foreach($requiredFields as $field)
													@php $isMissing = in_array($field, $missingRequired, true); @endphp
													<div class="flex items-center gap-1.5 text-xs">
														@if($isMissing)
															<svg class="h-3.5 w-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
															<span class="font-medium text-red-700">{{ $field }}</span>
														@else
															<svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
															<span class="text-text-secondary">{{ $field }}</span>
														@endif
													</div>
												@endforeach
											</div>
										@endif

										@if(!empty($recommendedFields))
											<div class="mb-2 text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Recommended Fields</div>
											<div class="flex flex-wrap gap-x-4 gap-y-1.5">
												@foreach($recommendedFields as $field)
													@php $isMissing = in_array($field, $missingRecommended, true); @endphp
													<div class="flex items-center gap-1.5 text-xs">
														@if($isMissing)
															<svg class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
															<span class="font-medium text-amber-700">{{ $field }}</span>
														@else
															<svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
															<span class="text-text-secondary">{{ $field }}</span>
														@endif
													</div>
												@endforeach
											</div>
										@endif

										@if(!empty($nestedIssues))
											<div class="mt-3 border-t {{ $style['border'] }} pt-2.5">
												<div class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Nested Structure Issues</div>
												@foreach($nestedIssues as $nestedIssue)
													<div class="flex items-start gap-1.5 text-xs">
														<svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
														<span class="font-medium text-red-700">{{ $nestedIssue }}</span>
													</div>
												@endforeach
											</div>
										@endif
									</div>
								@endif
							</div>
						@endforeach
					</div>
				</div>
			@endif

			{{-- Structured data displays --}}
			@if(!empty($dataFindings))
				@if($moduleResult->module_key === "wpPlugins" && !empty($dataFindings["detectedPlugins"]))
					<x-scan.wp.plugin-detail-table :detectedPlugins="$dataFindings['detectedPlugins']" />
				@endif

				@if($moduleResult->module_key === "wpTheme" && !empty($dataFindings["themeDetails"]))
					<x-scan.wp.theme-detail-card
						:themeDetails="$dataFindings['themeDetails']"
						:themeVulnerabilities="$dataFindings['themeVulnerabilities'] ?? array()"
					/>
				@endif

				@if($moduleResult->module_key === "wpDetection" && !empty($dataFindings["coreDetails"]))
					<x-scan.wp.core-detail-card
						:coreDetails="$dataFindings['coreDetails']"
						:coreVulnerabilities="$dataFindings['coreVulnerabilities'] ?? array()"
					/>
				@endif

				@if(in_array($moduleResult->module_key, ["coreWebVitalsMobile", "coreWebVitalsDesktop"]) && !empty($dataFindings["cwvMetrics"]))
					<x-scan.cwv-metrics
						:cwvMetrics="$dataFindings['cwvMetrics']"
						:dataSource="$dataFindings['cwvDataSource'] ?? 'lab'"
					/>
				@endif

				@if($moduleResult->module_key === "techStackDetection" && !empty($dataFindings["detectedTechStack"]))
					<div class="border-t border-border/40 px-5 py-4">
						<div class="mb-3 flex items-center gap-2">
							<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0L12 16.5l-5.571-2.25m11.142 0L21.75 16.5 12 21.75 2.25 16.5l4.179-2.25" />
							</svg>
							<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Detected Technologies</span>
						</div>
						<x-scan.tech-stack-display :techStack="$dataFindings['detectedTechStack']" />
					</div>
				@endif

				@if($moduleResult->module_key === "imageAnalysis" && !empty($dataFindings["imageDetails"]))
					<x-scan.image-detail-table :imageDetails="$dataFindings['imageDetails']" />
				@endif

				@if($moduleResult->module_key === "sitemapAnalysis" && !empty($dataFindings["sitemapDetails"]))
					<x-scan.sitemap-detail-table :sitemapDetails="$dataFindings['sitemapDetails']" />
				@endif
			@endif

			{{-- Insight: educational description + recommendations --}}
			@if($moduleInsight || $hasRecommendations)
				<div class="{{ $insightBgClass }} px-5 py-4">
					<div class="mb-2 flex items-center gap-2">
						@if($hasRecommendations)
							<svg class="h-3.5 w-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
							</svg>
							<span class="text-xs font-bold uppercase tracking-wider text-amber-800">Insight</span>
						@else
							<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
							</svg>
							<span class="text-xs font-bold uppercase tracking-wider text-text-secondary">Insight</span>
						@endif
					</div>

					@if($moduleInsight)
						<p class="text-sm leading-relaxed {{ $hasRecommendations ? 'text-text-primary' : 'text-text-secondary' }}">
							{{ $moduleInsight["description"] }}
							@if(!$hasRecommendations && !empty($moduleInsight["passing"]))
								<span class="font-medium text-emerald-600">{{ $moduleInsight["passing"] }}</span>
							@endif
						</p>
					@endif

					@if($hasRecommendations)
						<ul class="mt-3 space-y-4">
							@foreach($moduleResult->recommendations as $recommendation)
								<li class="flex items-start gap-2.5 text-sm leading-relaxed text-text-primary">
									<span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400/60"></span>
									{{ $recommendation }}
								</li>
							@endforeach
						</ul>
					@endif
				</div>
			@endif

			{{-- AI Optimize button (only for eligible modules) --}}
			@if($isAiEligible)
			<div x-show="aiState !== 'success'" class="border-t border-border/60 px-5 py-3">
				@if($hasApiKey)
					{{-- Fully functional: plan access + API key configured --}}
					<button
						x-show="aiState === 'idle' || aiState === 'error'"
						@click.stop="optimizeWithAi()"
						class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-ai px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-ai-hover"
					>
						<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
						</svg>
						AI Optimize
					</button>

					<div
						x-show="aiState === 'loading' && !aiReoptimizing"
						x-cloak
						class="inline-flex items-center gap-2 text-xs font-medium text-ai"
					>
						<svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
						</svg>
						Optimizing with AI&hellip;
					</div>

					<p
						x-show="aiState === 'error' && aiError"
						x-cloak
						class="mt-2 text-xs text-red-600"
						x-text="aiError"
					></p>
				@elseif($aiAvailable)
					{{-- Paid plan but no API key: prompt to configure --}}
					<button
						@click.stop="$dispatch('open-modal', 'configure-api-key')"
						class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-ai px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-ai-hover"
					>
						<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
						</svg>
						AI Optimize
					</button>
				@else
					{{-- Free plan: locked button opens upgrade modal --}}
					<button
						@click.stop="$dispatch('open-modal', 'upgrade-pro')"
						class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-ai/40 px-3 py-1.5 text-xs font-semibold text-white/70 transition hover:bg-ai/60"
					>
						<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
						</svg>
						AI Optimize
						<span class="rounded bg-white/20 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider">Pro</span>
					</button>
				@endif
			</div>

			{{-- AI Suggestion panel (reactive) — imageAnalysis uses per-card display in image-detail-table --}}
			@if($moduleResult->module_key !== "imageAnalysis")
			<div x-show="(aiState === 'success' && aiSuggestion) || aiReoptimizing" x-cloak>
				@if($isSocialTagsOptimized)
				{{-- Social Tags: dual og:title + og:description before/after display --}}
				<div
					class="relative border-t border-ai-border bg-ai px-5 py-5"
					x-data="{
						originalOgTitle: @js($originalOgTitle),
						originalOgDesc: @js($originalOgDesc),
						get parsedOgTitle() {
							const text = this.aiSuggestion || '';
							const match = text.match(/^OG_TITLE:\s*(.+)$/m);
							return match ? match[1].trim() : '';
						},
						get parsedOgDesc() {
							const text = this.aiSuggestion || '';
							const match = text.match(/^OG_DESC:\s*(.+)$/m);
							return match ? match[1].trim() : '';
						},
						get parsedSocialExplanation() {
							const text = this.aiSuggestion || '';
							const idx = text.indexOf('OG_DESC:');
							if (idx < 0) return text.replace(/^PAGE_TYPE:[^\n]*\n?/im, '').replace(/^OG_TITLE:[^\n]*\n?/im, '').trim();
							const lineEnd = text.indexOf('\n', idx);
							if (lineEnd < 0) return '';
							return text.substring(lineEnd + 1).trim();
						},
					}"
				>
					<x-scan.ai-reoptimizing-overlay />

					<div class="flex items-center justify-between mb-4">
						<div class="flex items-center gap-2">
							<div class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-white/20">
								<svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
								</svg>
							</div>
							<span class="text-xs font-semibold uppercase tracking-wider text-white/80">AI Suggestion</span>
						</div>
						<button
							x-show="!aiReoptimizing"
							@click.stop="optimizeWithAi()"
							class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-white/15 px-2.5 py-1 text-[11px] font-semibold text-white/80 transition hover:bg-white/25 hover:text-white"
						>
							<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
							</svg>
							Re-optimize
						</button>
					</div>

					{{-- og:title before/after --}}
					<div class="mb-3">
						<div class="flex items-center justify-between mb-1.5">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-white/50">og:title — Original</span>
							<template x-if="originalOgTitle">
								<span class="text-[11px] font-medium text-white/50" x-text="originalOgTitle.length + '/{{ $ogTitleLimit }} chars'"></span>
							</template>
						</div>
						<div class="rounded-md bg-white/10 px-3.5 py-2.5">
							<template x-if="originalOgTitle">
								<p class="text-sm leading-relaxed text-white/70" x-text="originalOgTitle"></p>
							</template>
							<template x-if="!originalOgTitle">
								<p class="text-sm italic leading-relaxed text-white/40">Not present on page</p>
							</template>
						</div>
					</div>
					<template x-if="parsedOgTitle">
						<x-scan.copyable-optimized-box label="og:title — Optimized" alpineValue="parsedOgTitle" :charLimit="$ogTitleLimit" />
					</template>

					{{-- og:description before/after --}}
					<div class="mb-3">
						<div class="flex items-center justify-between mb-1.5">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-white/50">og:description — Original</span>
							<template x-if="originalOgDesc">
								<span class="text-[11px] font-medium text-white/50" x-text="originalOgDesc.length + '/{{ $ogDescLimit }} chars'"></span>
							</template>
						</div>
						<div class="rounded-md bg-white/10 px-3.5 py-2.5">
							<template x-if="originalOgDesc">
								<p class="text-sm leading-relaxed text-white/70" x-text="originalOgDesc"></p>
							</template>
							<template x-if="!originalOgDesc">
								<p class="text-sm italic leading-relaxed text-white/40">Not present on page</p>
							</template>
						</div>
					</div>
					<template x-if="parsedOgDesc">
						<x-scan.copyable-optimized-box label="og:description — Optimized" alpineValue="parsedOgDesc" :charLimit="$ogDescLimit" />
					</template>

					{{-- Page type classification --}}
					<template x-if="parsedPageType">
						<div class="mb-3 flex items-center gap-1.5">
							<svg class="h-3 w-3 text-white/30" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
								<path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
							</svg>
							<span class="text-[12px] italic text-white">Optimized as: <span class="not-italic font-medium text-white" x-text="parsedPageType"></span></span>
						</div>
					</template>

					{{-- Explanation --}}
					<template x-if="parsedSocialExplanation">
						<div class="rounded-md bg-white/5 px-3.5 py-3">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-white/50">Why This Works</span>
							<div class="ai-prose overflow-x-hidden break-words mt-1.5 text-sm leading-relaxed text-white/80" x-html="parsedSocialExplanation"></div>
						</div>
					</template>
				</div>
				@elseif($hasOptimizedFormat)
				{{-- Structured display for titleTag / metaDescription / h1Tag --}}
				<div
					class="relative border-t border-ai-border bg-ai px-5 py-5"
					x-data="{
						originalText: @js($originalText),
						get parsedOptimized() {
							const text = this.aiSuggestion || '';
							const idx = text.indexOf('OPTIMIZED:');
							if (idx < 0) return '';
							let rest = text.substring(idx + 10);
							rest = rest.replace(/^<\/[^>]+>/, '').trimStart();
							const end = rest.search(/<\/p>|\n/);
							return (end >= 0 ? rest.substring(0, end) : rest).trim();
						},
						get parsedExplanation() {
							const text = this.aiSuggestion || '';
							const idx = text.indexOf('OPTIMIZED:');
							if (idx < 0) return text;
							const rest = text.substring(idx);
							const pEnd = rest.indexOf('</p>');
							const nlEnd = rest.indexOf('\n');
							let skipTo;
							if (pEnd >= 0 && (nlEnd < 0 || pEnd <= nlEnd)) { skipTo = idx + pEnd + 4; }
							else if (nlEnd >= 0) { skipTo = idx + nlEnd + 1; }
							else { return ''; }
							return text.substring(skipTo).trim();
						},
					}"
				>
					<x-scan.ai-reoptimizing-overlay />

					<div class="flex items-center justify-between mb-4">
						<div class="flex items-center gap-2">
							<div class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-white/20">
								<svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
								</svg>
							</div>
							<span class="text-xs font-semibold uppercase tracking-wider text-white/80">AI Suggestion</span>
						</div>
						<button
							x-show="!aiReoptimizing"
							@click.stop="optimizeWithAi()"
							class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-white/15 px-2.5 py-1 text-[11px] font-semibold text-white/80 transition hover:bg-white/25 hover:text-white"
						>
							<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
							</svg>
							Re-optimize
						</button>
					</div>

					{{-- Original text --}}
					<div class="mb-3">
						<div class="flex items-center justify-between mb-1.5">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-white/50">Original</span>
							<template x-if="originalText">
								<span class="text-[11px] font-medium text-white/50" x-text="originalText.length + '/{{ $charLimits[$moduleResult->module_key] ?? 0 }} chars'"></span>
							</template>
						</div>
						<div class="rounded-md bg-white/10 px-3.5 py-2.5">
							<template x-if="originalText">
								<p class="text-sm leading-relaxed text-white/70" x-text="originalText"></p>
							</template>
							<template x-if="!originalText">
								<p class="text-sm italic leading-relaxed text-white/40">Not present on page</p>
							</template>
						</div>
					</div>

					{{-- Optimized text --}}
					<template x-if="parsedOptimized">
						<x-scan.copyable-optimized-box label="Optimized" alpineValue="parsedOptimized" :charLimit="$charLimits[$moduleResult->module_key] ?? 0" />
					</template>

					{{-- Page type classification --}}
					<template x-if="parsedPageType">
						<div class="mb-3 flex items-center gap-1.5">
							<svg class="h-3 w-3 text-white/30" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
								<path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
							</svg>
							<span class="text-[12px] italic text-white">Optimized as: <span class="not-italic font-medium text-white" x-text="parsedPageType"></span></span>
						</div>
					</template>

					{{-- Explanation --}}
					<template x-if="parsedExplanation">
						<div class="rounded-md bg-white/5 px-3.5 py-3">
							<span class="text-[11px] font-semibold uppercase tracking-wider text-white/50">Why This Works</span>
							<div class="ai-prose overflow-x-hidden break-words mt-1.5 text-sm leading-relaxed text-white/80" x-html="parsedExplanation"></div>
						</div>
					</template>
				</div>
				@else
				{{-- Default plain text display for other modules --}}
				<div
					class="relative border-t border-ai-border bg-ai px-5 py-5"
					x-data="{
						get strippedSuggestion() {
							const text = this.aiSuggestion || '';
							return text.replace(/<p>\s*(?:<[^>]+>\s*)*PAGE_TYPE:[^<]*(?:<\/[^>]+>\s*)*<\/p>\s*/i, '').replace(/^PAGE_TYPE:[^\n]*\n?/im, '').trim();
						},
					}"
				>
					<x-scan.ai-reoptimizing-overlay />

					<div class="mb-3 flex items-center justify-between">
						<div class="flex items-center gap-2">
							<div class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-white/20">
								<svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
								</svg>
							</div>
							<span class="text-xs font-semibold uppercase tracking-wider text-white/80">AI Suggestion</span>
						</div>
						<button
							x-show="!aiReoptimizing"
							@click.stop="optimizeWithAi()"
							class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-white/15 px-2.5 py-1 text-[11px] font-semibold text-white/80 transition hover:bg-white/25 hover:text-white"
						>
							<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
							</svg>
							Re-optimize
						</button>
					</div>
					{{-- Current State summary for enhanced generic modules --}}
					@if($currentStateSummary)
						<div class="mb-3">
							<div class="mb-1.5">
								<span class="text-[11px] font-semibold uppercase tracking-wider text-white/50">Current State</span>
							</div>
							<div class="rounded-md bg-white/10 px-3.5 py-2.5">
								<p class="text-sm leading-relaxed text-white/70">{{ $currentStateSummary }}</p>
							</div>
						</div>
					@endif

					{{-- Page type classification --}}
					<template x-if="parsedPageType">
						<div class="mb-3 flex items-center gap-1.5">
							<svg class="h-3 w-3 text-white/30" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
								<path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
							</svg>
							<span class="text-[12px] italic text-white">Identified as: <span class="not-italic font-medium text-white" x-text="parsedPageType"></span></span>
						</div>
					</template>

					<div class="ai-prose overflow-x-hidden break-words text-sm leading-relaxed text-white/90" x-html="strippedSuggestion"></div>
				</div>
				@endif
			</div>
			@endif
			@endif
		</div>
	</div>
</div>
