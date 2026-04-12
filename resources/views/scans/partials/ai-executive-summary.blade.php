{{-- AI Executive Summary \u2014 slide-in panel from right --}}
{{-- Expects variables: $scan --}}

@php
	$existingSummary = $scan->ai_executive_summary;
	$aiProviderKey = auth()->user()->ai_provider ?? config("ai.default_provider");
	$aiDisplayNames = array("gemini" => "Gemini", "openai" => "ChatGPT", "anthropic" => "Claude");
	$aiModelName = $aiDisplayNames[$aiProviderKey] ?? "AI";
@endphp

<div
	x-data="aiSummaryPanel()"
	x-on:ai-generate-summary.window="openAndGenerate()"
>
	{{-- Backdrop --}}
	<div
		x-show="panelOpen"
		x-transition:enter="transition ease-out duration-300"
		x-transition:enter-start="opacity-0"
		x-transition:enter-end="opacity-100"
		x-transition:leave="transition ease-in duration-200"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		@click="panelOpen = false"
		x-cloak
		class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm"
	></div>

	{{-- Slide-over panel --}}
	<div
		x-show="panelOpen"
		x-transition:enter="transition ease-out duration-300"
		x-transition:enter-start="translate-x-full"
		x-transition:enter-end="translate-x-0"
		x-transition:leave="transition ease-in duration-200"
		x-transition:leave-start="translate-x-0"
		x-transition:leave-end="translate-x-full"
		x-cloak
		class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl"
	>
		{{-- Header --}}
		<div class="flex items-center justify-between border-b border-border bg-ai px-5 py-4">
			<div class="flex items-center gap-2.5">
				<svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
				</svg>
				<h2 class="text-sm font-semibold text-white">AI Executive Summary</h2>
			</div>
			<div class="flex items-center gap-2">
				<button
					@click="regenerate()"
					x-show="summaryState === 'loaded'"
					class="inline-flex cursor-pointer items-center gap-1 rounded-md bg-white/20 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-white/30"
				>
					<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
					</svg>
					Regenerate
				</button>
				<button @click="panelOpen = false" class="rounded-md p-1 text-white/70 transition hover:bg-white/20 hover:text-white">
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
					</svg>
				</button>
			</div>
		</div>

		{{-- Body --}}
		<div class="flex-1 overflow-y-auto">
			{{-- Error --}}
			<div x-show="summaryState === 'error'" x-cloak class="p-5">
				<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
					<div class="flex items-start gap-2">
						<svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
						</svg>
						<p class="text-sm text-red-700" x-text="summaryError"></p>
					</div>
				</div>
			</div>

			{{-- Loading --}}
			<div x-show="summaryState === 'loading'" x-cloak class="flex flex-1 flex-col items-center justify-center p-10">
				<svg class="h-8 w-8 animate-spin text-ai" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
				</svg>
				<p class="mt-4 text-sm font-medium text-ai" x-text="loadingMessage"></p>
			</div>

			{{-- Summary content --}}
			<div x-show="summaryState === 'loaded' && summaryData" x-cloak class="p-5">
				<h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-ai">Overview</h3>
				<p class="text-sm leading-relaxed text-text-secondary" x-text="summaryData?.summary"></p>

				<template x-if="summaryData?.topIssues?.length > 0">
					<div class="mt-6">
						<h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-ai">Top Issues</h3>
						<div class="space-y-2.5">
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
					<div class="mt-6">
						<h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-ai">Quick Wins</h3>
						<div class="space-y-2.5">
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

<script>
function aiSummaryPanel() {
	return {
		panelOpen: false,
		summaryState: '{{ $existingSummary ? "loaded" : "idle" }}',
		summaryData: {!! $existingSummary ? \Illuminate\Support\Js::from($existingSummary) : 'null' !!},
		summaryError: null,
		loadingMessage: '',

		waitingMessages: [
			'Asking {{ $aiModelName }} to read between the lines\u2026',
			'{{ $aiModelName }} is crunching the numbers\u2026',
			'Brewing a fresh pot of insights with {{ $aiModelName }}\u2026',
			'{{ $aiModelName }} is connecting the dots\u2026',
			'Teaching {{ $aiModelName }} about your website\u2026',
			'{{ $aiModelName }} is putting on its reading glasses\u2026',
			'Letting {{ $aiModelName }} work its magic\u2026',
			'{{ $aiModelName }} is deep in thought\u2026',
			'Consulting {{ $aiModelName }} for the bigger picture\u2026',
			'{{ $aiModelName }} is drafting your executive briefing\u2026',
			'Giving {{ $aiModelName }} a moment to think\u2026',
			'{{ $aiModelName }} is reviewing your site like a pro\u2026',
			'Warming up {{ $aiModelName }} for your summary\u2026',
			'{{ $aiModelName }} is piecing together the puzzle\u2026',
			'Sit tight \u2014 {{ $aiModelName }} is on the case\u2026',
			'{{ $aiModelName }} is scanning every pixel of your site\u2026',
			'Pouring {{ $aiModelName }} a coffee while it works\u2026',
			'{{ $aiModelName }} is writing you a masterpiece\u2026',
			'Hold tight \u2014 {{ $aiModelName }} is almost there\u2026',
			'{{ $aiModelName }} is separating the signal from the noise\u2026',
			'Giving {{ $aiModelName }} the grand tour of your site\u2026',
			'{{ $aiModelName }} is doing the heavy lifting\u2026',
			'{{ $aiModelName }} just said \u2018interesting\u2019 out loud\u2026',
			'{{ $aiModelName }} is channeling its inner SEO guru\u2026',
			'Sharpening {{ $aiModelName }}\u2019s pencils for your report\u2026',
			'{{ $aiModelName }} is reading your site cover to cover\u2026',
			'Almost there \u2014 {{ $aiModelName }} is dotting the i\u2019s\u2026',
			'{{ $aiModelName }} is turning data into wisdom\u2026',
			'Handing {{ $aiModelName }} a magnifying glass\u2026',
			'{{ $aiModelName }} is polishing your summary to perfection\u2026',
		],

		pickLoadingMessage() {
			const messages = this.waitingMessages;
			this.loadingMessage = messages[Math.floor(Math.random() * messages.length)];
		},

		openAndGenerate() {
			this.panelOpen = true;
			if (this.summaryState !== 'loaded') {
				this.fetchSummary();
			}
		},

		regenerate() {
			this.fetchSummary();
		},

		async fetchSummary() {
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
		},
	};
}
</script>
