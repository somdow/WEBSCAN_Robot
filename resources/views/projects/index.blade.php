<x-app-layout>
	<x-slot name="header">
		<div class="flex items-start justify-between">
			<div>
				<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Projects</h1>
				<p class="mt-1 text-sm text-text-secondary">Manage your SEO monitoring projects.</p>
				<x-breadcrumb :items="array(
					array('label' => 'Home', 'url' => route('dashboard')),
					array('label' => 'Projects'),
				)" />
			</div>
			<x-new-project-button class="mt-6" />
		</div>
	</x-slot>

	@if($projects->isEmpty())
		<div class="rounded-lg border border-border bg-surface px-6 py-16 text-center shadow-card">
			<svg class="mx-auto h-10 w-10 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
			</svg>
			<h3 class="mt-4 text-sm font-medium text-text-primary">No projects yet</h3>
			<p class="mt-1 text-sm text-text-secondary">Create your first project to start monitoring SEO performance.</p>
			<x-primary-button :href="route('projects.create')" class="mt-6">
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
				</svg>
				Create Your First Project
			</x-primary-button>
		</div>
	@else
		<div class="rounded-lg border border-border bg-surface shadow-card">
			<table class="w-full">
				<thead>
					<tr class="border-b border-border text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">
						<th class="px-6 py-3">Project</th>
						<th class="hidden px-6 py-3 sm:table-cell">URL</th>
						<th class="hidden px-6 py-3 sm:table-cell">Last Scan</th>
						<th class="hidden px-6 py-3 sm:table-cell">Pages</th>
						<th class="px-6 py-3">Health</th>
						<th class="px-6 py-3"></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-border">
					@foreach($projects as $project)
						<tr class="cursor-pointer transition hover:bg-gray-300" onclick="window.location='{{ route("projects.show", $project) }}'">
							<td class="px-6 py-4">
								<span class="text-sm font-medium text-text-primary">{{ $project->name }}</span>
							</td>
							<td class="hidden px-6 py-4 text-sm text-text-secondary sm:table-cell">
								{{ $project->domain() }}
							</td>
							<td class="hidden px-6 py-4 sm:table-cell">
								@if($project->latestScan)
									<span class="text-sm text-text-secondary">{{ $project->latestScan->created_at->diffForHumans() }}</span>
								@else
									<span class="text-xs text-text-tertiary">Never</span>
								@endif
							</td>
							<td class="hidden px-6 py-4 sm:table-cell">
								@if($project->latestScan && $project->latestScan->pages_crawled !== null)
									<span class="text-sm text-text-secondary">{{ $project->latestScan->pages_crawled }}</span>
								@else
									<span class="text-sm text-text-tertiary">&mdash;</span>
								@endif
							</td>
							<td class="px-6 py-4">
								@if($project->latestScan && $project->latestScan->overall_score !== null)
									<span class="text-sm font-semibold {{ $project->latestScan->scoreColorClass() }}">
										{{ $project->latestScan->overall_score }}
									</span>
								@else
									<span class="text-sm text-text-tertiary">&mdash;</span>
								@endif
							</td>
							<td class="px-6 py-4 text-right">
								<span class="text-xs font-medium text-accent">View &rarr;</span>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		@if($projects->hasPages())
			<div class="mt-4 flex justify-center">
				{{ $projects->links() }}
			</div>
		@endif
	@endif
</x-app-layout>
