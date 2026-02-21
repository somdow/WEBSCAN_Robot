{{-- Real-time scan progress — handles both server-rendered and AJAX-triggered scans --}}
@props(["scan" => null])

@php
	$circumference = config("scan-ui.score_ring_circumference");
	$hasActiveScan = $scan !== null;
	$initialPercent = $hasActiveScan ? ($scan->progress_percent ?? 0) : 0;
	$initialLabel = $hasActiveScan ? addslashes($scan->progress_label ?? "Preparing scan...") : "Preparing scan...";
	$pollUrl = $hasActiveScan ? route("scans.progress", $scan) : "";
@endphp

<div
	x-data="{
		percent: {{ $initialPercent }},
		label: '{{ $initialLabel }}',
		failed: false,
		visible: {{ $hasActiveScan ? 'true' : 'false' }},
		polling: null,
		pollUrl: '{{ $pollUrl }}',
		circumference: {{ $circumference }},

		get dashOffset() {
			return this.circumference * (1 - this.percent / 100);
		},

		startPolling() {
			this.$nextTick(() => this.visible = true);
			this.poll();
			this.polling = setInterval(() => this.poll(), 1500);
		},

		activateFromAjax(scanId) {
			this.pollUrl = '/scans/' + scanId + '/progress';
			this.percent = 0;
			this.label = 'Preparing scan...';
			this.failed = false;
			this.startPolling();
		},

		async poll() {
			try {
				const response = await fetch(this.pollUrl);
				if (!response.ok) return;

				const data = await response.json();
				this.percent = data.progress_percent;
				this.label = data.progress_label;

				if (data.is_complete) {
					clearInterval(this.polling);

					if (data.status === 'completed') {
						this.percent = 100;
						this.label = 'Scan complete! Loading results...';
						setTimeout(() => window.location.reload(), 1200);
					} else if (data.status === 'blocked') {
						this.failed = true;
						this.label = data.progress_label || 'Site has bot protection that prevented scanning.';
						setTimeout(() => window.location.reload(), 2500);
					} else {
						this.failed = true;
						this.label = data.progress_label || 'Scan encountered an error. Reloading...';
						setTimeout(() => window.location.reload(), 2500);
					}
				}
			} catch {
				{{-- Silently retry on next interval --}}
			}
		}
	}"
	x-init="{{ $hasActiveScan ? 'startPolling()' : '' }}"
	x-on:scan-started.window="activateFromAjax($event.detail.scanId)"
>
	{{-- Absolutely centered within the main content column --}}
	<div
		x-show="visible"
		x-transition:enter="transition-all duration-500 ease-out"
		x-transition:enter-start="opacity-0 scale-90"
		x-transition:enter-end="opacity-100 scale-100"
		x-cloak
		class="absolute inset-0 z-40 flex items-start justify-center pt-24"
	>
		<div class="w-72 rounded-lg border border-border bg-white p-6 shadow-card">
			{{-- Progress ring --}}
			<div class="relative mx-auto h-36 w-36">
				<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
					<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
					<circle
						cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round"
						:class="failed ? 'stroke-red-500' : 'stroke-orange-500'"
						:stroke-dasharray="circumference"
						:stroke-dashoffset="dashOffset"
						style="transition: stroke-dashoffset 1.2s cubic-bezier(0.4, 0, 0.2, 1)"
					/>
				</svg>
				<div class="absolute inset-0 flex flex-col items-center justify-center">
					<span
						class="tabular-nums text-3xl font-bold"
						:class="failed ? 'text-red-600' : 'text-orange-600'"
						x-text="`${percent}%`"
					>0%</span>
					<span class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-text-tertiary">
						Scanning
					</span>
				</div>
			</div>

			{{-- Step label --}}
			<p
				class="mt-4 text-center text-sm text-text-secondary"
				x-text="label"
			>Preparing scan...</p>

			{{-- Animated dots indicator --}}
			<div class="mt-3 flex items-center justify-center gap-1">
				<template x-if="!failed">
					<div class="flex items-center gap-1">
						<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-orange-400" style="animation-delay: 0ms"></span>
						<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-orange-400" style="animation-delay: 300ms"></span>
						<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-orange-400" style="animation-delay: 600ms"></span>
					</div>
				</template>
				<template x-if="failed">
					<svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
					</svg>
				</template>
			</div>
		</div>
	</div>
</div>
