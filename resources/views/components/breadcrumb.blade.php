@props(["items" => array()])

<nav aria-label="Breadcrumb" class="mt-10 mb-5 flex flex-wrap items-center gap-1.5 text-sm">
	@foreach($items as $index => $item)
		@if($index > 0)
			<svg class="h-3 w-3 shrink-0 text-text-tertiary/50" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
			</svg>
		@endif

		@if($loop->last)
			<span class="font-medium text-text-secondary break-all">{{ $item["label"] }}</span>
		@else
			<a href="{{ $item["url"] }}" class="text-text-tertiary transition hover:text-text-primary">{{ $item["label"] }}</a>
		@endif
	@endforeach
</nav>
