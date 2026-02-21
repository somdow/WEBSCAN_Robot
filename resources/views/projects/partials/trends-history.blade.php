{{-- Score trend chart (left) + compact scan history table (right) --}}
{{-- Expects variables: $project, $completedScans, $latestScan --}}

<div class="grid grid-cols-1 gap-6 {{ $completedScans->count() >= 2 ? 'lg:grid-cols-2' : '' }}">
	{{-- Score trend chart --}}
	@if($completedScans->count() >= 2)
		<div>
			<h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-text-tertiary">Score Trend</h4>
			<x-score-trend-chart :scans="$completedScans" />
		</div>
	@elseif($completedScans->count() === 1)
		<div class="flex items-center gap-2 text-sm text-text-secondary">
			<svg class="h-4 w-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
			</svg>
			Run another scan to see your score trend over time.
		</div>
	@endif

	{{-- Compact scan history --}}
	<div>
		<h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-text-tertiary">Scan History</h4>
		@if($project->ownScans->isEmpty())
			<p class="text-sm text-text-tertiary">No scans yet. Click "Run Scan" to get started.</p>
		@else
			<table class="w-full">
				<thead>
					<tr class="border-b border-border text-left text-[10px] font-medium uppercase tracking-wider text-text-tertiary">
						<th class="pb-2 pr-3">Date</th>
						<th class="pb-2 pr-3">Score</th>
						<th class="pb-2">Status</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-border/50">
					@foreach($project->ownScans->take(10) as $historyScan)
						<tr class="transition hover:bg-background {{ $latestScan && $historyScan->id === $latestScan->id ? 'bg-orange-50/50 border-l-2 border-l-orange-400' : '' }}">
							<td class="py-2 pr-3 text-xs text-text-secondary">
								@if($historyScan->status === \App\Enums\ScanStatus::Completed)
									<a href="{{ route("projects.show", array("project" => $project, "scan" => $historyScan)) }}" class="hover:text-accent">
										{{ $historyScan->created_at->diffForHumans() }}
									</a>
								@else
									{{ $historyScan->created_at->diffForHumans() }}
								@endif
							</td>
							<td class="py-2 pr-3">
								@if($historyScan->overall_score !== null)
									<span class="text-xs font-semibold {{ $historyScan->scoreColorClass() }}">{{ $historyScan->overall_score }}</span>
								@else
									<span class="text-xs text-text-tertiary">&mdash;</span>
								@endif
							</td>
							<td class="py-2">
								<x-scan.status-badge :status="$historyScan->status" />
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>
</div>
