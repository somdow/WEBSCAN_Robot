<x-app-layout>
	<x-slot name="header">
		@php
			$isHistoricalScan = !empty($requestedScan);
			$subtitle = $isHistoricalScan ? "Scan Report" : "Account Overview";
			$breadcrumbItems = array(
				array("label" => "Home", "url" => route("dashboard")),
				array("label" => "Projects", "url" => route("projects.index")),
			);
			if ($isHistoricalScan) {
				$breadcrumbItems[] = array("label" => $project->name, "url" => route("projects.show", $project));
				$breadcrumbItems[] = array("label" => "Scan Report");
			} else {
				$breadcrumbItems[] = array("label" => $project->name);
			}
			$scanRunning = !empty($activeScan) && !$activeScan->isComplete();
		@endphp
		<div
			class="flex items-start justify-between gap-12"
			x-data="{
				scanning: {{ $scanRunning ? 'true' : 'false' }},
				scansUsed: {{ $scansUsed ?? 0 }},
				scansMax: {{ $scansMax ?? 10 }},
				triggerScan() {
					if (this.scanning) return;
					this.scanning = true;
					fetch('{{ route("scans.store", $project) }}', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'Accept': 'application/json',
							'X-CSRF-TOKEN': '{{ csrf_token() }}'
						}
					})
					.then(r => {
						if (!r.ok && r.status !== 422 && r.status !== 429) throw new Error();
						return r.json();
					})
					.then(data => {
						if (data.success) {
							this.scansUsed = Math.min(this.scansUsed + 1, this.scansMax);
							window.dispatchEvent(new CustomEvent('scan-started', { detail: { scanId: data.scan_id } }));
						} else {
							this.scanning = false;
							alert(data.error || 'Failed to start scan.');
						}
					})
					.catch(() => {
						this.scanning = false;
						alert('Failed to start scan. Please try again.');
					});
				}
			}"
		>
			<div x-bind:class="scanning && 'pointer-events-none opacity-40'">
				<x-scan.page-header
					:projectName="$project->name"
					:subtitle="$subtitle"
					:url="$project->url"
					:keywords="$project->target_keywords ?? array()"
					:breadcrumbItems="$breadcrumbItems"
				/>
			</div>
			<div class="mt-6 shrink-0">
				<div class="flex items-center gap-2">
				@include("scans.partials.ai-summary-header-button", array("scan" => $latestScan))
				<a
					href="{{ route("projects.edit", $project) }}"
					class="inline-flex items-center gap-1.5 rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-secondary transition hover:bg-gray-50 hover:text-text-primary"
					x-bind:class="scanning && 'pointer-events-none opacity-40'"
					@if($scanRunning) aria-disabled="true" @endif
				>
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
					</svg>
					Edit
				</a>
				@if($latestScan && $latestScan->status === \App\Enums\ScanStatus::Completed)
				<a
					href="{{ route("scans.pdf", $latestScan) }}"
					class="inline-flex items-center gap-1.5 rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-secondary transition hover:bg-gray-50 hover:text-text-primary"
					x-bind:class="scanning && 'pointer-events-none opacity-40'"
					@if($scanRunning) aria-disabled="true" @endif
				>
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
					</svg>
					Download PDF
				</a>
				@endif
				<x-primary-button
					x-bind:disabled="scanning"
					x-bind:class="scanning && 'opacity-40 cursor-not-allowed'"
					x-on:click="triggerScan()"
				>
					<svg class="h-4 w-4" x-bind:class="scanning && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						@if($project->ownScans->isNotEmpty())
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
						@else
							<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
						@endif
					</svg>
					<span x-text="scanning ? 'Scanning...' : '{{ $project->ownScans->isNotEmpty() ? "Rescan" : "Run Scan" }}'"></span>
				</x-primary-button>
				</div>
				<p class="mt-1.5 text-right text-xs text-text-tertiary"
				x-on:credits-used.window="scansUsed = Math.min(scansUsed + ($event.detail?.count || 1), scansMax)"
			>
					<span x-text="scansUsed"></span> of <span x-text="scansMax"></span> scans used this month
				</p>
			</div>
		</div>
	</x-slot>

	{{-- No scans yet (only when nothing is running either) --}}
	@if(!$latestScan)
		<div class="rounded-lg border border-border bg-surface px-6 py-12 text-center shadow-card">
			<svg class="mx-auto h-10 w-10 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
			</svg>
			<p class="mt-3 text-sm font-medium text-text-primary">Run your first scan</p>
			<p class="mt-1 text-sm text-text-tertiary">Click "Run Scan" above to analyze this website.</p>
		</div>
	@endif

	@include("scans.partials.scan-results-body", array(
		"scan" => $displayScan ?? $latestScan,
		"activeScan" => $activeScan ?? null,
		"project" => $project,
		"canAddPages" => $canAddPages ?? false,
		"additionalPages" => $additionalPages ?? collect(),
		"maxAdditionalPages" => $maxAdditionalPages ?? 0,
		"displayScan" => $displayScan ?? $latestScan,
	))
</x-app-layout>
