{{-- Reusable "Clear filter" button --}}
{{-- Requires parent Alpine scope with `statusFilter` variable --}}
@props(["afterClear" => ""])

<button
	@click="statusFilter = '';{{ $afterClear }}"
	class="ml-auto inline-flex cursor-pointer items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium text-white"
	style="background-color: #111827; transition: transform 150ms ease;"
	@mouseenter="$el.style.transform = 'scale(1.05)'"
	@mouseleave="$el.style.transform = 'scale(1)'"
>
	<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
		<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
	</svg>
	Clear filter
</button>
