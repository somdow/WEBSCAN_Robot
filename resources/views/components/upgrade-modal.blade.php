{{-- Reusable upgrade modal — triggered via $dispatch('open-modal', 'upgrade-pro') --}}
<x-modal name="upgrade-pro" maxWidth="md">
	<div class="overflow-hidden">
		{{-- Header with AI gradient --}}
		<div class="bg-gradient-to-br from-ai to-orange-700 px-6 py-8 text-center">
			<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
				<svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
				</svg>
			</div>
			<h3 class="mt-4 text-lg font-bold text-white">Unlock AI Optimization</h3>
			<p class="mt-1.5 text-sm text-white/80">Get actionable, AI-powered suggestions for every module in your scan.</p>
		</div>

		{{-- Benefits --}}
		<div class="px-6 py-6">
			<ul class="space-y-4">
				<li class="flex items-start gap-3">
					<div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ai-light">
						<svg class="h-3.5 w-3.5 text-ai" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
						</svg>
					</div>
					<div>
						<p class="text-sm font-medium text-text-primary">Per-module AI suggestions</p>
						<p class="text-xs text-text-secondary">Optimized titles, meta descriptions, headings, and content — written for you.</p>
					</div>
				</li>
				<li class="flex items-start gap-3">
					<div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ai-light">
						<svg class="h-3.5 w-3.5 text-ai" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
						</svg>
					</div>
					<div>
						<p class="text-sm font-medium text-text-primary">Executive summary</p>
						<p class="text-xs text-text-secondary">AI-generated overview of your biggest issues and quick wins with estimated impact.</p>
					</div>
				</li>
				<li class="flex items-start gap-3">
					<div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ai-light">
						<svg class="h-3.5 w-3.5 text-ai" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
						</svg>
					</div>
					<div>
						<p class="text-sm font-medium text-text-primary">Choose your AI provider</p>
						<p class="text-xs text-text-secondary">Connect your own Gemini, OpenAI, or Anthropic API key for full control.</p>
					</div>
				</li>
			</ul>

			<div class="mt-6 rounded-lg border border-accent/20 bg-accent-light/40 px-4 py-3 text-center">
				<p class="text-sm font-medium text-text-primary">Pro plan starts at <span class="font-bold text-accent">$49/mo</span></p>
				<p class="text-xs text-text-secondary">100 scans/mo &middot; 5 projects &middot; Full AI access</p>
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
				Upgrade to Pro
			</a>
		</div>
	</div>
</x-modal>
