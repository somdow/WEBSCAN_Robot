{{-- SERP Preview sidebar button — renders inside the Site Health group or as a standalone section --}}
{{-- Used in single-page-results and show-page sidebars --}}
@props(["serpPreviewResult", "serpData", "groupedResults", "sidebarGroups", "clickAction", "activeExpression" => "activeCategory === 'serp-preview'"])

@if($serpPreviewResult && $serpData)
	@php
		/* Check whether the Site Health group rendered any category buttons naturally */
		$siteHealthHasCategories = false;
		foreach ($sidebarGroups as $gName => $gData) {
			if ($gName !== "Site Health") continue;
			foreach ($gData["categories"] as $gCat) {
				if ($groupedResults->has($gCat)) { $siteHealthHasCategories = true; break 2; }
			}
		}
	@endphp

	{{-- If Site Health group has no other categories, render a standalone group header --}}
	@if(!$siteHealthHasCategories)
		<div class="mt-3 px-4 pb-1 pt-2">
			<div class="flex items-center gap-2">
				<svg class="h-4 w-4 text-text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
				</svg>
				<span class="text-[18px] font-bold uppercase tracking-widest text-text-primary">Site Health</span>
			</div>
		</div>
	@endif

	<button
		@click="{{ $clickAction }}"
		:class="{{ $activeExpression }}
			? 'bg-gray-300 text-text-primary font-medium'
			: 'text-text-secondary hover:bg-gray-50 hover:text-text-primary'"
		class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg px-5 py-2.5 text-left text-sm transition-colors duration-150"
	>
		<div class="flex items-center gap-2.5 min-w-0">
			<svg class="h-4 w-4 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
			</svg>
			<span class="truncate">SERP Preview</span>
		</div>
		<span class="shrink-0 rounded-full bg-blue-50 text-blue-600 px-2 py-0.5 text-[11px] font-medium transition-colors">Info</span>
	</button>
@endif
