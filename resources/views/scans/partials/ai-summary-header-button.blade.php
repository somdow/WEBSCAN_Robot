{{-- AI Summary header button — shared across project and scan views --}}
{{-- Expects: $scan, $hasApiKey, $aiAvailable --}}
{{-- Assumes parent has Alpine x-data with `scanning` property --}}
@if($scan && $scan->isComplete())
	@php $hasSummary = (bool) $scan->ai_executive_summary; @endphp
	@if($hasApiKey)
		<button
			@click="$dispatch('ai-generate-summary')"
			class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-ai/20 bg-surface px-3 py-2 text-sm font-medium text-ai transition hover:bg-ai-light"
			x-bind:class="scanning && 'pointer-events-none opacity-40'"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
			</svg>
			{{ $hasSummary ? "Regenerate AI Summary" : "AI Summary" }}
		</button>
	@elseif($aiAvailable)
		<button
			@click="$dispatch('open-modal', 'configure-api-key')"
			class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-ai/20 bg-surface px-3 py-2 text-sm font-medium text-ai transition hover:bg-ai-light"
			x-bind:class="scanning && 'pointer-events-none opacity-40'"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
			</svg>
			AI Summary
		</button>
	@else
		<button
			@click="$dispatch('open-modal', 'upgrade-pro')"
			class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-ai/20 bg-surface px-3 py-2 text-sm font-medium text-ai/50 transition hover:bg-ai-light"
			x-bind:class="scanning && 'pointer-events-none opacity-40'"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
			</svg>
			AI Summary
			<span class="rounded bg-ai/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-ai/60">Pro</span>
		</button>
	@endif
@endif
