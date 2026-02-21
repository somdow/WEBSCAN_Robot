{{-- Technology Stack Display: categorized pills showing detected technologies --}}
@props(["techStack"])

@if(!empty($techStack))
<div class="space-y-3">
	@foreach($techStack as $category => $technologies)
		<div>
			<div class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-text-tertiary">{{ $category }}</div>
			<div class="flex flex-wrap gap-1.5">
				@foreach($technologies as $tech)
					<span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-text-primary shadow-sm">
						{{ $tech["name"] }}
						@if(!empty($tech["version"]))
							<span class="text-text-tertiary">{{ $tech["version"] }}</span>
						@endif
					</span>
				@endforeach
			</div>
		</div>
	@endforeach
</div>
@endif
