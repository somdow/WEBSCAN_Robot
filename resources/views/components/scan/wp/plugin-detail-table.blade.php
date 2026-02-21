@props(["detectedPlugins" => array()])

@php
	$pluginCount = count($detectedPlugins);

	/* Sort: vulnerable first (desc by count), then outdated, then current, then unknown */
	usort($detectedPlugins, function ($pluginA, $pluginB) {
		$vulnA = $pluginA["vulnerabilities_count"] ?? 0;
		$vulnB = $pluginB["vulnerabilities_count"] ?? 0;
		if ($vulnA !== $vulnB) {
			return $vulnB <=> $vulnA;
		}

		$statusOrder = array("outdated" => 0, "current" => 1, "unknown" => 2);
		$orderA = $statusOrder[$pluginA["version_status"] ?? "unknown"] ?? 2;
		$orderB = $statusOrder[$pluginB["version_status"] ?? "unknown"] ?? 2;
		if ($orderA !== $orderB) {
			return $orderA <=> $orderB;
		}

		return strcasecmp($pluginA["name"] ?? $pluginA["slug"], $pluginB["name"] ?? $pluginB["slug"]);
	});

	$vulnerableCount = count(array_filter($detectedPlugins, fn($p) => ($p["vulnerabilities_count"] ?? 0) > 0));
	$outdatedCount = count(array_filter($detectedPlugins, fn($p) => ($p["version_status"] ?? "unknown") === "outdated" && ($p["vulnerabilities_count"] ?? 0) === 0));
	$currentCount = count(array_filter($detectedPlugins, fn($p) => ($p["version_status"] ?? "unknown") === "current" && ($p["vulnerabilities_count"] ?? 0) === 0));
@endphp

@if($pluginCount > 0)
	<div class="mx-5 my-4 overflow-hidden rounded-lg border border-border bg-white shadow-sm">
		{{-- Section header --}}
		<div class="flex items-center justify-between border-b border-border/60 bg-gray-50/60 px-4 py-3">
			<div class="flex items-center gap-2.5">
				<div class="flex h-7 w-7 items-center justify-center rounded-md bg-orange-50">
					<svg class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875S10.5 3.09 10.5 4.125c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z" />
					</svg>
				</div>
				<div>
					<span class="text-[13px] font-semibold text-text-primary">Detected Plugins</span>
					<span class="ml-1.5 rounded-full bg-border/60 px-2 py-0.5 text-[10px] font-semibold text-text-tertiary">{{ $pluginCount }}</span>
				</div>
			</div>
			<div class="flex items-center gap-2">
				@if($vulnerableCount > 0)
					<span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">
						<svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
						{{ $vulnerableCount }} vulnerable
					</span>
				@endif
				@if($outdatedCount > 0)
					<span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">{{ $outdatedCount }} outdated</span>
				@endif
				@if($currentCount > 0)
					<span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">{{ $currentCount }} current</span>
				@endif
			</div>
		</div>

		{{-- Plugin table --}}
		<div class="overflow-x-auto">
			{{-- Column headers --}}
			<div class="grid grid-cols-[1fr_5rem_5rem_6rem_6rem] border-b border-border/40 bg-gray-50/40 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-text-tertiary">
				<span>Plugin</span>
				<span>Detected</span>
				<span>Latest</span>
				<span>Status</span>
				<span class="text-center">Vulns</span>
			</div>

			{{-- Plugin rows --}}
			<div class="divide-y divide-border/50" x-data="{ openPlugin: '' }">
				@foreach($detectedPlugins as $plugin)
					@php
						$pluginSlug = $plugin["slug"] ?? "";
						$pluginName = $plugin["name"] ?? ucwords(str_replace("-", " ", $pluginSlug));
						$detectedVersion = $plugin["detected_version"] ?? null;
						$latestVersion = $plugin["latest_version"] ?? null;
						$versionStatus = $plugin["version_status"] ?? "unknown";
						$isPremium = $plugin["is_premium"] ?? false;
						$pluginVulnCount = $plugin["vulnerabilities_count"] ?? 0;
						$vulnerabilities = $plugin["vulnerabilities"] ?? array();
						$hasVulns = $pluginVulnCount > 0 && !empty($vulnerabilities);
					@endphp

					<div>
						<div
							@if($hasVulns)
								@click="openPlugin = openPlugin === '{{ $pluginSlug }}' ? '' : '{{ $pluginSlug }}'"
								class="grid cursor-pointer grid-cols-[1fr_5rem_5rem_6rem_6rem] items-center px-4 py-2.5 text-[12px] transition hover:bg-orange-50/40"
							@else
								class="grid grid-cols-[1fr_5rem_5rem_6rem_6rem] items-center px-4 py-2.5 text-[12px] transition hover:bg-gray-50/60"
							@endif
						>
							{{-- Plugin name --}}
							<span class="min-w-0 font-medium text-text-primary">
								{{ $pluginName }}
								@if($isPremium)
									<span class="ml-1 inline-flex rounded bg-violet-100 px-1 py-0.5 text-[9px] font-bold uppercase text-violet-700">Premium</span>
								@endif
							</span>

							{{-- Detected version --}}
							<span class="text-text-secondary">
								@if($detectedVersion)
									<span class="font-mono text-[11px]">{{ $detectedVersion }}</span>
								@else
									<span class="italic text-text-tertiary">N/A</span>
								@endif
							</span>

							{{-- Latest version --}}
							<span class="text-text-secondary">
								@if($isPremium)
									<span class="text-text-tertiary">N/A</span>
								@elseif($latestVersion)
									<span class="font-mono text-[11px]">{{ $latestVersion }}</span>
								@else
									<span class="italic text-text-tertiary">N/A</span>
								@endif
							</span>

							{{-- Status badge --}}
							<span>
								<x-scan.wp.version-status-badge :versionStatus="$versionStatus" :isPremium="$isPremium" />
							</span>

							{{-- Vulnerability count --}}
							<span class="text-center">
								@if($hasVulns)
									<span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">
										{{ $pluginVulnCount }}
										<svg :class="openPlugin === '{{ $pluginSlug }}' ? 'rotate-180' : ''" class="h-2.5 w-2.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
										</svg>
									</span>
								@else
									<span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">None</span>
								@endif
							</span>
						</div>

						{{-- Expanded vulnerability details --}}
						@if($hasVulns)
							<div x-show="openPlugin === '{{ $pluginSlug }}'" x-collapse x-cloak>
								<div class="border-t border-red-200/40 bg-red-50 px-5 py-3">
									<div class="mb-1.5 flex items-center justify-between">
										<span class="text-[10px] font-bold uppercase tracking-wider text-red-900">Known Vulnerabilities</span>
										@if(!$isPremium && $pluginSlug)
											<a href="https://wordpress.org/plugins/{{ $pluginSlug }}/" target="_blank" rel="noopener noreferrer" @click.stop class="inline-flex items-center gap-1 text-[10px] font-semibold text-accent transition hover:text-accent-hover hover:underline">
												View Plugin
												<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
											</a>
										@endif
									</div>
									<div class="divide-y divide-red-200/50">
										@foreach($vulnerabilities as $vulnerability)
											<x-scan.wp.vulnerability-row :vulnerability="$vulnerability" />
										@endforeach
									</div>
								</div>
							</div>
						@endif
					</div>
				@endforeach
			</div>
		</div>
	</div>
@endif
