{{-- "Pages" tab -- combined view: page list + add page + discover pages --}}
{{-- Lives inside x-data="scanResultsManager()" scope in scan-results-body --}}
{{-- Expects: $project, $maxAdditionalPages (passed from scan-results-body) --}}

<div class="mb-5 flex items-center justify-between gap-4">
	<div class="flex items-center gap-3">
		<svg class="h-6 w-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
		</svg>
		<h2 class="text-[1.8rem] font-bold leading-tight tracking-tight text-text-primary">Page Explorer</h2>
		<span class="rounded-full bg-border/60 px-2.5 py-0.5 text-xs font-medium text-text-tertiary" x-text="pages.length + ' / {{ $maxAdditionalPages }}'"></span>
	</div>
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


{{-- Pages table --}}
<div x-show="pages.length > 0" class="overflow-x-auto rounded-lg border border-border bg-surface shadow-card">
	<table class="w-full text-left text-sm">
		<thead class="border-b border-border bg-background/50 text-xs uppercase tracking-wider text-text-tertiary">
			<tr>
				<th class="px-6 py-3 font-medium">URL</th>
				<th class="px-4 py-3 font-medium">Score</th>
				<th class="px-4 py-3 font-medium">Status</th>
				<th class="px-4 py-3 font-medium">Source</th>
				<th class="px-4 py-3 font-medium">Scanned</th>
				<th class="px-4 py-3 font-medium"></th>
			</tr>
		</thead>
		<tbody class="divide-y divide-border">
			<template x-for="page in pages" :key="page.id">
				<tr class="transition" :class="page._isNew ? 'bg-blue-100 hover:bg-blue-100' : 'hover:bg-gray-50'">
					<td class="px-6 py-3">
						<a
							:href="page.scan_page_url"
							class="flex items-center gap-2 max-w-md"
							:class="page.scan_page_url ? 'text-accent hover:underline' : 'text-text-primary pointer-events-none'"
						>
							<span class="truncate" :title="page.url" x-text="truncateUrl(page.url, 60)"></span>
							<span x-show="page._isNew" x-cloak class="shrink-0 rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-blue-700">New</span>
						</a>
					</td>
					<td class="px-4 py-3">
						<template x-if="page.analysis_status === 'completed' && page.page_score !== null">
							<span class="font-semibold" :class="scoreColorClass(page.page_score)" x-text="page.page_score"></span>
						</template>
						<template x-if="page.analysis_status === 'pending' || page.analysis_status === 'running'">
							<span class="inline-flex items-center gap-1.5 text-text-tertiary">
								<svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
								</svg>
								<span class="text-xs">Analyzing</span>
							</span>
						</template>
						<template x-if="page.analysis_status === 'failed'">
							<span class="text-xs font-medium text-red-600">Failed</span>
						</template>
					</td>
					<td class="px-4 py-3">
						<span
							class="inline-block rounded-full px-2 py-0.5 text-[11px] font-medium"
							:class="{
								'bg-amber-100 text-amber-700': page.analysis_status === 'pending',
								'bg-blue-100 text-blue-700': page.analysis_status === 'running',
								'bg-emerald-100 text-emerald-700': page.analysis_status === 'completed',
								'bg-red-100 text-red-700': page.analysis_status === 'failed',
							}"
							x-text="page.analysis_status.charAt(0).toUpperCase() + page.analysis_status.slice(1)"
						></span>
						<span
							x-show="page.analysis_status === 'failed'"
							x-cloak
							class="ml-1 inline-block rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-600 border border-amber-200"
						>Credit Refunded</span>
					</td>
					<td class="px-4 py-3">
						<span
							class="inline-block rounded-full px-2 py-0.5 text-[11px] font-medium"
							:class="page.source === 'manual' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'"
							x-text="page.source === 'manual' ? 'Manual' : page.source.charAt(0).toUpperCase() + page.source.slice(1)"
						></span>
					</td>
					<td class="px-4 py-3 text-xs text-text-tertiary whitespace-nowrap" x-text="formatScannedDate(page.scanned_at)"></td>
					<td class="px-4 py-3">
						<button
							x-show="page.analysis_status === 'completed' || page.analysis_status === 'failed'"
							@click.stop="rescanPage(page)"
							:disabled="page._rescanning"
							class="inline-flex items-center gap-1 rounded-md border border-border bg-surface px-2.5 py-1 text-xs font-medium text-text-secondary transition hover:bg-gray-50 hover:text-text-primary disabled:cursor-not-allowed disabled:opacity-50"
						>
							<svg class="h-3.5 w-3.5" :class="page._rescanning && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
							</svg>
							<span x-text="page._rescanning ? 'Rescanning...' : 'Rescan'"></span>
						</button>
					</td>
				</tr>
			</template>
		</tbody>
	</table>
</div>


{{-- Empty state --}}
<div x-show="pages.length === 0" class="rounded-lg border border-dashed border-border bg-background/30 px-6 py-10 text-center">
	<svg class="mx-auto h-8 w-8 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
		<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
	</svg>
	<p class="mt-2 text-sm text-text-tertiary">No additional pages yet. Use the buttons above to add pages manually or discover them automatically.</p>
</div>
