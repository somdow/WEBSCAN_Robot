@if ($paginator->hasPages())
	<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center gap-2">
		<span class="inline-flex items-center rounded-md shadow-sm">
			{{-- Previous --}}
			@if ($paginator->onFirstPage())
				<span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
					<span class="inline-flex items-center gap-1 rounded-l-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-tertiary cursor-not-allowed" aria-hidden="true">
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
						Previous
					</span>
				</span>
			@else
				<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 rounded-l-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-secondary transition hover:bg-background hover:text-text-primary" aria-label="{{ __('pagination.previous') }}">
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
					Previous
				</a>
			@endif

			{{-- Page numbers --}}
			@foreach ($elements as $element)
				@if (is_string($element))
					<span aria-disabled="true">
						<span class="inline-flex items-center border border-border bg-surface px-4 py-2 -ml-px text-sm font-medium text-text-tertiary cursor-default">{{ $element }}</span>
					</span>
				@endif

				@if (is_array($element))
					@foreach ($element as $page => $url)
						@if ($page == $paginator->currentPage())
							<span aria-current="page">
								<span class="inline-flex items-center border border-accent bg-accent/10 px-4 py-2 -ml-px text-sm font-semibold text-accent cursor-default">{{ $page }}</span>
							</span>
						@else
							<a href="{{ $url }}" class="inline-flex items-center border border-border bg-surface px-4 py-2 -ml-px text-sm font-medium text-text-secondary transition hover:bg-background" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
								{{ $page }}
							</a>
						@endif
					@endforeach
				@endif
			@endforeach

			{{-- Next --}}
			@if ($paginator->hasMorePages())
				<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 rounded-r-md border border-border bg-surface px-3 py-2 -ml-px text-sm font-medium text-text-secondary transition hover:bg-background hover:text-text-primary" aria-label="{{ __('pagination.next') }}">
					Next
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
				</a>
			@else
				<span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
					<span class="inline-flex items-center gap-1 rounded-r-md border border-border bg-surface px-3 py-2 -ml-px text-sm font-medium text-text-tertiary cursor-not-allowed" aria-hidden="true">
						Next
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
					</span>
				</span>
			@endif
		</span>

		<p class="text-xs text-text-tertiary">
			{!! __('Showing') !!}
			@if ($paginator->firstItem())
				<span class="font-medium text-text-secondary">{{ $paginator->firstItem() }}</span>
				{!! __('to') !!}
				<span class="font-medium text-text-secondary">{{ $paginator->lastItem() }}</span>
			@else
				{{ $paginator->count() }}
			@endif
			{!! __('of') !!}
			<span class="font-medium text-text-secondary">{{ $paginator->total() }}</span>
			{!! __('results') !!}
		</p>
	</nav>
@endif
