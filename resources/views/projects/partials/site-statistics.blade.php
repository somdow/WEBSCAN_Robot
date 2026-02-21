{{-- Consolidated site statistics: carousel (score + sub-scores) + trend + history --}}
{{-- Expects: $scan, $scanViewData (nullable). Uses parent scope: $completedScans, $project, $latestScan --}}

@php
	$hasScoreData = $scan->overall_score !== null && $scanViewData;

	if ($hasScoreData) {
		$circumference = config("scan-ui.score_ring_circumference");

		$scoreSlides = array();
		if ($scan->seo_score !== null) {
			$scoreSlides[] = array(
				"label" => "SEO Score",
				"dotColor" => "bg-indigo-500",
			);
		}
		if ($scan->health_score !== null) {
			$scoreSlides[] = array(
				"label" => "Site Health",
				"dotColor" => "bg-teal-500",
			);
		}
	}

	$gridCols = match (true) {
		$hasScoreData => "lg:grid-cols-4",
		default => "lg:grid-cols-2",
	};
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:h-52 {{ $gridCols }}">
	@if($hasScoreData)
		{{-- Column 1: Homepage Screenshot --}}
		<div class="rounded-lg border border-border bg-surface shadow-card overflow-hidden flex flex-col">
			@if($scan->getScreenshotUrl())
				<img
					src="{{ $scan->getScreenshotUrl() }}"
					alt="Screenshot of {{ $scan->project->url }}"
					class="h-full w-full object-cover object-top"
				/>
			@else
				<div class="flex flex-1 items-center justify-center">
					<svg class="mr-1.5 h-4 w-4 text-text-tertiary/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
					</svg>
					<p class="text-xs text-text-tertiary">No screenshot available</p>
				</div>
			@endif
		</div>

	@endif

	{{-- Score Trend Chart --}}
	<div class="rounded-lg border border-border bg-surface p-4 shadow-card overflow-hidden flex flex-col">
		<h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-text-tertiary">Score Trend</h4>
		@if($completedScans->count() >= 2)
			<div class="flex-1 min-h-0">
				<x-score-trend-chart :scans="$completedScans" height="h-full" />
			</div>
		@elseif($completedScans->count() === 1)
			<div class="flex flex-1 min-h-0 items-center justify-center">
				<p class="text-center text-xs text-text-tertiary">Run another scan to see your score trend.</p>
			</div>
		@else
			<div class="flex flex-1 min-h-0 items-center justify-center">
				<p class="text-center text-xs text-text-tertiary">No trend data yet.</p>
			</div>
		@endif
	</div>

	{{-- Scan History --}}
	<div class="rounded-lg border border-border bg-surface p-4 shadow-card overflow-hidden flex flex-col">
		<h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-text-tertiary">Scan History</h4>
		@if($project->ownScans->isEmpty())
			<div class="flex flex-1 min-h-0 items-center justify-center">
				<p class="text-xs text-text-tertiary">No scans yet.</p>
			</div>
		@else
			<div class="overflow-y-auto flex-1 min-h-0">
				<table class="w-full">
					<thead>
						<tr class="border-b border-border text-left text-[10px] font-medium uppercase tracking-wider text-text-tertiary">
							<th class="pb-1.5 pr-2">Date</th>
							<th class="pb-1.5 text-center">Score</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-border/50">
						@foreach($project->ownScans->take(7) as $historyScan)
							<tr class="transition hover:bg-background {{ $latestScan && $historyScan->id === $latestScan->id ? 'bg-orange-50/50 border-l-2 border-l-orange-400' : '' }}">
								<td class="py-1.5 pr-2 text-xs text-text-secondary">
									@if($historyScan->status === \App\Enums\ScanStatus::Completed)
										<a href="{{ route("projects.show", array("project" => $scan->project, "scan" => $historyScan)) }}" class="pl-1.5 hover:text-accent">
											{{ $historyScan->created_at->diffForHumans() }}
										</a>
									@else
										{{ $historyScan->created_at->diffForHumans() }}
									@endif
								</td>
								<td class="py-1.5 text-center">
									@if($historyScan->overall_score !== null)
										<span class="text-xs font-semibold {{ $historyScan->scoreColorClass() }}">{{ $historyScan->overall_score }}</span>
									@else
										<span class="text-xs text-text-tertiary">&mdash;</span>
									@endif
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@endif
	</div>

	@if($hasScoreData)
		{{-- Score Carousel: Overall + SEO + Site Health (reactive via Alpine) --}}
		@php $totalSlides = 1 + count($scoreSlides); @endphp
		<div
			x-data="scoreCarousel()"
			x-on:score-tab-changed.window="navigate($event.detail.slide)"
			x-on:scores-updated.window="updateScores($event.detail)"
			class="rounded-lg border border-transparent p-4 shadow-card flex flex-col overflow-hidden transition-colors duration-500"
			:class="{
				'bg-emerald-50 border-emerald-200': activeScore() >= 80,
				'bg-amber-50 border-amber-200': activeScore() >= 50 && activeScore() < 80,
				'bg-red-50 border-red-200': activeScore() < 50,
			}"
		>
			{{-- Slide content area --}}
			<div class="flex-1 min-h-0 relative">
				@if($totalSlides > 1)
					{{-- Left arrow --}}
					<button @click.stop="navigate((slide + total - 1) % total)" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-white/70 text-text-tertiary shadow-sm transition hover:bg-white hover:text-text-primary" aria-label="Previous slide">
						<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
					</button>

					{{-- Right arrow --}}
					<button @click.stop="navigate((slide + 1) % total)" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-white/70 text-text-tertiary shadow-sm transition hover:bg-white hover:text-text-primary" aria-label="Next slide">
						<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
					</button>
				@endif

				{{-- Slide 0: Overall Score Ring --}}
				<div x-show="slide === 0" class="flex h-full items-center justify-center px-8">
					<div class="relative h-full max-h-40 aspect-square">
						<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
							<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
							<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round"
								:class="strokeClass(overallScore)"
								:stroke-dasharray="circumference"
								:stroke-dashoffset="circumference * (1 - overallScore / 100)"
								style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)"
							/>
						</svg>
						<div class="absolute inset-0 flex flex-col items-center justify-center">
							<span class="text-3xl font-bold" :class="colorClass(overallScore)">
								<span x-text="overallScore"></span><span class="text-lg font-medium opacity-50">/100</span>
							</span>
							<span class="text-[10px] font-semibold uppercase tracking-wider mt-0.5" style="color: #555;">Overall Score</span>
						</div>
					</div>
				</div>

				{{-- Slide 1: SEO Score Ring --}}
				@if(count($scoreSlides) >= 1)
					<div x-show="slide === 1" x-cloak class="flex h-full items-center justify-center px-8">
						<div class="relative h-full max-h-40 aspect-square">
							<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
								<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
								<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round"
									:class="strokeClass(seoScore)"
									:stroke-dasharray="circumference"
									:stroke-dashoffset="circumference * (1 - seoScore / 100)"
									style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)"
								/>
							</svg>
							<div class="absolute inset-0 flex flex-col items-center justify-center">
								<span class="text-3xl font-bold" :class="colorClass(seoScore)">
									<span x-text="seoScore"></span><span class="text-lg font-medium opacity-50">/100</span>
								</span>
								<span class="text-[10px] font-semibold uppercase tracking-wider mt-0.5" style="color: #555;">SEO Score</span>
							</div>
						</div>
					</div>
				@endif

				{{-- Slide 2: Site Health Score Ring --}}
				@if(count($scoreSlides) >= 2)
					<div x-show="slide === 2" x-cloak class="flex h-full items-center justify-center px-8">
						<div class="relative h-full max-h-40 aspect-square">
							<svg class="h-full w-full -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
								<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" class="stroke-gray-200" />
								<circle cx="40" cy="40" r="34" fill="none" stroke-width="4" stroke-linecap="round"
									:class="strokeClass(healthScore)"
									:stroke-dasharray="circumference"
									:stroke-dashoffset="circumference * (1 - healthScore / 100)"
									style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1)"
								/>
							</svg>
							<div class="absolute inset-0 flex flex-col items-center justify-center">
								<span class="text-3xl font-bold" :class="colorClass(healthScore)">
									<span x-text="healthScore"></span><span class="text-lg font-medium opacity-50">/100</span>
								</span>
								<span class="text-[10px] font-semibold uppercase tracking-wider mt-0.5" style="color: #555;">Site Health</span>
							</div>
						</div>
					</div>
				@endif
			</div>

			{{-- Dot indicators --}}
			@if($totalSlides > 1)
				<div class="flex justify-center gap-2 pt-2">
					<button @click.stop="navigate(0)" :class="slide === 0 ? 'bg-orange-500 w-4' : 'bg-gray-300 w-1.5'" class="h-1.5 rounded-full transition-all duration-200" aria-label="Show overall score"></button>
					@foreach($scoreSlides as $dotIndex => $dotSlide)
						<button @click.stop="navigate({{ $dotIndex + 1 }})" :class="slide === {{ $dotIndex + 1 }} ? '{{ $dotSlide["dotColor"] }} w-4' : 'bg-gray-300 w-1.5'" class="h-1.5 rounded-full transition-all duration-200" aria-label="Show {{ strtolower($dotSlide['label']) }}"></button>
					@endforeach
				</div>
			@endif
		</div>

		<script>
		function scoreCarousel() {
			return {
				slide: 0,
				total: {{ $totalSlides }},
				circumference: {{ $circumference }},
				overallScore: {{ $scan->overall_score ?? 0 }},
				seoScore: {{ $scan->seo_score ?? 0 }},
				healthScore: {{ $scan->health_score ?? 0 }},

				navigate(index) {
					if (index >= 0 && index < this.total) {
						this.slide = index;
					}
				},

				updateScores(scores) {
					if (scores.overall_score !== undefined && scores.overall_score !== null) this.overallScore = scores.overall_score;
					if (scores.seo_score !== undefined && scores.seo_score !== null) this.seoScore = scores.seo_score;
					if (scores.health_score !== undefined && scores.health_score !== null) this.healthScore = scores.health_score;
				},

				colorClass(score) {
					if (score >= 80) return 'text-emerald-600';
					if (score >= 50) return 'text-amber-600';
					return 'text-red-600';
				},

				strokeClass(score) {
					if (score >= 80) return 'stroke-emerald-500';
					if (score >= 50) return 'stroke-amber-500';
					return 'stroke-red-500';
				},

				activeScore() {
					if (this.slide === 1) return this.seoScore;
					if (this.slide === 2) return this.healthScore;
					return this.overallScore;
				},
			};
		}
		</script>
	@endif
</div>
