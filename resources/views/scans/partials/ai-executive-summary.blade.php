{{-- AI Executive Summary — on-demand generation with loading states --}}
{{-- Expects variables: $scan, $hasApiKey --}}

@php
	$existingSummary = $scan->ai_executive_summary;
	$aiProviderKey = auth()->user()->ai_provider ?? config("ai.default_provider");
	$aiDisplayNames = array("gemini" => "Gemini", "openai" => "ChatGPT", "anthropic" => "Claude");
	$aiModelName = $aiDisplayNames[$aiProviderKey] ?? "AI";
@endphp
<div
	x-data="{
		summaryState: '{{ $existingSummary ? "loaded" : "idle" }}',
		summaryData: {{ $existingSummary ? \Illuminate\Support\Js::from($existingSummary) : 'null' }},
		summaryError: null,
		loadingMessage: '',
		waitingMessages: [
			'Asking MODEL to read between the lines\u2026',
			'MODEL is crunching the numbers\u2026',
			'Brewing a fresh pot of insights with MODEL\u2026',
			'MODEL is connecting the dots\u2026',
			'Teaching MODEL about your website\u2026',
			'MODEL is putting on its reading glasses\u2026',
			'Letting MODEL work its magic\u2026',
			'MODEL is deep in thought\u2026',
			'Consulting MODEL for the bigger picture\u2026',
			'MODEL is drafting your executive briefing\u2026',
			'Giving MODEL a moment to think\u2026',
			'MODEL is reviewing your site like a pro\u2026',
			'Warming up MODEL for your summary\u2026',
			'MODEL is piecing together the puzzle\u2026',
			'Sit tight \u2014 MODEL is on the case\u2026',
			'MODEL is scanning every pixel of your site\u2026',
			'Pouring MODEL a coffee while it works\u2026',
			'MODEL is writing you a masterpiece\u2026',
			'Hold tight \u2014 MODEL is almost there\u2026',
			'MODEL is separating the signal from the noise\u2026',
			'Giving MODEL the grand tour of your site\u2026',
			'MODEL is doing the heavy lifting\u2026',
			'MODEL just said \u2018interesting\u2019 out loud\u2026',
			'MODEL is channeling its inner SEO guru\u2026',
			'Sharpening MODEL\u2019s pencils for your report\u2026',
			'MODEL is reading your site cover to cover\u2026',
			'Almost there \u2014 MODEL is dotting the i\u2019s\u2026',
			'MODEL is turning data into wisdom\u2026',
			'Handing MODEL a magnifying glass\u2026',
			'MODEL is polishing your summary to perfection\u2026',
		],
		pickLoadingMessage() {
			const model = '{{ $aiModelName }}';
			const messages = this.waitingMessages;
			const message = messages[Math.floor(Math.random() * messages.length)];
			this.loadingMessage = message.replaceAll('MODEL', model);
		},
		async generateSummary() {
			this.pickLoadingMessage();
			this.summaryState = 'loading';
			this.summaryError = null;
			try {
				const response = await fetch('{{ route("ai.executive-summary", $scan) }}', {
					method: 'POST',
					headers: {
						'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
						'Accept': 'application/json',
					},
				});
				const data = await response.json();
				if (data.success) {
					this.summaryData = data.summary;
					this.summaryState = 'loaded';
				} else {
					this.summaryError = data.error;
					this.summaryState = 'error';
				}
			} catch (err) {
				this.summaryError = 'Network error. Please try again.';
				this.summaryState = 'error';
			}
		}
	}"
	x-on:ai-generate-summary.window="if (summaryState !== 'loading') { generateSummary() }"
	class="mb-8"
>
	{{-- Error message (trigger button moved to page header nav) --}}
	<p x-show="summaryState === 'error'" x-cloak class="text-xs text-red-600" x-text="summaryError"></p>

	{{-- Loading --}}
	<div x-show="summaryState === 'loading'" x-cloak class="flex items-center gap-3 rounded-lg border border-ai/20 bg-ai-light px-5 py-4">
		<svg class="h-5 w-5 animate-spin text-ai" fill="none" viewBox="0 0 24 24">
			<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
			<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
		</svg>
		<span class="text-sm font-medium text-ai" x-text="loadingMessage"></span>
	</div>

	{{-- Loaded: collapsible summary card with localStorage persistence --}}
	<div
		x-show="summaryState === 'loaded' && summaryData"
		x-cloak
		x-data="{
			expanded: JSON.parse(localStorage.getItem('aiSummaryExpanded') ?? 'false'),
			toggle() {
				this.expanded = !this.expanded;
				localStorage.setItem('aiSummaryExpanded', JSON.stringify(this.expanded));
			}
		}"
	>
		<div class="overflow-hidden rounded-lg border border-ai-border shadow-card">
			<div @click="toggle()" class="cursor-pointer bg-ai px-6 py-4 transition hover:bg-ai-hover">
				<div class="flex items-center justify-between">
					<div class="flex items-center gap-2.5">
						<svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
						</svg>
						<h2 class="text-sm font-semibold text-white">AI Executive Summary</h2>
					</div>
					<div class="flex items-center gap-2">
						<button
							@click.stop="generateSummary()"
							class="inline-flex cursor-pointer items-center gap-1 rounded-md bg-white/20 px-2 py-1 text-[10px] font-semibold text-white transition hover:bg-white/30"
						>
							<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
							</svg>
							Regenerate
						</button>
						<svg :class="expanded ? 'rotate-180' : ''" class="h-4 w-4 text-white transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
						</svg>
					</div>
				</div>
			</div>
			<div x-show="expanded" x-collapse>
				<div class="bg-ai-light px-6 py-5">
					<p class="text-sm leading-relaxed text-text-secondary" x-text="summaryData?.summary"></p>

					<template x-if="summaryData?.topIssues?.length > 0">
						<div class="mt-5">
							<h3 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-ai">Top Issues</h3>
							<div class="space-y-2">
								<template x-for="issue in summaryData.topIssues" :key="issue.module">
									<div class="flex items-start gap-3">
										<span
											class="mt-0.5 inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
											:class="{
												'bg-red-100 text-red-700': issue.impact === 'high',
												'bg-amber-100 text-amber-700': issue.impact === 'medium',
												'bg-blue-100 text-blue-700': issue.impact === 'low' || !issue.impact,
											}"
											x-text="issue.impact || 'low'"
										></span>
										<span class="text-sm text-text-secondary" x-text="issue.issue"></span>
									</div>
								</template>
							</div>
						</div>
					</template>

					<template x-if="summaryData?.quickWins?.length > 0">
						<div class="mt-5">
							<h3 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-ai">Quick Wins</h3>
							<div class="space-y-2">
								<template x-for="win in summaryData.quickWins" :key="win.action">
									<div class="flex items-start gap-3">
										<span class="mt-0.5 inline-flex h-5 shrink-0 items-center rounded bg-emerald-100 px-1.5 text-[10px] font-bold text-emerald-700" x-text="'+' + (win.estimatedPoints || 0)"></span>
										<span class="text-sm text-text-secondary" x-text="win.action"></span>
									</div>
								</template>
							</div>
						</div>
					</template>
				</div>
			</div>
		</div>
	</div>
</div>
