@props(["sitemapDetails" => array()])

@php
	$sitemaps = $sitemapDetails["sitemaps"] ?? array();
	$totalUrlCount = $sitemapDetails["total_url_count"] ?? 0;
	$crossReference = $sitemapDetails["cross_reference"] ?? null;

	$accessibleCount = count(array_filter($sitemaps, fn($s) => $s["accessible"] ?? false));
	$inaccessibleCount = count($sitemaps) - $accessibleCount;

	$hasCrossReference = $crossReference !== null;
	$inSitemapOnly = $crossReference["in_sitemap_only"] ?? array();
	$inSitemapOnlyCount = $crossReference["in_sitemap_only_count"] ?? 0;
	$crawledOnly = $crossReference["crawled_only"] ?? array();
	$crawledOnlyCount = $crossReference["crawled_only_count"] ?? 0;
	$inBothCount = $crossReference["in_both_count"] ?? 0;
	$crawledCount = $crossReference["crawled_count"] ?? 0;
	$sitemapCount = $crossReference["sitemap_count"] ?? $totalUrlCount;
	$isPerfectMatch = $hasCrossReference && $inSitemapOnlyCount === 0 && $crawledOnlyCount === 0;
	$hasGaps = $hasCrossReference && ($inSitemapOnlyCount > 0 || $crawledOnlyCount > 0);
@endphp

