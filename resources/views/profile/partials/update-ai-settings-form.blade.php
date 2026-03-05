<section>
	<header>
		<h2 class="text-lg font-medium text-text-primary">
			AI Provider Settings
		</h2>

		<p class="mt-1 text-sm text-text-secondary">
			Add your API keys for one or more AI providers. Your key powers AI recommendations across all scan modules. We recommend starting with <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" class="font-medium text-accent hover:text-accent-hover">Google Gemini</a> (generous free tier).
		</p>
	</header>

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
				<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Recommended</span>
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
					Get your free Gemini API key at aistudio.google.com
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
</section>
