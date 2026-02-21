{{-- Visual keyword location checklist for Content Keywords module --}}
{{-- Expects: $findings (array of finding objects with type and message) --}}

@props(["findings"])

@php
	$locationChecks = array();
	$locationLabels = array(
		"title tag" => "Title Tag",
		"H1 heading" => "H1 Heading",
		"first paragraph" => "First Paragraph",
		"URL path" => "URL Path",
		"meta description" => "Meta Description",
	);

	foreach ($findings as $finding) {
		$msg = $finding["message"] ?? "";
		$type = $finding["type"] ?? "info";

		foreach ($locationLabels as $needle => $label) {
			if (stripos($msg, $needle) !== false && (stripos($msg, "found in") !== false || stripos($msg, "not found in") !== false)) {
				$locationChecks[] = array(
					"label" => $label,
					"found" => $type === "ok",
				);
				break;
			}
		}
	}
@endphp

@if(!empty($locationChecks))
<div class="border-t border-border/40 px-5 py-4">
	<div class="mb-3 flex items-center gap-2">
		<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
			<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
		</svg>
		<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Keyword Locations</span>
	</div>

	<div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
		@foreach($locationChecks as $check)
			<div class="flex items-center gap-2 rounded-md px-3 py-2 {{ $check['found'] ? 'bg-emerald-50/60' : 'bg-red-50/60' }}">
				@if($check["found"])
					<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
					</svg>
				@else
					<svg class="h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
					</svg>
				@endif
				<span class="text-sm font-medium {{ $check['found'] ? 'text-emerald-700' : 'text-red-700' }}">{{ $check["label"] }}</span>
			</div>
		@endforeach
	</div>
</div>
@endif
