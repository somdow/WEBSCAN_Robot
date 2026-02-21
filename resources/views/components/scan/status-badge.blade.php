@props(["status"])

@php
$statusValue = $status instanceof \BackedEnum ? $status->value : $status;

$styleMap = array(
	"ok" => "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
	"warning" => "bg-amber-50 text-amber-700 ring-amber-600/20",
	"bad" => "bg-red-50 text-red-700 ring-red-600/20",
	"info" => "bg-blue-50 text-blue-700 ring-blue-600/20",
	"pending" => "bg-amber-50 text-amber-700 ring-amber-600/20",
	"running" => "bg-blue-50 text-blue-700 ring-blue-600/20",
	"completed" => "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
	"failed" => "bg-red-50 text-red-700 ring-red-600/20",
	"blocked" => "bg-orange-50 text-orange-700 ring-orange-600/20",
);

$dotMap = array(
	"ok" => "bg-emerald-500",
	"warning" => "bg-amber-500",
	"bad" => "bg-red-500",
	"info" => "bg-blue-500",
	"pending" => "bg-amber-500",
	"running" => "bg-blue-500",
	"completed" => "bg-emerald-500",
	"failed" => "bg-red-500",
	"blocked" => "bg-orange-500",
);

$labelMap = array(
	"ok" => "Pass",
	"warning" => "Warning",
	"bad" => "Fail",
	"info" => "Info",
	"pending" => "Pending",
	"running" => "Running",
	"completed" => "Completed",
	"failed" => "Failed",
	"blocked" => "Blocked",
);

$classes = $styleMap[$statusValue] ?? "bg-gray-50 text-gray-700 ring-gray-600/20";
$dotClass = $dotMap[$statusValue] ?? "bg-gray-500";
$label = $labelMap[$statusValue] ?? ucfirst($statusValue);
@endphp

<span {{ $attributes->merge(array("class" => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {$classes}")) }}>
	<span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
	{{ $label }}
</span>
