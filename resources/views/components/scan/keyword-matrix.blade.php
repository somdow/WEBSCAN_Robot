{{-- Keyword Consistency Matrix: visual grid showing keyword presence across page locations --}}
@props(["matrix"])

@if(!empty($matrix))
<div class="mt-4 overflow-x-auto rounded-lg border border-gray-100">
	<table class="w-full text-xs">
		<thead>
			<tr class="bg-gray-50 text-left text-text-secondary">
				<th class="px-3 py-2 font-medium">Keyword</th>
				<th class="px-2 py-2 text-center font-medium">Title</th>
				<th class="px-2 py-2 text-center font-medium">Meta</th>
				<th class="px-2 py-2 text-center font-medium">H1</th>
				<th class="px-2 py-2 text-center font-medium">H2+</th>
				<th class="px-2 py-2 text-center font-medium">Body</th>
				<th class="px-2 py-2 text-center font-medium">URL</th>
				<th class="px-2 py-2 text-center font-medium">Alt</th>
			</tr>
		</thead>
		<tbody>
			@foreach($matrix as $rowIndex => $row)
				<tr class="{{ $rowIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50/50' }}">
					<td class="px-3 py-1.5 font-medium text-text-primary" title="{{ $row['keyword'] }}">
						<span class="inline-block max-w-[140px] truncate">{{ $row["keyword"] }}</span>
					</td>
					@foreach(array("title", "metaDescription", "h1", "h2Plus", "body", "url", "imageAlt") as $location)
						<td class="px-2 py-1.5 text-center">
							@if($row["locations"][$location] ?? false)
								<svg class="mx-auto h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
							@else
								<svg class="mx-auto h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
							@endif
						</td>
					@endforeach
				</tr>
			@endforeach
		</tbody>
	</table>
</div>
@endif
