{{-- "Analyze Additional Pages" tab — tools for adding/discovering pages --}}
{{-- Lives inside the shared x-data="addPageManager()" scope in scan-results-body --}}
{{-- Expects: $project, $maxAdditionalPages --}}

<div class="mb-5 flex items-center gap-3">
	<svg class="h-6 w-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
		<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
	</svg>
	<h2 class="text-[1.8rem] font-bold leading-tight tracking-tight text-text-primary">Analyze Additional Pages</h2>
	<span class="rounded-full bg-border/60 px-2.5 py-0.5 text-xs font-medium text-text-tertiary" x-text="pages.length + ' / {{ $maxAdditionalPages }}'"></span>
	<div class="ml-auto flex items-center gap-2">
		<button
			@click="showManualInput = !showManualInput"
			class="inline-flex items-center gap-2 rounded-md border border-border bg-surface px-4 py-2 text-sm font-medium text-text-secondary transition hover:bg-gray-50 hover:text-text-primary"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			<span>Add Page</span>
		</button>
		<button
			@click="startDiscovery()"
			:disabled="discoveryStatus === 'running' || discoveryStatus === 'pending'"
			class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
		>
			<svg x-show="discoveryStatus !== 'running' && discoveryStatus !== 'pending'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
			</svg>
			<svg x-show="discoveryStatus === 'running' || discoveryStatus === 'pending'" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
				<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
				<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
			</svg>
			<span x-text="discoveryStatus === 'running' || discoveryStatus === 'pending' ? 'Discovering...' : discoveredPages.length > 0 ? 'Re-discover' : 'Discover Pages'"></span>
		</button>
	</div>
</div>

{{-- Manual URL input (hidden by default) --}}
<div x-show="showManualInput" x-cloak x-transition class="mb-8 rounded-lg border border-border bg-surface p-5 shadow-card">
	<div class="mb-3 flex items-center justify-between">
		<div class="flex items-center gap-2">
			<h3 class="text-sm font-semibold text-text-primary">Add a page URL</h3>
			<span class="text-xs text-text-tertiary">must match <strong>{{ parse_url($project->url, PHP_URL_HOST) }}</strong></span>
		</div>
		<button @click="showManualInput = false" class="text-text-tertiary transition hover:text-text-secondary">
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
			</svg>
		</button>
	</div>
	<div class="flex items-start gap-3">
		<div class="flex-1">
			<input
				type="url"
				x-model="newPageUrl"
				@keydown.enter.prevent="submitPage()"
				placeholder="https://{{ parse_url($project->url, PHP_URL_HOST) }}/about"
				style="outline: none; box-shadow: none;"
				class="w-full rounded-md border border-border bg-background px-4 py-2.5 text-sm text-text-primary placeholder-text-tertiary focus:border-accent focus:outline-none"
				:disabled="isSubmitting"
			/>
			<p x-show="errorMessage" x-text="errorMessage" x-cloak class="mt-1.5 text-xs text-red-600"></p>
		</div>
		<button
			@click="submitPage()"
			:disabled="isSubmitting || !newPageUrl.trim()"
			class="inline-flex items-center gap-2 rounded-md bg-accent px-5 py-2.5 text-sm font-medium text-white transition hover:bg-accent/90 disabled:cursor-not-allowed disabled:opacity-50"
		>
			<svg x-show="!isSubmitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			<svg x-show="isSubmitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
				<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
				<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
			</svg>
			<span x-text="isSubmitting ? 'Adding...' : 'Analyze'"></span>
		</button>
	</div>
</div>

{{-- Discovered pages list --}}
<div class="mb-8 rounded-lg border border-border bg-surface p-5 shadow-card" x-show="discoveredPages.length > 0 || discoveryStatus === 'running' || discoveryStatus === 'pending'" x-cloak>
	<div x-show="discoveredPages.length === 0 && (discoveryStatus === 'running' || discoveryStatus === 'pending')" class="flex items-center gap-2 text-sm text-text-tertiary">
		<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
			<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
			<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
		</svg>
		<span>Crawling your site for pages&hellip;</span>
	</div>
	<div x-show="discoveredPages.length > 0">
		<div class="mb-3 flex items-center justify-between">
			<span class="text-xs font-medium text-text-secondary" x-text="discoveredPages.length + ' pages found'"></span>
			<div class="flex items-center gap-3">
				<span class="text-xs text-text-tertiary" x-text="selectedDiscoveredIds.length + ' selected (max 5)'"></span>
				<button
					@click="analyzeSelected()"
					:disabled="selectedDiscoveredIds.length === 0 || isAnalyzingSelected"
					class="inline-flex items-center gap-1.5 rounded-md bg-accent px-3 py-1.5 text-xs font-medium text-white transition hover:bg-accent/90 disabled:cursor-not-allowed disabled:opacity-50"
				>
					<svg x-show="!isAnalyzingSelected" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
					</svg>
					<svg x-show="isAnalyzingSelected" x-cloak class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
					</svg>
					<span x-text="isAnalyzingSelected ? 'Queuing...' : 'Analyze Selected'"></span>
				</button>
			</div>
		</div>
		<div class="max-h-64 overflow-y-auto rounded-md border border-border">
			<template x-for="dp in discoveredPages" :key="dp.id">
				<label
					class="flex items-center gap-3 border-b border-border px-4 py-2 text-sm transition last:border-b-0"
					:class="dp.is_analyzed ? 'bg-gray-50 opacity-60' : 'hover:bg-gray-50 cursor-pointer'"
				>
					<input
						type="checkbox"
						:value="dp.id"
						:disabled="dp.is_analyzed || (selectedDiscoveredIds.length >= 5 && !selectedDiscoveredIds.includes(dp.id))"
						@change="toggleDiscoveredSelection(dp.id)"
						:checked="selectedDiscoveredIds.includes(dp.id)"
						class="h-4 w-4 rounded border-border text-accent focus:ring-accent"
					/>
					<span class="flex-1 truncate text-text-primary" :title="dp.url" x-text="dp.url"></span>
					<span x-show="dp.is_analyzed" class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Analyzed</span>
					<span x-show="!dp.is_analyzed" class="shrink-0 text-[10px] text-text-tertiary" x-text="'Depth ' + dp.crawl_depth"></span>
				</label>
			</template>
		</div>
	</div>
	<p x-show="discoveryError" x-text="discoveryError" x-cloak class="mt-2 text-xs text-red-600"></p>
</div>
