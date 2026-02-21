@props(["href" => null])

@php
	$classes = "inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-sm font-medium text-white cursor-pointer transition hover:bg-accent-hover focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2";
@endphp

@if($href)
	<a href="{{ $href }}" {{ $attributes->merge(array("class" => $classes)) }}>
		{{ $slot }}
	</a>
@else
	<button {{ $attributes->merge(array("type" => "submit", "class" => $classes)) }}>
		{{ $slot }}
	</button>
@endif
