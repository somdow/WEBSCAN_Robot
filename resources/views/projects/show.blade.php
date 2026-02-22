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
				<div x-data="{ pdfOpen: false }" class="relative" x-bind:class="scanning && 'pointer-events-none opacity-40'">
					<button
						@click="pdfOpen = !pdfOpen"
						class="inline-flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm font-medium transition"
						:class="pdfOpen ? 'border-red-600 bg-red-600 text-white' : 'border-border bg-surface text-text-secondary hover:bg-gray-50 hover:text-text-primary'"
					>
						<svg class="h-5 w-5 transition-colors" :class="pdfOpen ? 'text-white' : 'text-red-600'" viewBox="0 0 24 24" fill="currentColor">
							<path d="M23.63 15.3c-.71-.745-2.166-1.17-4.224-1.17-1.1 0-2.377.106-3.761.354a19.443 19.443 0 0 1-2.307-2.661c-.532-.71-.994-1.49-1.42-2.236.817-2.484 1.207-4.507 1.207-5.962 0-1.632-.603-3.336-2.342-3.336-.532 0-1.065.32-1.349.781-.78 1.384-.425 4.4.923 7.381a60.277 60.277 0 0 1-1.703 4.507c-.568 1.349-1.207 2.733-1.917 4.01C2.834 18.53.314 20.34.03 21.758c-.106.533.071 1.03.462 1.42.142.107.639.533 1.49.533 2.59 0 5.323-4.188 6.707-6.707 1.065-.355 2.13-.71 3.194-.994a34.963 34.963 0 0 1 3.407-.745c2.732 2.448 5.145 2.839 6.352 2.839 1.49 0 2.023-.604 2.2-1.1.32-.64.106-1.349-.213-1.704zm-1.42 1.03c-.107.532-.64.887-1.384.887-.213 0-.39-.036-.604-.071-1.348-.32-2.626-.994-3.903-2.059a17.717 17.717 0 0 1 2.98-.248c.746 0 1.385.035 1.81.142.497.106 1.278.426 1.1 1.348zm-7.524-1.668a38.01 38.01 0 0 0-2.945.674 39.68 39.68 0 0 0-2.52.745 40.05 40.05 0 0 0 1.207-2.555c.426-.994.78-2.023 1.136-2.981.354.603.745 1.207 1.135 1.739a50.127 50.127 0 0 0 1.987 2.378zM10.038 1.46a.768.768 0 0 1 .674-.425c.745 0 .887.851.887 1.526 0 1.135-.355 2.874-.958 4.861-1.03-2.768-1.1-5.074-.603-5.962zM6.134 17.997c-1.81 2.981-3.549 4.826-4.613 4.826a.872.872 0 0 1-.532-.177c-.213-.213-.32-.461-.249-.745.213-1.065 2.271-2.555 5.394-3.904Z"/>
						</svg>
						Download PDF
						<svg class="h-3.5 w-3.5 transition-transform" :class="pdfOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
						</svg>
					</button>
					<div
						x-show="pdfOpen"
						x-cloak
						@click.outside="pdfOpen = false"
						x-transition:enter="transition ease-out duration-100"
						x-transition:enter-start="opacity-0 scale-95"
						x-transition:enter-end="opacity-100 scale-100"
						x-transition:leave="transition ease-in duration-75"
						x-transition:leave-start="opacity-100 scale-100"
						x-transition:leave-end="opacity-0 scale-95"
						class="absolute right-0 z-10 mt-1 w-48 origin-top-right rounded-md border border-border bg-surface py-1 shadow-lg"
					>
						<a href="{{ route('scans.pdf', $latestScan) }}?type=full" class="block px-4 py-2 text-right text-sm text-text-secondary hover:bg-gray-50 hover:text-text-primary">
							Full Report
						</a>
						<a href="{{ route('scans.pdf', $latestScan) }}?type=seo" class="block px-4 py-2 text-right text-sm text-text-secondary hover:bg-gray-50 hover:text-text-primary">
							SEO Report
						</a>
						<a href="{{ route('scans.pdf', $latestScan) }}?type=health" class="block px-4 py-2 text-right text-sm text-text-secondary hover:bg-gray-50 hover:text-text-primary">
							Site Health Report
						</a>
					</div>
				</div>
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
