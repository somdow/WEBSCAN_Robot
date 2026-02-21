{{-- SAFETY: $icon uses {!! !!} unescaped output for inline SVG rendering.
     Only pass trusted SVG markup from application code — never user input. --}}
@props(["title", "alpineVar", "icon" => null])

<div class="rounded-lg border border-border bg-surface overflow-visible">
	{{-- Clickable title bar --}}
	<button
		type="button"
		class="flex w-full items-center justify-between px-5 py-4 text-left"
		@click="{{ $alpineVar }} = !{{ $alpineVar }}"
	>
		<div class="flex items-center gap-2.5">
			@if($icon)
				<span class="text-text-secondary">{!! $icon !!}</span>
			@endif
			<h3 class="text-sm font-semibold text-text-primary">{{ $title }}</h3>
		</div>
		<svg
			class="h-4 w-4 text-text-tertiary transition-transform duration-200"
			:class="{{ $alpineVar }} ? 'rotate-180' : ''"
			fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
		>
			<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
		</svg>
	</button>

	{{-- Collapsible body --}}
	<div x-show="{{ $alpineVar }}" class="overflow-visible">
		<div class="border-t border-border px-5 pb-5 pt-4 overflow-visible">
			{{ $slot }}
		</div>
	</div>
</div>
