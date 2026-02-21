@props(["themeDetails" => array(), "themeVulnerabilities" => array()])

@php
	$themeSlug = $themeDetails["slug"] ?? "";
	$themeName = $themeDetails["name"] ?? ucwords(str_replace("-", " ", $themeSlug));
	$detectedVersion = $themeDetails["detected_version"] ?? null;
	$latestVersion = $themeDetails["latest_version"] ?? null;
	$isPremium = $themeDetails["is_premium"] ?? false;
	$versionStatus = match (true) {
		$latestVersion === null || $detectedVersion === null => "unknown",
		version_compare($detectedVersion, $latestVersion, ">=") => "current",
		default => "outdated",
	};
	$vulnCount = count($themeVulnerabilities);
@endphp

@if(!empty($themeDetails))
	<div class="mx-5 my-4 overflow-hidden rounded-lg border border-border bg-white shadow-sm">
		{{-- Section header --}}
		<div class="flex items-center gap-2.5 border-b border-border/60 bg-gray-50/60 px-4 py-3">
			<div class="flex h-7 w-7 items-center justify-center rounded-md bg-violet-50">
				<svg class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
				</svg>
			</div>
			<span class="text-[13px] font-semibold text-text-primary">Active Theme</span>
		</div>

		{{-- Theme details --}}
		<div class="px-4 py-4">
			<div class="flex flex-wrap items-start gap-x-8 gap-y-3 text-[12px]">
				{{-- Theme name --}}
				<div>
					<span class="block text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Theme</span>
					<span class="mt-0.5 block font-medium text-text-primary">
						@if(!$isPremium && $themeSlug)
							<a href="https://wordpress.org/themes/{{ $themeSlug }}/" target="_blank" rel="noopener noreferrer" class="transition hover:text-accent hover:underline">{{ $themeName }}</a>
						@else
							{{ $themeName }}
							@if($isPremium)
								<span class="ml-1 inline-flex rounded bg-violet-100 px-1 py-0.5 text-[9px] font-bold uppercase text-violet-700">Premium</span>
							@endif
						@endif
					</span>
				</div>

				{{-- Detected version --}}
				<div>
					<span class="block text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Detected Version</span>
					<span class="mt-0.5 block font-mono text-text-secondary">{{ $detectedVersion ?? "Unknown" }}</span>
				</div>

				{{-- Latest version --}}
				<div>
					<span class="block text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Latest Version</span>
					<span class="mt-0.5 block font-mono text-text-secondary">{{ $isPremium ? "N/A" : ($latestVersion ?? "Unknown") }}</span>
				</div>

				{{-- Status --}}
				<div>
					<span class="block text-[10px] font-bold uppercase tracking-wider text-text-tertiary">Status</span>
					<span class="mt-1 block">
						<x-scan.wp.version-status-badge :versionStatus="$versionStatus" :isPremium="$isPremium" />
					</span>
				</div>
			</div>

			{{-- Vulnerability details --}}
			@if($vulnCount > 0)
				<div class="mt-4 rounded-md border border-red-200/60 bg-red-50/40 px-4 py-3">
					<div class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-red-600">
						Known Vulnerabilities ({{ $vulnCount }})
					</div>
					<div class="divide-y divide-red-200/50">
						@foreach($themeVulnerabilities as $vulnerability)
							<x-scan.wp.vulnerability-row :vulnerability="$vulnerability" />
						@endforeach
					</div>
				</div>
			@endif
		</div>
	</div>
@endif
