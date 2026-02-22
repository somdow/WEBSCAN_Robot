@props(["items" => array()])

<nav aria-label="Breadcrumb" class="mt-10 mb-5 flex flex-wrap items-center gap-1.5 text-sm">
	@foreach($items as $index => $item)
		@if($index > 0)
			<svg class="h-3 w-3 shrink-0 text-text-tertiary/50" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
			</svg>
		@endif

		@if($loop->last && !empty($item["externalUrl"]))
			<a href="{{ $item["externalUrl"] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-medium break-all transition hover:underline" style="color: #F25A15;">
				{{ $item["label"] }}
				<svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
				</svg>
			</a>
		@elseif($loop->last)
			<span class="font-medium text-text-secondary break-all">{{ $item["label"] }}</span>
		@else
			<a href="{{ $item["url"] }}" class="text-text-tertiary transition hover:text-text-primary">{{ $item["label"] }}</a>
		@endif
	@endforeach
</nav>
