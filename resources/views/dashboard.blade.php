<x-app-layout>
	<x-slot name="header">
		<div class="flex items-start justify-between">
			<div>
				<h1 x-data="{ greeting: (() => { const h = new Date().getHours(); return h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening'; })() }" class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Good <span x-text="greeting"></span>, {{ Auth::user()->name }}</h1>
				<p class="mt-1 text-sm text-text-secondary">Here's what's happening with your SEO projects.</p>
			</div>
			<x-new-project-button class="mt-6 hidden sm:block" />
		</div>
	</x-slot>

	{{-- Onboarding wizard for first-time users --}}
	@if(!$gettingStarted["hasProject"])
		<x-onboarding-wizard />
	@endif

	{{-- Metric cards --}}
	<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
		<div class="rounded-lg border border-border bg-surface p-5 shadow-card">
			<div class="text-xs font-medium uppercase tracking-wider text-text-tertiary">Projects</div>
			<div class="mt-2 text-2xl font-bold tracking-tight text-text-primary">{{ $projectCount }}</div>
			<div class="mt-1 text-xs text-text-tertiary">of {{ $plan->max_projects ?? 1 }} allowed</div>
		</div>
		<div class="rounded-lg border border-border bg-surface p-5 shadow-card">
			<div class="text-xs font-medium uppercase tracking-wider text-text-tertiary">Scans This Month</div>
			<div class="mt-2 text-2xl font-bold tracking-tight text-text-primary">{{ $scansThisMonth }}</div>
			<div class="mt-1 text-xs text-text-tertiary">of {{ $maxScans }} allowed</div>
		</div>
		<div class="rounded-lg border border-border bg-surface p-5 shadow-card">
			<div class="text-xs font-medium uppercase tracking-wider text-text-tertiary">Avg. Website Health</div>
			@if($averageScore !== null)
				<div class="mt-2 text-2xl font-bold tracking-tight text-text-primary">{{ $averageScore }}</div>
				<div class="mt-1 text-xs text-text-tertiary">across all scans</div>
			@else
				<div class="mt-2 text-2xl font-bold tracking-tight text-text-tertiary">&mdash;</div>
				<div class="mt-1 text-xs text-text-tertiary">No scans yet</div>
			@endif
		</div>
		<div class="rounded-lg border border-border bg-surface p-5 shadow-card">
			<div class="text-xs font-medium uppercase tracking-wider text-text-tertiary">Current Plan</div>
			<div class="mt-2 text-2xl font-bold tracking-tight text-text-primary">{{ $plan->name ?? "Free" }}</div>
			<div class="mt-1">
				@if($plan && $plan->slug !== "free")
					<span class="text-xs font-medium text-emerald-600">Active</span>
				@else
					<a href="{{ route("pricing") }}" class="text-xs font-medium text-accent hover:text-accent-hover">Upgrade</a>
				@endif
			</div>
		</div>
	</div>

	{{-- Recent scans --}}
	<div class="mt-8 rounded-lg border border-border bg-surface shadow-card">
		<div class="border-b border-border px-6 py-4">
			<div class="flex items-center justify-between">
				<h2 class="text-sm font-semibold text-text-primary">Recent Scans</h2>
				@if($recentScans->isNotEmpty())
					<a href="{{ route("scans.index") }}" class="text-xs font-medium text-accent hover:text-accent-hover">View all &rarr;</a>
				@endif
			</div>
		</div>

		@if($recentScans->isNotEmpty())
			<table class="w-full">
				<thead>
					<tr class="border-b border-border text-left text-xs font-medium uppercase tracking-wider text-text-tertiary">
						<th class="px-6 py-3">Project</th>
						<th class="px-6 py-3">Score</th>
						<th class="hidden px-6 py-3 sm:table-cell">Date</th>
						<th class="px-6 py-3"></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-border">
					@foreach($recentScans as $scan)
						<tr class="cursor-pointer transition hover:bg-background" onclick="window.location='{{ route("projects.show", array("project" => $scan->project, "scan" => $scan)) }}'">
							<td class="px-6 py-3 text-sm font-medium text-text-primary">{{ $scan->project->name }}</td>
							<td class="px-6 py-3">
								@if($scan->overall_score !== null)
									<span class="text-sm font-semibold {{ $scan->scoreColorClass() }}">{{ $scan->overall_score }}</span>
								@else
									<span class="text-sm text-text-tertiary">&mdash;</span>
								@endif
							</td>
							<td class="hidden px-6 py-3 text-sm text-text-secondary sm:table-cell">{{ $scan->created_at->diffForHumans() }}</td>
							<td class="px-6 py-3 text-right">
								<svg class="inline-block h-4 w-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
								</svg>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@else
			<div class="px-6 py-16 text-center">
				<svg class="mx-auto h-10 w-10 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
				</svg>
				<h3 class="mt-4 text-sm font-medium text-text-primary">No scans yet</h3>
				<p class="mt-1 text-sm text-text-secondary">Create a project and run your first SEO scan to see results here.</p>
				<x-primary-button :href="route('projects.create')" class="mt-6">
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
					</svg>
					Create Your First Project
				</x-primary-button>
			</div>
		@endif
	</div>

	{{-- Getting started cards — hidden once user has projects and scans --}}
	@if(!$gettingStarted["hasProject"] || !$gettingStarted["hasCompletedScan"])
	<div class="mt-8">
		<h2 class="mb-4 text-sm font-semibold text-text-primary">Getting Started</h2>
		<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
			<div class="relative rounded-lg border {{ $gettingStarted["hasProject"] ? "border-emerald-200 bg-emerald-50/50" : "border-accent/30 bg-accent-light" }} p-5 shadow-card">
				@if($gettingStarted["hasProject"])
					<div class="absolute right-4 top-4">
						<svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
						</svg>
					</div>
				@endif
				<div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $gettingStarted["hasProject"] ? "bg-emerald-100" : "bg-accent-light" }}">
					<svg class="h-5 w-5 {{ $gettingStarted["hasProject"] ? "text-emerald-500" : "text-accent" }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
					</svg>
				</div>
				<h3 class="mt-3 text-sm font-medium text-text-primary">1. Add a Project</h3>
				<p class="mt-1 text-xs text-text-secondary">Enter your website URL to start tracking its SEO performance.</p>
				@if(!$gettingStarted["hasProject"])
					<a href="{{ route("projects.create") }}" class="mt-3 inline-flex text-xs font-semibold text-accent hover:text-accent-hover">Get started &rarr;</a>
				@endif
			</div>

			<div class="relative rounded-lg border {{ $gettingStarted["hasCompletedScan"] ? "border-emerald-200 bg-emerald-50/50" : ($gettingStarted["hasProject"] ? "border-accent/30 bg-accent-light" : "border-border bg-surface") }} p-5 shadow-card">
				@if($gettingStarted["hasCompletedScan"])
					<div class="absolute right-4 top-4">
						<svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
						</svg>
					</div>
				@endif
				<div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $gettingStarted["hasCompletedScan"] ? "bg-emerald-100" : "bg-emerald-50" }}">
					<svg class="h-5 w-5 {{ $gettingStarted["hasCompletedScan"] ? "text-emerald-500" : "text-emerald-400" }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
					</svg>
				</div>
				<h3 class="mt-3 text-sm font-medium text-text-primary">2. Run a Scan</h3>
				<p class="mt-1 text-xs text-text-secondary">Analyze {{ $analyzerCount }} SEO factors including E-E-A-T signals and technical health.</p>
				@if($gettingStarted["hasProject"] && !$gettingStarted["hasCompletedScan"])
					<a href="{{ route("projects.index") }}" class="mt-3 inline-flex text-xs font-semibold text-accent hover:text-accent-hover">Run your first scan &rarr;</a>
				@endif
			</div>

			<div class="rounded-lg border border-border bg-surface p-5 shadow-card">
				<div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50">
					<svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
					</svg>
				</div>
				<h3 class="mt-3 text-sm font-medium text-text-primary">3. Download Report</h3>
				<p class="mt-1 text-xs text-text-secondary">Get a PDF report with actionable SEO recommendations.</p>
			</div>
		</div>
	</div>
	@endif
</x-app-layout>
