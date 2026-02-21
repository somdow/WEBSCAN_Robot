{{-- "Pages" tab — shows all project pages with scores and status --}}
{{-- Lives inside x-data="scanResultsManager()" scope in scan-results-body --}}

<div class="mb-5 flex items-center justify-between gap-4">
	<div class="flex items-center gap-3">
		<svg class="h-6 w-6 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
		</svg>
		<h2 class="text-[1.8rem] font-bold leading-tight tracking-tight text-text-primary">Pages</h2>
		<span class="rounded-full bg-border/60 px-2.5 py-0.5 text-xs font-medium text-text-tertiary" x-text="pages.length + ' page' + (pages.length !== 1 ? 's' : '')"></span>
	</div>
	<button
		@click="scoreTab = 'addPages'; statusFilter = ''"
		class="inline-flex items-center gap-1.5 rounded-md border border-accent bg-accent px-3 py-2 text-sm font-medium text-white transition hover:bg-accent-hover"
	>
		Analyze Additional Pages
	</button>
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
	<p class="mt-2 text-sm text-text-tertiary">No additional pages yet. Use the "Analyze Additional Pages" tab to add pages.</p>
</div>
