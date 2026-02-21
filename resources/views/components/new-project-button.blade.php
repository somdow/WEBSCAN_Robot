@php
$user = Auth::user();
$organization = $user?->currentOrganization();
$plan = $organization?->plan;
$projectsUsed = $organization?->projects()->count() ?? 0;
$projectsMax = $plan?->max_projects ?? 1;
@endphp

<div {{ $attributes->merge(array("class" => "text-right")) }}>
	<x-primary-button :href="route('projects.create')">
		<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
		</svg>
		New Project
	</x-primary-button>
	<p class="mt-1.5 text-xs text-text-tertiary">
		{{ $projectsUsed }} of {{ $projectsMax }} projects used
	</p>
</div>
