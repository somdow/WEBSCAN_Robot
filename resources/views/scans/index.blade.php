<x-app-layout>
	<x-slot name="header">
		<div>
			<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Scans</h1>
			<p class="mt-1 text-sm text-text-secondary">All scan results across your projects</p>
			<x-breadcrumb :items="array(
				array('label' => 'Home', 'url' => route('dashboard')),
				array('label' => 'Scans'),
			)" />
		</div>
	</x-slot>

	<div class="rounded-lg border border-border bg-surface shadow-card">
		@if($scans->isEmpty())
			<div class="px-6 py-16 text-center">
				<svg class="mx-auto h-12 w-12 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
				</svg>
				<h3 class="mt-3 text-sm font-semibold text-text-primary">No scans yet</h3>
				<p class="mt-1 text-sm text-text-secondary">Create a project and run your first scan to see results here.</p>
				<div class="mt-4">
					<a href="{{ route("projects.create") }}" class="inline-flex items-center gap-1.5 rounded-md bg-accent px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover">
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
						</svg>
						Create Project
					</a>
				</div>
			</div>
		@else
			<table class="w-full">
				<thead>
					<tr class="border-b border-border text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">
						<th class="px-6 py-3">Project</th>
						<th class="hidden px-6 py-3 sm:table-cell">URL</th>
						<th class="px-6 py-3">Status</th>
						<th class="px-6 py-3">Score</th>
						<th class="hidden px-6 py-3 sm:table-cell">Pages</th>
						<th class="hidden px-6 py-3 sm:table-cell">Duration</th>
						<th class="hidden px-6 py-3 md:table-cell">Triggered By</th>
						<th class="px-6 py-3">Date</th>
						<th class="px-6 py-3"></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-border">
					@foreach($scans as $scan)
						<tr class="cursor-pointer transition hover:bg-background" onclick="window.location='{{ route("projects.show", array("project" => $scan->project, "scan" => $scan)) }}'">
							<td class="px-6 py-3 text-sm font-medium text-text-primary">
								{{ $scan->project->name }}
							</td>
							<td class="hidden px-6 py-3 text-sm text-text-secondary sm:table-cell">
								{{ $scan->project->domain() }}
							</td>
							<td class="px-6 py-3">
								<x-scan.status-badge :status="$scan->status" />
							</td>
							<td class="px-6 py-3">
								@if($scan->overall_score !== null)
									<span class="text-sm font-semibold {{ $scan->scoreColorClass() }}">
										{{ $scan->overall_score }}
									</span>
								@else
									<span class="text-sm text-text-tertiary">&mdash;</span>
								@endif
							</td>
							<td class="hidden px-6 py-3 sm:table-cell">
								@if($scan->pages_crawled !== null)
									<span class="text-sm text-text-secondary">{{ $scan->pages_crawled }}</span>
								@else
									<span class="text-sm text-text-tertiary">&mdash;</span>
								@endif
							</td>
							<td class="hidden px-6 py-3 text-sm text-text-secondary sm:table-cell">
								{{ $scan->formattedDuration() }}
							</td>
							<td class="hidden px-6 py-3 text-sm text-text-secondary md:table-cell">
								{{ $scan->triggeredBy?->name ?? "System" }}
							</td>
							<td class="px-6 py-3 text-sm text-text-secondary">
								{{ $scan->created_at->format("M j, Y") }}
							</td>
							<td class="px-6 py-3 text-right">
								<a href="{{ route("projects.show", array("project" => $scan->project, "scan" => $scan)) }}" class="text-sm text-accent hover:text-accent-hover">
									<svg class="inline-block h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
									</svg>
								</a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>

			@if($scans->hasPages())
				<div class="border-t border-border px-6 py-4">
					{{ $scans->links() }}
				</div>
			@endif
		@endif
	</div>
</x-app-layout>
