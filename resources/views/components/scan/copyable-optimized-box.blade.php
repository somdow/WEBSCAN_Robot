@props(["label", "alpineValue", "charLimit" => 0])

{{-- Reusable optimized-text box with click-to-copy and visual feedback.
     $label: display label (e.g. "Optimized", "og:title — Optimized")
     $alpineValue: Alpine.js expression for the text value (e.g. "parsedOptimized", "parsedOgTitle") --}}
<div class="mb-4" x-data="{
	copied: false,
	copyText(text) {
		const ta = Object.assign(document.createElement('textarea'), { value: text });
		ta.style.cssText = 'position:fixed;opacity:0';
		document.body.appendChild(ta);
		ta.select();
		document.execCommand('copy');
		ta.remove();
		this.copied = true;
		setTimeout(() => this.copied = false, 2000);
	}
}">
	<div class="flex items-center justify-between mb-1.5">
		<span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-300">{{ $label }}</span>
		<span x-show="copied" x-cloak class="text-[11px] font-semibold text-emerald-400">Copied!</span>
		<span x-show="!copied" class="text-[11px] font-medium text-emerald-300" x-text="{{ $alpineValue }}.length + '/{{ $charLimit }} chars'"></span>
	</div>
	<div
		@click.stop="copyText({{ $alpineValue }})"
		class="flex cursor-pointer items-start gap-2 rounded-md bg-white/15 px-3.5 py-2.5 ring-1 ring-emerald-400/30 transition hover:bg-white/20"
	>
		<p class="min-w-0 flex-1 text-sm font-medium leading-relaxed text-white" x-text="{{ $alpineValue }}"></p>
		<div class="shrink-0 pt-0.5">
			<svg x-show="!copied" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
			<svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
		</div>
	</div>
</div>
