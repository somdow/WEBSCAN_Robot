{{-- Prompt paid users to add their AI API key — triggered via $dispatch('open-modal', 'configure-api-key') --}}
<x-modal name="configure-api-key" maxWidth="md">
	<div class="overflow-hidden">
		{{-- Header --}}
		<div class="bg-gradient-to-br from-amber-500 to-orange-600 px-6 py-8 text-center">
			<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
				<svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
				</svg>
			</div>
			<h3 class="mt-4 text-lg font-bold text-white">Add Your API Key</h3>
			<p class="mt-1.5 text-sm text-white/80">Connect an AI provider to start generating optimization suggestions.</p>
		</div>

		{{-- Body --}}
		<div class="px-6 py-6">
			<p class="text-sm text-text-secondary">
				Your plan includes AI optimization, but you need to connect at least one AI provider to get started.
			</p>

			<div class="mt-4 space-y-3">
				<div class="flex items-center gap-3 rounded-lg border border-border bg-surface-inset px-4 py-3">
					<span class="text-sm font-medium text-text-primary">Google Gemini</span>
					<span class="ml-auto text-xs text-text-tertiary">Free tier available</span>
				</div>
				<div class="flex items-center gap-3 rounded-lg border border-border bg-surface-inset px-4 py-3">
					<span class="text-sm font-medium text-text-primary">OpenAI</span>
					<span class="ml-auto text-xs text-text-tertiary">GPT-4o</span>
				</div>
				<div class="flex items-center gap-3 rounded-lg border border-border bg-surface-inset px-4 py-3">
					<span class="text-sm font-medium text-text-primary">Anthropic</span>
					<span class="ml-auto text-xs text-text-tertiary">Claude</span>
				</div>
			</div>
		</div>

		{{-- Actions --}}
		<div class="flex items-center justify-end gap-3 border-t border-border px-6 py-4">
			<button
				@click="$dispatch('close-modal', 'configure-api-key')"
				class="cursor-pointer rounded-md px-4 py-2 text-sm font-medium text-text-secondary transition hover:text-text-primary"
			>
				Later
			</button>
			<a
				href="{{ route('profile.edit') }}#ai-settings"
				class="inline-flex items-center gap-2 rounded-md bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a7 7 0 0 1 0 .255c-.007.378.138.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a7 7 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a7 7 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a7 7 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
				</svg>
				Go to AI Settings
			</a>
		</div>
	</div>
</x-modal>
