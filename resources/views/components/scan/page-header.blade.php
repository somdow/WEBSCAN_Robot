{{-- Shared left-side header for project/scan/page views --}}
@props(["projectName", "subtitle", "url", "urlDisplay" => null, "keywords" => array(), "breadcrumbItems" => array()])

<div class="min-w-0 flex-1">
	<h1 class="font-bold tracking-tight text-text-primary break-words" style="font-size: 2.5rem"><a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $projectName }}</a> <span class="font-normal">{{ $subtitle }}</span></h1>
	<x-breadcrumb :items="$breadcrumbItems" />
	@if(!empty($keywords))
		<div class="mt-3 flex items-start gap-2.5">
			<span class="mt-0.5 text-xs font-medium uppercase tracking-wider text-gray-400">Keywords</span>
			<div class="flex flex-wrap gap-1.5">
				@foreach($keywords as $keyword)
					<span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700">{{ $keyword }}</span>
				@endforeach
			</div>
		</div>
	@endif
</div>
