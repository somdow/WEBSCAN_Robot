@props(["serpData", "moduleResult"])

@php
	$title = $serpData["title"] ?? "";
	$displayUrl = $serpData["url"] ?? "";
	$description = $serpData["description"] ?? "";

	$titleLength = mb_strlen($title);
	$descLength = mb_strlen($description);
	$isTitleLong = $titleLength > 60;

	$visibleFindings = array_filter($moduleResult->findings ?? array(), fn($finding) => ($finding["type"] ?? "") !== "data");
@endphp

<div>
	<div class="mb-6">
		<div class="flex items-center gap-3">
			<svg class="h-6 w-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
			</svg>
			<h2 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">SERP Preview</h2>
		</div>
		<p class="mt-1.5 text-sm text-text-secondary">How this page appears in Google search results.</p>
	</div>

	{{-- Google SERP mockup --}}
	<div class="rounded-xl border border-border bg-surface p-6 shadow-card">
		<div class="max-w-[600px]">
			{{-- URL breadcrumb --}}
			<div class="flex items-center gap-2">
				<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100">
					<svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
					</svg>
				</div>
				<div class="min-w-0">
					<span class="block truncate text-sm text-text-secondary">{{ $displayUrl }}</span>
				</div>
			</div>

			{{-- Title --}}
			<h3 class="mt-2 text-xl font-normal leading-snug text-[#1a0dab] hover:underline">
				{{ $title }}@if($isTitleLong)<span class="text-text-tertiary">...</span>@endif
			</h3>

			{{-- Description --}}
			@if($description)
				<p class="mt-1.5 text-sm leading-relaxed text-[#545454]">{{ $description }}</p>
			@else
				<p class="mt-1.5 text-sm italic leading-relaxed text-text-tertiary">No meta description set &mdash; search engines will auto-generate a snippet.</p>
			@endif
		</div>
	</div>

	{{-- Character counts --}}
	<div class="mt-4 flex flex-wrap gap-3">
		<div class="inline-flex items-center gap-2 rounded-lg border border-border bg-surface px-3.5 py-2 shadow-card">
			<span class="text-xs font-medium text-text-tertiary">Title length</span>
			<span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $isTitleLong ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $titleLength }} chars</span>
		</div>
		<div class="inline-flex items-center gap-2 rounded-lg border border-border bg-surface px-3.5 py-2 shadow-card">
			<span class="text-xs font-medium text-text-tertiary">Description length</span>
			<span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $descLength === 0 ? 'bg-red-100 text-red-700' : ($descLength > 160 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $descLength }} chars</span>
		</div>
	</div>

	{{-- Recommendations --}}
	@if(!empty($visibleFindings))
		<div class="mt-6 rounded-lg border border-amber-200/40 px-5 py-4" style="background-color: #faf2ca">
			<div class="mb-2.5 flex items-center gap-2">
				<svg class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
				</svg>
				<span class="text-xs font-semibold uppercase tracking-wider text-amber-600">Recommendations</span>
			</div>
			<ul class="space-y-2">
				@foreach($visibleFindings as $finding)
					<li class="flex items-start gap-2.5 text-sm leading-relaxed text-text-secondary">
						<span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400/60"></span>
						{{ $finding["message"] ?? "" }}
					</li>
				@endforeach
			</ul>
		</div>
	@endif
</div>
