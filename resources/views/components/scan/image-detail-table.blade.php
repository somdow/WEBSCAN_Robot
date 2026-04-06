@props(["imageDetails" => array()])

@php
	$imageCount = count($imageDetails);

	/* Sort: missing alt first, then empty alt, then ok */
	$statusOrder = array("missing" => 0, "empty" => 1, "ok" => 2);
	usort($imageDetails, function ($imageA, $imageB) use ($statusOrder) {
		$orderA = $statusOrder[$imageA["alt_status"] ?? "ok"] ?? 2;
		$orderB = $statusOrder[$imageB["alt_status"] ?? "ok"] ?? 2;
		return $orderA <=> $orderB;
	});
@endphp

@if($imageCount > 0)
	<div
		class="border-t border-border/40 px-5 py-3"
		x-data="{
			showImageGrid: true,
			copiedIndex: null,
			aiSuggestionMap: {},
			aiOverallTip: '',
			copyAltText(text, index) {
				if (navigator.clipboard && window.isSecureContext) {
					navigator.clipboard.writeText(text);
				} else {
					const textarea = document.createElement('textarea');
					textarea.value = text;
					textarea.style.position = 'fixed';
					textarea.style.opacity = '0';
					document.body.appendChild(textarea);
					textarea.select();
					document.execCommand('copy');
					document.body.removeChild(textarea);
				}
				this.copiedIndex = index;
				setTimeout(() => this.copiedIndex = null, 2000);
			},
		}"
		x-effect="
			const map = {};
			let tipText = '';
			const raw = (typeof aiSuggestion === 'string') ? aiSuggestion : '';
			if (raw.length > 0) {
				const lines = raw.split('\n');
				let currentImage = null;
				let lastAltLine = -1;
				for (let i = 0; i < lines.length; i++) {
					const trimmed = lines[i].trim();
					if (trimmed.toUpperCase().startsWith('IMAGE:')) {
						currentImage = trimmed.substring(6).trim().toLowerCase();
					} else if (trimmed.toUpperCase().startsWith('ALT:') && currentImage !== null) {
						map[currentImage] = trimmed.substring(4).trim();
						currentImage = null;
						lastAltLine = i;
					}
				}
				if (lastAltLine >= 0 && lastAltLine < lines.length - 1) {
					tipText = lines.slice(lastAltLine + 1).join('\n').trim();
				}
			}
			aiSuggestionMap = map;
			aiOverallTip = tipText;
		"
	>
		<button
			@click="showImageGrid = !showImageGrid"
			class="flex w-full cursor-pointer items-center gap-2 text-left text-[13px] font-semibold text-text-primary transition hover:text-accent"
		>
			<svg
				:class="showImageGrid ? 'rotate-90' : ''"
				class="h-3.5 w-3.5 shrink-0 text-text-tertiary transition-transform duration-200"
				fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"
			>
				<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
			</svg>
			Show Image Details ({{ $imageCount }})
		</button>

		<div x-show="showImageGrid" x-collapse x-cloak class="mt-3">
			<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
				@foreach($imageDetails as $image)
					@php
						$imageSrc = $image["src"] ?? "";
						$imageAlt = $image["alt"] ?? null;
						$altStatus = $image["alt_status"] ?? "ok";

						$statusBadge = match ($altStatus) {
							"missing" => array("label" => "MISSING", "class" => "bg-red-100 text-red-700"),
							"empty" => array("label" => "EMPTY", "class" => "bg-amber-100 text-amber-700"),
							default => array("label" => "OK", "class" => "bg-emerald-100 text-emerald-700"),
						};

						$borderColor = match ($altStatus) {
							"missing" => "border-red-300",
							"empty" => "border-amber-300",
							default => "border-border/60",
						};

						$urlPath = parse_url($imageSrc, PHP_URL_PATH);
						$fileName = $urlPath ? basename($urlPath) : $imageSrc;
						/* Handle CDN/image optimizer URLs (Next.js /_next/image, etc.) */
						if (!str_contains($fileName, ".") || $fileName === "image") {
							$cdnQuery = parse_url($imageSrc, PHP_URL_QUERY);
							if ($cdnQuery) {
								parse_str($cdnQuery, $cdnParams);
								foreach (array("url", "src", "source", "img") as $cdnKey) {
									if (!empty($cdnParams[$cdnKey])) {
										$innerPath = parse_url(urldecode($cdnParams[$cdnKey]), PHP_URL_PATH);
										if ($innerPath && str_contains(basename($innerPath), ".")) {
											$fileName = basename($innerPath);
											break;
										}
									}
								}
							}
						}
						$fileNameKey = Js::from(strtolower($fileName));
					@endphp

					<div class="flex flex-col overflow-hidden rounded-lg border {{ $borderColor }} bg-white shadow-sm">
						{{-- Image preview --}}
						<a href="{{ $imageSrc }}" target="_blank" rel="noopener noreferrer" class="group block">
							<div class="relative aspect-[4/3] overflow-hidden bg-gray-50">
								<img
									src="{{ $imageSrc }}"
									alt="{{ $imageAlt ?? '' }}"
									class="h-full w-full object-contain p-2 transition duration-200 group-hover:scale-105"
									loading="lazy"
									onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
								/>
								<div class="hidden h-full w-full items-center justify-center bg-gray-50 text-text-tertiary" style="display:none">
									<svg class="h-8 w-8 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
									</svg>
								</div>

								{{-- Status badge overlay --}}
								<span class="absolute right-1.5 top-1.5 rounded-full {{ $statusBadge['class'] }} px-2 py-0.5 text-[9px] font-bold shadow-sm">{{ $statusBadge["label"] }}</span>
							</div>
						</a>

						{{-- Card footer: filename + alt text --}}
						<div class="flex-1 px-3 py-2.5">
							<a
								href="{{ $imageSrc }}"
								target="_blank"
								rel="noopener noreferrer"
								class="block truncate font-mono text-[11px] text-text-secondary transition hover:text-accent hover:underline"
								title="{{ $imageSrc }}"
							>{{ $fileName }}</a>

							<div class="mt-1.5 text-[11px]">
								<span class="font-semibold text-text-tertiary">Alt:</span>
								@if($altStatus === "missing")
									<span class="italic text-red-500">No alt attribute</span>
								@elseif($altStatus === "empty")
									<span class="italic text-amber-500">Empty alt=""</span>
								@else
									<span class="text-text-secondary" title="{{ $imageAlt }}">{{ Str::limit($imageAlt, 60) }}</span>
								@endif
							</div>
						</div>

						{{-- AI suggested alt text (appears after optimization) --}}
						<div
							x-show="aiState === 'success' && aiSuggestionMap[{{ $fileNameKey }}]"
							x-cloak
							@click.stop="copyAltText(aiSuggestionMap[{{ $fileNameKey }}], {{ $loop->index }})"
							class="cursor-pointer border-t border-ai-border bg-ai px-3 py-2 transition hover:brightness-110"
							title="Click to copy alt text"
						>
							<div class="flex items-start gap-1.5">
								<svg class="mt-0.5 h-3 w-3 shrink-0 text-white/70" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
								</svg>
								<div class="min-w-0 flex-1">
									<span
										x-show="copiedIndex !== {{ $loop->index }}"
										class="text-[10px] font-semibold uppercase tracking-wider text-white/70"
									>AI Alt Text — click to copy</span>
									<span
										x-show="copiedIndex === {{ $loop->index }}"
										x-cloak
										class="text-[10px] font-semibold uppercase tracking-wider text-emerald-300"
									>Copied!</span>
									<p
										class="mt-0.5 text-[11px] leading-relaxed text-white/90"
										x-text="aiSuggestionMap[{{ $fileNameKey }}]"
									></p>
								</div>
								<div class="shrink-0 text-white/50">
									<template x-if="copiedIndex !== {{ $loop->index }}">
										<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
										</svg>
									</template>
									<template x-if="copiedIndex === {{ $loop->index }}">
										<svg class="h-3.5 w-3.5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
										</svg>
									</template>
								</div>
							</div>
						</div>
					</div>
				@endforeach
			</div>

			{{-- AI overall strategy tip --}}
			<div
				x-show="aiState === 'success' && aiOverallTip.length > 0"
				x-cloak
				class="mt-3 rounded-lg border border-ai-border bg-ai px-4 py-3"
			>
				<div class="flex items-start gap-2">
					<svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-white/70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
					</svg>
					<p class="text-[12px] leading-relaxed text-white/90" x-text="aiOverallTip"></p>
				</div>
			</div>

			{{-- Fallback: show full AI text if no per-image suggestions could be parsed --}}
			<div
				x-show="aiState === 'success' && aiSuggestion && Object.keys(aiSuggestionMap).length === 0"
				x-cloak
				class="mt-3 rounded-lg border border-ai-border bg-ai px-4 py-3"
			>
				<div class="flex items-start gap-2">
					<svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-white/70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
					</svg>
					<div class="min-w-0 flex-1">
						<span class="text-[10px] font-semibold uppercase tracking-wider text-white/70">AI Suggestion</span>
						<p class="mt-1 whitespace-pre-line text-[12px] leading-relaxed text-white/90" x-text="aiSuggestion"></p>
					</div>
				</div>
			</div>
		</div>
	</div>
@endif
