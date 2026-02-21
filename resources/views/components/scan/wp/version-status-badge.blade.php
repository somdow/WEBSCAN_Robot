@props(["versionStatus" => "unknown", "isPremium" => false])

@php
	if ($isPremium) {
		$badgeClasses = "bg-violet-50 text-violet-700 ring-violet-600/20";
		$label = "Premium";
	} else {
		$statusStyles = array(
			"current" => array("classes" => "bg-emerald-50 text-emerald-700 ring-emerald-600/20", "label" => "Up to Date"),
			"outdated" => array("classes" => "bg-amber-50 text-amber-700 ring-amber-600/20", "label" => "Outdated"),
			"unknown" => array("classes" => "bg-gray-50 text-gray-600 ring-gray-500/20", "label" => "Unknown"),
		);
		$resolved = $statusStyles[$versionStatus] ?? $statusStyles["unknown"];
		$badgeClasses = $resolved["classes"];
		$label = $resolved["label"];
	}
@endphp

<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ring-1 ring-inset {{ $badgeClasses }}">
	{{ $label }}
</span>
