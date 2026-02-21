{{-- Reusable Passed / Warnings / Failed filter card row --}}
{{-- Requires parent Alpine scope with `statusFilter` variable --}}
@props(["passedCount", "warningCount", "failedCount"])

@php
	$filterCards = array(
		array("key" => "ok", "label" => "Passed", "count" => $passedCount, "iconBg" => "bg-emerald-50", "iconColor" => "text-emerald-500", "iconPath" => "m4.5 12.75 6 6 9-13.5", "activeRing" => "ring-2 ring-emerald-400 border-emerald-300 bg-emerald-50"),
		array("key" => "warning", "label" => "Warnings", "count" => $warningCount, "iconBg" => "bg-amber-50", "iconColor" => "text-amber-500", "iconPath" => "M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z", "activeRing" => "ring-2 ring-amber-400 border-amber-300 bg-amber-50"),
		array("key" => "bad", "label" => "Failed", "count" => $failedCount, "iconBg" => "bg-red-50", "iconColor" => "text-red-500", "iconPath" => "M6 18 18 6M6 6l12 12", "activeRing" => "ring-2 ring-red-400 border-red-300 bg-red-50"),
	);
@endphp

<p class="mb-2 text-xs font-semibold uppercase tracking-wider" style="color: #383838;">Filter by Status</p>
<div {{ $attributes->merge(array("class" => "grid grid-cols-3 gap-4")) }}>
	@foreach($filterCards as $card)
		<button
			@click="statusFilter = statusFilter === '{{ $card['key'] }}' ? '' : '{{ $card['key'] }}'"
			:class="statusFilter === '{{ $card['key'] }}' ? '{{ $card['activeRing'] }}' : 'border-border bg-surface hover:bg-gray-50'"
			class="cursor-pointer rounded-lg border border-border bg-surface p-5 shadow-card text-left transition-all duration-150"
		>
			<div class="flex items-center gap-3">
				<div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $card['iconBg'] }}">
					<svg class="h-4.5 w-4.5 {{ $card['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['iconPath'] }}" />
					</svg>
				</div>
				<div>
					<p class="text-xs font-medium text-text-tertiary">{{ $card["label"] }}</p>
					<p class="text-2xl font-bold tracking-tight text-text-primary">{{ $card["count"] }}</p>
				</div>
			</div>
		</button>
	@endforeach
</div>
