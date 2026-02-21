@props(["coreDetails" => array(), "coreVulnerabilities" => array()])

@php
	$detectedVersion = $coreDetails["detected_version"] ?? null;
	$latestVersion = $coreDetails["latest_version"] ?? null;
	$versionStatus = $coreDetails["version_status"] ?? "unknown";
	$vulnCount = count($coreVulnerabilities);
@endphp

<div class="mx-5 my-4 overflow-hidden rounded-lg border border-border bg-white shadow-sm">
	{{-- Section header --}}
	<div class="flex items-center gap-2.5 border-b border-border/60 bg-gray-50/60 px-4 py-3">
		<div class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-50">
			<svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
			</svg>
		</div>
		<span class="text-[13px] font-semibold text-text-primary">WordPress Core</span>
	</div>

	{{-- Core details --}}
	<div class="px-4 py-4">
		<div class="flex flex-wrap items-start gap-x-8 gap-y-3 text-[12px]">
			{{-- Detected version --}}
			<div>
				<span class="block text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Detected Version</span>
				<span class="mt-0.5 block font-mono text-text-secondary">{{ $detectedVersion ?? "Unknown" }}</span>
			</div>

			{{-- Latest version --}}
			<div>
				<span class="block text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Latest Version</span>
				<span class="mt-0.5 block font-mono text-text-secondary">{{ $latestVersion ?? "Unknown" }}</span>
			</div>

			{{-- Status --}}
			<div>
				<span class="block text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Status</span>
				<span class="mt-1 block">
					<x-scan.wp.version-status-badge :versionStatus="$versionStatus" />
				</span>
			</div>
		</div>

		{{-- Vulnerability details --}}
		@if($vulnCount > 0)
			<div class="mt-4 rounded-md border border-red-200/60 bg-red-50 px-4 py-3">
				<div class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-red-900">
					Known Vulnerabilities ({{ $vulnCount }})
				</div>
				<div class="divide-y divide-red-200/50">
					@foreach($coreVulnerabilities as $vulnerability)
						<x-scan.wp.vulnerability-row :vulnerability="$vulnerability" />
					@endforeach
				</div>
			</div>
		@endif
	</div>
</div>
