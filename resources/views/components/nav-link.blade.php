@props(["active"])

@php
$classes = ($active ?? false)
	? "inline-flex items-center px-1 pt-1 border-b-2 border-accent text-sm font-medium leading-5 text-text-primary focus:outline-none focus:border-accent transition duration-150 ease-in-out"
	: "inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-text-secondary hover:text-text-primary hover:border-border-strong focus:outline-none focus:text-text-primary focus:border-border-strong transition duration-150 ease-in-out";
@endphp

<a {{ $attributes->merge(["class" => $classes]) }}>
	{{ $slot }}
</a>
