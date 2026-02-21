<section>
	<header>
		<div class="flex items-center gap-3">
			<h2 class="text-lg font-medium text-text-primary">
				AI Provider Settings
			</h2>
			@if(!($canAccessAi ?? false))
				<span class="inline-flex items-center gap-1 rounded-full bg-ai/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-ai">
					<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
					</svg>
					Pro
				</span>
			@endif
		</div>

		<p class="mt-1 text-sm text-text-secondary">
			Add your API keys for one or more AI providers. The preferred provider will be used for scan insights.
		</p>
	</header>

	@if($canAccessAi ?? false)
		{{-- Unlocked: full interactive form --}}
		<form method="post" action="{{ route("ai-settings.update") }}" class="mt-6 space-y-6">
			@csrf
			@method("patch")

			{{-- Preferred Provider --}}
			<div>
				<x-input-label for="ai_provider" value="Preferred Provider" />
				<select
					id="ai_provider"
					name="ai_provider"
					class="mt-1 block w-full cursor-pointer rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary shadow-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
				>
					<option value="">System Default ({{ ucfirst(config("ai.default_provider", "gemini")) }})</option>
					@foreach(\App\Enums\AiProvider::cases() as $provider)
						<option value="{{ $provider->value }}" @selected(old("ai_provider", $user->ai_provider) === $provider->value)>
							{{ $provider->label() }}
						</option>
					@endforeach
				</select>
				<x-input-error class="mt-2" :messages="$errors->get('ai_provider')" />
			</div>

			{{-- Gemini API Key --}}
			<div class="rounded-lg border border-border bg-surface-inset p-4">
				<div class="flex items-center gap-2 mb-3">
					<span class="text-sm font-semibold text-text-primary">Google Gemini</span>
					@if($user->ai_gemini_key)
						<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Connected</span>
					@endif
				</div>
				<x-text-input
					id="ai_gemini_key"
					name="ai_gemini_key"
					type="password"
					class="block w-full"
					placeholder="{{ $user->ai_gemini_key ? '••••••••••••••••' : 'Paste your Gemini API key' }}"
					autocomplete="off"
				/>
				<p class="mt-2 text-xs text-text-secondary">
					<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" class="font-medium text-accent hover:text-accent-hover">
						Get your Gemini API key at aistudio.google.com
					</a>
				</p>
				@if($user->ai_gemini_key)
					<label class="mt-2 flex items-center gap-2 text-sm text-text-secondary">
						<input name="clear_gemini_key" type="checkbox" value="1" class="rounded border-border text-accent shadow-sm focus:ring-accent" />
						Remove key
					</label>
				@endif
				<x-input-error class="mt-2" :messages="$errors->get('ai_gemini_key')" />
			</div>

			{{-- OpenAI API Key --}}
			<div class="rounded-lg border border-border bg-surface-inset p-4">
				<div class="flex items-center gap-2 mb-3">
					<span class="text-sm font-semibold text-text-primary">OpenAI</span>
					@if($user->ai_openai_key)
						<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Connected</span>
					@endif
				</div>
				<x-text-input
					id="ai_openai_key"
					name="ai_openai_key"
					type="password"
					class="block w-full"
					placeholder="{{ $user->ai_openai_key ? '••••••••••••••••' : 'Paste your OpenAI API key' }}"
					autocomplete="off"
				/>
				<p class="mt-2 text-xs text-text-secondary">
					<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener" class="font-medium text-accent hover:text-accent-hover">
						Manage OpenAI keys at platform.openai.com
					</a>
				</p>
				@if($user->ai_openai_key)
					<label class="mt-2 flex items-center gap-2 text-sm text-text-secondary">
						<input name="clear_openai_key" type="checkbox" value="1" class="rounded border-border text-accent shadow-sm focus:ring-accent" />
						Remove key
					</label>
				@endif
				<x-input-error class="mt-2" :messages="$errors->get('ai_openai_key')" />
			</div>

			{{-- Anthropic API Key --}}
			<div class="rounded-lg border border-border bg-surface-inset p-4">
				<div class="flex items-center gap-2 mb-3">
					<span class="text-sm font-semibold text-text-primary">Anthropic (Claude)</span>
					@if($user->ai_anthropic_key)
						<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Connected</span>
					@endif
				</div>
				<x-text-input
					id="ai_anthropic_key"
					name="ai_anthropic_key"
					type="password"
					class="block w-full"
					placeholder="{{ $user->ai_anthropic_key ? '••••••••••••••••' : 'Paste your Anthropic API key' }}"
					autocomplete="off"
				/>
				<p class="mt-2 text-xs text-text-secondary">
					<a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener" class="font-medium text-accent hover:text-accent-hover">
						Manage Anthropic keys at console.anthropic.com
					</a>
				</p>
				@if($user->ai_anthropic_key)
					<label class="mt-2 flex items-center gap-2 text-sm text-text-secondary">
						<input name="clear_anthropic_key" type="checkbox" value="1" class="rounded border-border text-accent shadow-sm focus:ring-accent" />
						Remove key
					</label>
				@endif
				<x-input-error class="mt-2" :messages="$errors->get('ai_anthropic_key')" />
			</div>

			<div class="flex items-center gap-4">
				<x-primary-button>Save AI Settings</x-primary-button>

				@if(session("status") === "ai-settings-updated")
					<p
						x-data="{ show: true }"
						x-show="show"
						x-transition
						x-init="setTimeout(() => show = false, 2000)"
						class="text-sm text-text-secondary"
					>Saved.</p>
				@endif
			</div>
		</form>
	@else
		{{-- Locked: visible but disabled with upgrade prompt --}}
		<div class="relative mt-6">
			{{-- Semi-transparent overlay content --}}
			<div class="pointer-events-none select-none opacity-40">
				<div class="space-y-6">
					{{-- Fake provider selector --}}
					<div>
						<span class="mb-1 block text-sm font-medium text-text-primary">Preferred Provider</span>
						<div class="block w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-tertiary">
							System Default ({{ ucfirst(config("ai.default_provider", "gemini")) }})
						</div>
					</div>

					{{-- Fake provider cards --}}
					@foreach(array("Google Gemini", "OpenAI", "Anthropic (Claude)") as $providerName)
						<div class="rounded-lg border border-border bg-surface-inset p-4">
							<span class="text-sm font-semibold text-text-primary">{{ $providerName }}</span>
							<div class="mt-3 block w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-tertiary">
								Paste your API key
							</div>
						</div>
					@endforeach
				</div>
			</div>

			{{-- Centered upgrade prompt --}}
			<div class="absolute inset-0 flex items-center justify-center">
				<div class="rounded-xl border border-ai/20 bg-surface px-8 py-6 text-center shadow-lg">
					<div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-ai-light">
						<svg class="h-5 w-5 text-ai" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
						</svg>
					</div>
					<p class="mt-3 text-sm font-semibold text-text-primary">Available on Pro & Agency</p>
					<p class="mt-1 text-xs text-text-secondary">Upgrade to connect your own AI provider keys.</p>
					<a
						href="{{ route("pricing") }}"
						class="mt-4 inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
						</svg>
						Upgrade to Pro
					</a>
				</div>
			</div>
		</div>
	@endif
</section>
