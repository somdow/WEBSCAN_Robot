<x-app-layout>
	<x-slot name="header">
		@php
			$breadcrumbItems = array(
				array("label" => "Home", "url" => route("dashboard")),
				array("label" => "Projects", "url" => route("projects.index")),
				array("label" => $project->name, "url" => route("projects.show", $project)),
				array("label" => $competitor->displayName()),
			);
		@endphp
		<div class="flex items-start justify-between gap-12">
			<div>
				<x-scan.page-header
					:projectName="$competitor->displayName()"
					subtitle="Competitor Analysis"
					:url="$competitor->url"
					:keywords="array()"
					:breadcrumbItems="$breadcrumbItems"
				/>
			</div>
			<div class="mt-6 shrink-0">
				<a
					href="{{ route("projects.show", $project) }}"
					class="inline-flex items-center gap-1.5 rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-secondary transition hover:bg-gray-50 hover:text-text-primary"
				>
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
					</svg>
					Back to Project
				</a>
			</div>
		</div>
	</x-slot>

	@if(!$scan)
		<div class="rounded-lg border border-border bg-surface px-6 py-12 text-center shadow-card">
			<p class="text-sm text-text-secondary">No scan results available for this competitor yet.</p>
		</div>
	@else
		@include("scans.partials.scan-results-body", array(
			"scan" => $scan,
			"scanViewData" => $scanViewData,
			"aiAvailable" => $aiAvailable,
			"hasApiKey" => $hasApiKey,
			"showCompetitorsTab" => false,
			"canAddPages" => false,
		))
	@endif
</x-app-layout>
