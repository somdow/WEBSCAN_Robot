{{-- Visual heading hierarchy for H2-H6 Tags module --}}
{{-- Expects: $headingsList (array of {tag, text, hidden, imageOnly?} objects) --}}

@props(["headingsList"])

@if(!empty($headingsList))
<div class="border-t border-border/40 px-5 py-4">
	<div class="mb-3 flex items-center gap-2">
		<svg class="h-3.5 w-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
		</svg>
		<span class="text-[11px] font-bold uppercase tracking-wider text-text-primary">Heading Structure</span>
		<span class="rounded-full bg-border/60 px-2 py-0.5 text-[10px] font-semibold text-text-tertiary">{{ count($headingsList) }}</span>
	</div>

	<div class="space-y-1">
		@php
			$tagColors = array(
				"h2" => "bg-orange-100 text-orange-700",
				"h3" => "bg-sky-100 text-sky-700",
				"h4" => "bg-teal-100 text-teal-700",
				"h5" => "bg-slate-100 text-slate-600",
				"h6" => "bg-gray-100 text-gray-600",
			);
			$indentMap = array("h2" => "pl-0", "h3" => "pl-5", "h4" => "pl-10", "h5" => "pl-14", "h6" => "pl-18");
		@endphp

		@foreach($headingsList as $heading)
			@php
				$tag = strtolower($heading["tag"] ?? "h2");
				$text = trim($heading["text"] ?? "");
				$isHidden = $heading["hidden"] ?? false;
				$isImageOnly = $heading["imageOnly"] ?? false;
				$isEmpty = $text === "" && !$isImageOnly;
				$pillClass = $tagColors[$tag] ?? $tagColors["h6"];
				$indent = $indentMap[$tag] ?? "pl-0";
			@endphp
			<div class="flex items-center gap-2.5 rounded-md px-3 py-1.5 transition hover:bg-gray-100/60 {{ $indent }} {{ $isHidden ? 'opacity-50' : '' }}">
				<span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $pillClass }}">{{ strtoupper($tag) }}</span>

				@if($isEmpty)
					<span class="truncate text-sm italic text-red-400">(empty heading)</span>
				@elseif($isImageOnly)
					<span class="inline-flex items-center gap-1.5 truncate text-sm text-text-secondary">
						<svg class="h-3.5 w-3.5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
						</svg>
						@if($text !== "")
							<span class="truncate">{{ $text }}</span>
						@else
							<span class="italic text-red-400">(no alt text)</span>
						@endif
					</span>
					<span class="shrink-0 rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-amber-600">Image</span>
				@else
					<span class="truncate text-sm text-text-secondary">{{ $text }}</span>
				@endif

				@if($isHidden)
					<span class="shrink-0 rounded bg-gray-200 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-gray-500">Hidden</span>
				@endif
			</div>
		@endforeach
	</div>
</div>
@endif