@if(count($sitemaps) > 0)
	<div class="mx-5 my-4 overflow-hidden rounded-lg border border-border bg-white shadow-sm">
		{{-- Header --}}
		<div class="flex items-center justify-between border-b border-border/60 bg-gray-50/60 px-4 py-3">
			<div class="flex items-center gap-3">
				<div class="flex h-7 w-7 items-center justify-center rounded-md bg-orange-50">
					<svg class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
					</svg>
				</div>
				<span class="text-[13px] font-semibold text-text-primary">Sitemap Overview</span>
				<span class="rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-semibold text-orange-700">{{ number_format($totalUrlCount) }} {{ $totalUrlCount === 1 ? "URL" : "URLs" }}</span>
			</div>
			<div class="flex items-center gap-2">
				@if($accessibleCount > 0)
					<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">{{ $accessibleCount }} accessible</span>
				@endif
				@if($inaccessibleCount > 0)
					<span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">{{ $inaccessibleCount }} inaccessible</span>
				@endif
			</div>
		</div>

		{{-- Sitemaps table --}}
		@if(count($sitemaps) > 0)
			<div class="overflow-x-auto">
				<table class="w-full text-left text-sm">
					<thead class="border-b border-border/40 bg-gray-50/30 text-[11px] uppercase tracking-wider text-text-tertiary">
						<tr>
							<th class="px-4 py-2.5 font-medium">Sitemap URL</th>
							<th class="px-4 py-2.5 font-medium text-center">URLs Found</th>
							<th class="px-4 py-2.5 font-medium text-center">Type</th>
							<th class="px-4 py-2.5 font-medium text-center">Status</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-border/30">
						@foreach($sitemaps as $sitemap)
							@php
								$sitemapPath = parse_url($sitemap["url"], PHP_URL_PATH) ?: $sitemap["url"];
								$isIndex = $sitemap["is_index"] ?? false;
								$childCount = $sitemap["child_count"] ?? 0;
							@endphp
							<tr class="transition hover:bg-gray-50/60">
								<td class="px-4 py-2.5">
									<div class="flex items-center gap-2 max-w-sm">
										<svg class="h-3.5 w-3.5 shrink-0 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
										</svg>
										<span class="truncate text-text-primary text-xs" title="{{ $sitemap['url'] }}">{{ $sitemapPath }}</span>
									</div>
								</td>
								<td class="px-4 py-2.5 text-center">
									<span class="font-semibold text-text-primary text-xs">{{ number_format($sitemap["url_count"] ?? 0) }}</span>
								</td>
								<td class="px-4 py-2.5 text-center">
									@if($isIndex)
										<span class="rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold text-violet-700">Index ({{ $childCount }})</span>
									@else
										<span class="text-[10px] text-text-tertiary">Urlset</span>
									@endif
								</td>
								<td class="px-4 py-2.5 text-center">
									@if($sitemap["accessible"] ?? false)
										<span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
											<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
											OK
										</span>
									@else
										<span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">
											<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
											Error
										</span>
									@endif
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@endif

		{{-- Cross-Reference section (crawl scans only) --}}
		@if($hasCrossReference)
			<div class="border-t border-border/60 px-4 py-4">
				<div class="mb-3 flex items-center gap-2">
					<svg class="h-4 w-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
					</svg>
					<span class="text-[12px] font-bold uppercase tracking-wider text-text-secondary">Sitemap vs. Crawl Cross-Reference</span>
				</div>

				{{-- Summary stats --}}
				<div class="mb-4 grid grid-cols-3 gap-3">
					<div class="rounded-lg border border-border bg-gray-50/60 px-3 py-2.5 text-center">
						<div class="text-lg font-bold text-text-primary">{{ $sitemapCount }}</div>
						<div class="text-[10px] text-text-tertiary">In Sitemap</div>
					</div>
					<div class="rounded-lg border border-border bg-gray-50/60 px-3 py-2.5 text-center">
						<div class="text-lg font-bold text-text-primary">{{ $crawledCount }}</div>
						<div class="text-[10px] text-text-tertiary">Crawled</div>
					</div>
					<div class="rounded-lg border border-emerald-200 bg-emerald-50/60 px-3 py-2.5 text-center">
						<div class="text-lg font-bold text-emerald-700">{{ $inBothCount }}</div>
						<div class="text-[10px] text-emerald-600">Matched</div>
					</div>
				</div>

				@if($isPerfectMatch)
					<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-center">
						<svg class="mx-auto h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
						</svg>
						<p class="mt-1.5 text-sm font-medium text-emerald-700">Perfect match</p>
						<p class="mt-0.5 text-xs text-emerald-600/70">All sitemap URLs were found during the crawl and all crawled pages are in the sitemap.</p>
					</div>
				@endif

				@if($hasGaps)
					<div class="space-y-3">
						@if($inSitemapOnlyCount > 0)
							<div class="rounded-lg border border-amber-200 bg-amber-50 p-3" x-data="{ showSitemapUrls: false }">
								<div class="cursor-pointer" @click="showSitemapUrls = !showSitemapUrls; if (showSitemapUrls) { const el = $el.closest('.rounded-lg'); setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200); }">
									<div class="flex items-center justify-between">
										<div class="flex items-center gap-2">
											<svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
											</svg>
											<span class="text-xs font-semibold text-amber-800">In Sitemap, Not Crawled</span>
											<span class="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-800">{{ $inSitemapOnlyCount }}</span>
										</div>
										<svg class="h-4 w-4 text-amber-600 transition-transform duration-200" :class="showSitemapUrls && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
										</svg>
									</div>
									<p class="mt-1 text-[11px] text-amber-700/80">Pages listed in your sitemap that weren't reachable by following links from your site. These may be orphan pages or pages blocked by navigation structure.</p>
								</div>

								<div x-show="showSitemapUrls" x-cloak class="mt-2.5 space-y-1">
									@foreach($inSitemapOnly as $orphanUrl)
										<a href="{{ $orphanUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 rounded bg-amber-100/60 px-2.5 py-1.5 transition hover:bg-amber-200/60 group" title="{{ $orphanUrl }}">
											<svg class="h-3 w-3 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
											</svg>
											<span class="truncate text-[11px] text-amber-800 group-hover:underline">{{ $orphanUrl }}</span>
											<svg class="h-3 w-3 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
										</a>
									@endforeach
									@if($inSitemapOnlyCount > count($inSitemapOnly))
										<p class="px-2.5 text-[10px] text-amber-600">...and {{ $inSitemapOnlyCount - count($inSitemapOnly) }} more</p>
									@endif
								</div>
							</div>
						@endif

						@if($crawledOnlyCount > 0)
							<div class="rounded-lg border border-blue-200 bg-blue-50 p-3" x-data="{ showCrawledUrls: false }">
								<div class="cursor-pointer" @click="showCrawledUrls = !showCrawledUrls; if (showCrawledUrls) { const el = $el.closest('.rounded-lg'); setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200); }">
									<div class="flex items-center justify-between">
										<div class="flex items-center gap-2">
											<svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
											</svg>
											<span class="text-xs font-semibold text-blue-800">Crawled, Not in Sitemap</span>
											<span class="rounded-full bg-blue-200 px-2 py-0.5 text-[10px] font-bold text-blue-800">{{ $crawledOnlyCount }}</span>
										</div>
										<svg class="h-4 w-4 text-blue-600 transition-transform duration-200" :class="showCrawledUrls && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
										</svg>
									</div>
									<p class="mt-1 text-[11px] text-blue-700/80">Discovered pages that are not included in your sitemap. Add these to ensure search engines can find all your content.</p>
								</div>

								<div x-show="showCrawledUrls" x-cloak class="mt-2.5 space-y-1">
									@foreach($crawledOnly as $missingUrl)
										<a href="{{ $missingUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 rounded bg-blue-100/60 px-2.5 py-1.5 transition hover:bg-blue-200/60 group" title="{{ $missingUrl }}">
											<svg class="h-3 w-3 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
											</svg>
											<span class="truncate text-[11px] text-blue-800 group-hover:underline">{{ $missingUrl }}</span>
											<svg class="h-3 w-3 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
										</a>
									@endforeach
									@if($crawledOnlyCount > count($crawledOnly))
										<p class="px-2.5 text-[10px] text-blue-600">...and {{ $crawledOnlyCount - count($crawledOnly) }} more</p>
									@endif
								</div>
							</div>
						@endif
					</div>
				@endif
			</div>
		@endif
	</div>
@endif
