{{-- Score overview cards with clickable status filters (single-page scan) --}}
{{-- Expects parent x-data scope with: activeCategory, statusFilter --}}
{{-- Expects variables: $scan, $statusCounts, $defaultCategory --}}

@if($scan->overall_score !== null)
	@php
		$passedCount = $statusCounts["ok"] ?? 0;
		$warningCount = $statusCounts["warning"] ?? 0;
		$failedCount = $statusCounts["bad"] ?? 0;
		$infoCount = $statusCounts["info"] ?? 0;
		$totalModules = $passedCount + $warningCount + $failedCount + $infoCount;

		$circumference = config("scan-ui.score_ring_circumference");
		$scoreBgClass = match (true) {
			$scan->overall_score >= 80 => "bg-emerald-50 border-emerald-200",
			$scan->overall_score >= 50 => "bg-amber-50 border-amber-200",
			default => "bg-red-50 border-red-200",
		};
	@endphp

	<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
		{{-- Overall Score with Ring — clears filter --}}
		<button
			@click="statusFilter = ''; activeCategory = '{{ $defaultCategory }}'; $nextTick(() => { let el = document.getElementById('results-panel'); if (el) el.scrollIntoView({ behavior: 'smooth' }) })"
			:class="!statusFilter ? 'ring-2 ring-orange-400 ring-offset-2' : ''"
			class="cursor-pointer rounded-lg border {{ $scoreBgClass }} p-5 shadow-card text-left transition-all duration-200 hover:shadow-card-hover"
		>
			<div class="flex items-center gap-4">
				<div class="relative h-16 w-16 shrink-0">
					<svg class="h-16 w-16 -rotate-90" viewBox="0 0 80 80">
						<circle cx="40" cy="40" r="34" fill="none" stroke-width="5" class="stroke-gray-100" />
						<circle cx="40" cy="40" r="34" fill="none" stroke-width="5" stroke-linecap="round" class="{{ $scan->scoreStrokeClass() }}" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference * (1 - $scan->overall_score / 100) }}" />
					</svg>
					<div class="absolute inset-0 flex items-center justify-center">
						<span class="text-lg font-bold {{ $scan->scoreColorClass() }}">{{ $scan->overall_score }}</span>
					</div>
				</div>
				<div>
					<p class="text-xs font-medium uppercase tracking-wider text-text-tertiary">Website Health</p>
					<p class="mt-0.5 text-xs text-text-tertiary">{{ $totalModules }} modules</p>
				</div>
			</div>
		</button>

		{{-- Passed --}}
		<button
			@click="statusFilter = statusFilter === 'ok' ? '' : 'ok'; if (statusFilter) activeCategory = ''; $nextTick(() => { let el = document.getElementById('results-panel'); if (el) el.scrollIntoView({ behavior: 'smooth' }) })"
			:class="statusFilter === 'ok' ? 'ring-2 ring-emerald-400 ring-offset-2' : ''"
			class="cursor-pointer rounded-lg border border-border bg-surface p-5 shadow-card text-left transition-all duration-200 hover:shadow-card-hover"
		>
			<div class="flex items-center gap-3">
				<div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50">
					<svg class="h-4.5 w-4.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
				</div>
				<div>
					<p class="text-xs font-medium text-text-tertiary">Passed</p>
					<p class="text-2xl font-bold tracking-tight text-text-primary">{{ $passedCount }}</p>
				</div>
			</div>
		</button>

		{{-- Warnings --}}
		<button
			@click="statusFilter = statusFilter === 'warning' ? '' : 'warning'; if (statusFilter) activeCategory = ''; $nextTick(() => { let el = document.getElementById('results-panel'); if (el) el.scrollIntoView({ behavior: 'smooth' }) })"
			:class="statusFilter === 'warning' ? 'ring-2 ring-amber-400 ring-offset-2' : ''"
			class="cursor-pointer rounded-lg border border-border bg-surface p-5 shadow-card text-left transition-all duration-200 hover:shadow-card-hover"
		>
			<div class="flex items-center gap-3">
				<div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50">
					<svg class="h-4.5 w-4.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
				</div>
				<div>
					<p class="text-xs font-medium text-text-tertiary">Warnings</p>
					<p class="text-2xl font-bold tracking-tight text-text-primary">{{ $warningCount }}</p>
				</div>
			</div>
		</button>

		{{-- Failed --}}
		<button
			@click="statusFilter = statusFilter === 'bad' ? '' : 'bad'; if (statusFilter) activeCategory = ''; $nextTick(() => { let el = document.getElementById('results-panel'); if (el) el.scrollIntoView({ behavior: 'smooth' }) })"
			:class="statusFilter === 'bad' ? 'ring-2 ring-red-400 ring-offset-2' : ''"
			class="cursor-pointer rounded-lg border border-border bg-surface p-5 shadow-card text-left transition-all duration-200 hover:shadow-card-hover"
		>
			<div class="flex items-center gap-3">
				<div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50">
					<svg class="h-4.5 w-4.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
				</div>
				<div>
					<p class="text-xs font-medium text-text-tertiary">Failed</p>
					<p class="text-2xl font-bold tracking-tight text-text-primary">{{ $failedCount }}</p>
				</div>
			</div>
		</button>
	</div>
@endif
