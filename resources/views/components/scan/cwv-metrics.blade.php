{{-- Core Web Vitals metrics display: LCP, CLS, INP/TBT + supplemental FCP, Speed Index --}}
@props(["cwvMetrics", "dataSource" => "lab"])

@php
	$ratingColorMap = array(
		"good" => array("bg" => "bg-emerald-100", "border" => "border-emerald-300", "text" => "text-emerald-800", "label" => "text-emerald-700", "badge" => "bg-emerald-200 text-emerald-800"),
		"needs-improvement" => array("bg" => "bg-amber-100", "border" => "border-amber-300", "text" => "text-amber-800", "label" => "text-amber-700", "badge" => "bg-amber-200 text-amber-800"),
		"poor" => array("bg" => "bg-red-100", "border" => "border-red-300", "text" => "text-red-800", "label" => "text-red-700", "badge" => "bg-red-200 text-red-800"),
		"unknown" => array("bg" => "bg-gray-100", "border" => "border-gray-300", "text" => "text-gray-600", "label" => "text-gray-500", "badge" => "bg-gray-200 text-gray-600"),
	);

	$ratingLabelMap = array(
		"good" => "Good",
		"needs-improvement" => "Needs Improvement",
		"poor" => "Poor",
		"unknown" => "No Data",
	);

	$coreMetrics = array();
	$supplementalMetrics = array();

	foreach ($cwvMetrics as $key => $metric) {
		if (!empty($metric["supplemental"])) {
			$supplementalMetrics[$key] = $metric;
		} else {
			$coreMetrics[$key] = $metric;
		}
	}
@endphp

<div class="border-t border-border/40 px-5 py-4">
	{{-- Data source label --}}
	<div class="mb-3 flex items-center gap-2">
		<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5" />
		</svg>
		<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Core Web Vitals</span>
		@if($dataSource === "field")
			<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
				<svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
				</svg>
				Real User Data (CrUX)
			</span>
		@else
			<span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
				<svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
				</svg>
				Lab Data (Lighthouse)
			</span>
		@endif
	</div>

	{{-- Core CWV metric cards --}}
	@if(!empty($coreMetrics))
		<div class="grid grid-cols-3 gap-3 mb-3">
			@foreach($coreMetrics as $metricKey => $metric)
				@php
					$rating = $metric["rating"] ?? "unknown";
					$colors = $ratingColorMap[$rating] ?? $ratingColorMap["unknown"];
					$ratingLabel = $ratingLabelMap[$rating] ?? "Unknown";
					$displayValue = $metric["displayValue"] ?? "N/A";
					$shortLabel = $metric["shortLabel"] ?? strtoupper($metricKey);
					$fullLabel = $metric["label"] ?? $shortLabel;
					$isLabProxy = !empty($metric["isLabProxy"]);
				@endphp
				<div class="rounded-lg border {{ $colors['bg'] }} {{ $colors['border'] }} px-4 py-4 text-center">
					<div class="text-xs font-bold uppercase tracking-wide {{ $colors['text'] }}">
						{{ $shortLabel }}
						@if($isLabProxy)
							<span class="text-[10px] font-normal normal-case opacity-70">(lab proxy)</span>
						@endif
					</div>
					<div class="mt-2 text-2xl font-extrabold {{ $colors['text'] }}">{{ $displayValue }}</div>
					<div class="mt-1.5 inline-flex items-center gap-1 rounded-full {{ $colors['badge'] }} px-2.5 py-0.5 text-[11px] font-bold">
						@if($rating === "good")
							<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
						@elseif($rating === "poor")
							<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126Z" /></svg>
						@elseif($rating === "needs-improvement")
							<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
						@endif
						{{ $ratingLabel }}
					</div>
					<div class="mt-2 text-[11px] font-medium {{ $colors['label'] }}">{{ $fullLabel }}</div>
				</div>
			@endforeach
		</div>
	@endif

	{{-- Supplemental metrics (FCP, Speed Index) --}}
	@if(!empty($supplementalMetrics))
		<div class="grid grid-cols-2 gap-3">
			@foreach($supplementalMetrics as $metricKey => $metric)
				@php
					$rating = $metric["rating"] ?? "unknown";
					$colors = $ratingColorMap[$rating] ?? $ratingColorMap["unknown"];
					$ratingLabel = $ratingLabelMap[$rating] ?? "Unknown";
					$displayValue = $metric["displayValue"] ?? "N/A";
					$shortLabel = $metric["shortLabel"] ?? strtoupper($metricKey);
					$fullLabel = $metric["label"] ?? $shortLabel;
				@endphp
				<div class="flex items-center justify-between rounded-lg border {{ $colors['bg'] }} {{ $colors['border'] }} px-4 py-3">
					<div>
						<div class="text-xs font-bold uppercase tracking-wide {{ $colors['text'] }}">{{ $shortLabel }}</div>
						<div class="text-[11px] font-medium {{ $colors['label'] }}">{{ $fullLabel }}</div>
					</div>
					<div class="text-right">
						<div class="text-lg font-extrabold {{ $colors['text'] }}">{{ $displayValue }}</div>
						<div class="text-[11px] font-bold {{ $colors['label'] }}">{{ $ratingLabel }}</div>
					</div>
				</div>
			@endforeach
		</div>
	@endif
</div>
