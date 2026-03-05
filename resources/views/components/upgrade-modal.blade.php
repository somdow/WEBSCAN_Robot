{{-- Reusable upgrade modal — triggered via $dispatch('open-modal', 'upgrade-pro') --}}
<x-modal name="upgrade-pro" maxWidth="md">
	<div class="overflow-hidden">
		{{-- Header --}}
		<div class="bg-gradient-to-br from-accent to-indigo-700 px-6 py-8 text-center">
			<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
				<svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
				</svg>
			</div>
			<h3 class="mt-4 text-lg font-bold text-white">Need More Scans?</h3>
			<p class="mt-1.5 text-sm text-white/80">You've reached your monthly scan limit. Upgrade for more volume and team features.</p>
		</div>

		{{-- Benefits --}}
		<div class="px-6 py-6">
			<ul class="space-y-4">
				<li class="flex items-start gap-3">
					<div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-light/60">
						<svg class="h-3.5 w-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
						</svg>
					</div>
					<div>
						<p class="text-sm font-medium text-text-primary">More scans per month</p>
						<p class="text-xs text-text-secondary">Pro: 100 scans/mo. Agency: 500 scans/mo. Scale as you grow.</p>
					</div>
				</li>
				<li class="flex items-start gap-3">
					<div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-light/60">
						<svg class="h-3.5 w-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
						</svg>
					</div>
					<div>
						<p class="text-sm font-medium text-text-primary">More projects & team seats</p>
						<p class="text-xs text-text-secondary">Manage multiple sites with your team. Up to 25 projects and 10 members.</p>
					</div>
				</li>
				<li class="flex items-start gap-3">
					<div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-light/60">
						<svg class="h-3.5 w-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
						</svg>
					</div>
					<div>
						<p class="text-sm font-medium text-text-primary">Extended scan history</p>
						<p class="text-xs text-text-secondary">Track progress over time. Pro: 90 days. Agency: unlimited history.</p>
					</div>
				</li>
			</ul>

			<div class="mt-6 rounded-lg border border-accent/20 bg-accent-light/40 px-4 py-3 text-center">
				<p class="text-sm font-medium text-text-primary">Pro plan starts at <span class="font-bold text-accent">$49/mo</span></p>
				<p class="text-xs text-text-secondary">100 scans/mo &middot; 5 projects &middot; 5 team members &middot; 90-day history</p>
			</div>
		</div>

		{{-- Actions --}}
		<div class="flex items-center justify-end gap-3 border-t border-border px-6 py-4">
			<button
				@click="$dispatch('close-modal', 'upgrade-pro')"
				class="cursor-pointer rounded-md px-4 py-2 text-sm font-medium text-text-secondary transition hover:text-text-primary"
			>
				Maybe Later
			</button>
			<a
				href="{{ route("pricing") }}"
				class="inline-flex items-center gap-2 rounded-md bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
				</svg>
				View Plans
			</a>
		</div>
	</div>
</x-modal>
