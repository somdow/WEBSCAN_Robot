<script>
	document.addEventListener("alpine:init", function () {
		Alpine.data("aiKeyTester", function (provider, inputId) {
			return {
				testing: false,
				resultValid: null,
				resultMessage: "",

				runTest() {
					const inputElement = document.getElementById(inputId);
					const apiKey = inputElement ? inputElement.value.trim() : "";

					if (!apiKey) {
						this.resultValid = false;
						this.resultMessage = "Please enter an API key first.";
						return;
					}

					this.testing = true;
					this.resultValid = null;
					this.resultMessage = "";

					fetch("{{ route("ai-settings.test-key") }}", {
						method: "POST",
						headers: {
							"Content-Type": "application/json",
							"Accept": "application/json",
							"X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").getAttribute("content"),
						},
						body: JSON.stringify({ provider: provider, api_key: apiKey }),
					})
					.then(function (response) { return response.json(); })
					.then(function (responseData) {
						this.resultValid = responseData.valid;
						this.resultMessage = responseData.message;
						this.testing = false;
					}.bind(this))
					.catch(function () {
						this.resultValid = false;
						this.resultMessage = "Network error — could not reach server.";
						this.testing = false;
					}.bind(this));
				},
			};
		});
	});
</script>

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
		<div class="rounded-lg border border-border bg-surface-inset p-4" x-data="aiKeyTester('gemini', 'ai_gemini_key')">
			<div class="flex items-center gap-2 mb-3">
				<span class="text-sm font-semibold text-text-primary">Google Gemini</span>
				<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Recommended</span>
				@if($user->ai_gemini_key)
					<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Connected</span>
				@endif
			</div>
			<div class="flex items-center gap-2">
				<x-text-input
					id="ai_gemini_key"
					name="ai_gemini_key"
					type="password"
					class="block w-full"
					placeholder="{{ $user->ai_gemini_key ? '••••••••••••••••' : 'Paste your Gemini API key' }}"
					autocomplete="off"
				/>
				<button
					type="button"
					class="inline-flex shrink-0 items-center gap-1 rounded-md border border-border bg-surface px-3 py-2 text-xs font-medium text-text-secondary shadow-sm transition hover:bg-surface-inset focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
					:disabled="testing"
					@click="runTest()"
				>
					<svg x-show="!testing" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
					<svg x-show="testing" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
					<span x-text="testing ? 'Testing...' : 'Test'"></span>
				</button>
			</div>
			<div x-show="resultMessage" x-transition class="mt-2 flex items-center gap-1.5 text-xs font-medium" :class="resultValid ? 'text-emerald-600' : 'text-red-600'">
				<svg x-show="resultValid" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
				<svg x-show="resultValid === false && resultMessage" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
				<span x-text="resultMessage"></span>
			</div>
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
		<div class="rounded-lg border border-border bg-surface-inset p-4" x-data="aiKeyTester('openai', 'ai_openai_key')">
			<div class="flex items-center gap-2 mb-3">
				<span class="text-sm font-semibold text-text-primary">OpenAI</span>
				@if($user->ai_openai_key)
					<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Connected</span>
				@endif
			</div>
			<div class="flex items-center gap-2">
				<x-text-input
					id="ai_openai_key"
					name="ai_openai_key"
					type="password"
					class="block w-full"
					placeholder="{{ $user->ai_openai_key ? '••••••••••••••••' : 'Paste your OpenAI API key' }}"
					autocomplete="off"
				/>
				<button
					type="button"
					class="inline-flex shrink-0 items-center gap-1 rounded-md border border-border bg-surface px-3 py-2 text-xs font-medium text-text-secondary shadow-sm transition hover:bg-surface-inset focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
					:disabled="testing"
					@click="runTest()"
				>
					<svg x-show="!testing" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
					<svg x-show="testing" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
					<span x-text="testing ? 'Testing...' : 'Test'"></span>
				</button>
			</div>
			<div x-show="resultMessage" x-transition class="mt-2 flex items-center gap-1.5 text-xs font-medium" :class="resultValid ? 'text-emerald-600' : 'text-red-600'">
				<svg x-show="resultValid" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
				<svg x-show="resultValid === false && resultMessage" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
				<span x-text="resultMessage"></span>
			</div>
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
		<div class="rounded-lg border border-border bg-surface-inset p-4" x-data="aiKeyTester('anthropic', 'ai_anthropic_key')">
			<div class="flex items-center gap-2 mb-3">
				<span class="text-sm font-semibold text-text-primary">Anthropic (Claude)</span>
				@if($user->ai_anthropic_key)
					<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Connected</span>
				@endif
			</div>
			<div class="flex items-center gap-2">
				<x-text-input
					id="ai_anthropic_key"
					name="ai_anthropic_key"
					type="password"
					class="block w-full"
					placeholder="{{ $user->ai_anthropic_key ? '••••••••••••••••' : 'Paste your Anthropic API key' }}"
					autocomplete="off"
				/>
				<button
					type="button"
					class="inline-flex shrink-0 items-center gap-1 rounded-md border border-border bg-surface px-3 py-2 text-xs font-medium text-text-secondary shadow-sm transition hover:bg-surface-inset focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
					:disabled="testing"
					@click="runTest()"
				>
					<svg x-show="!testing" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
					<svg x-show="testing" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
					<span x-text="testing ? 'Testing...' : 'Test'"></span>
				</button>
			</div>
			<div x-show="resultMessage" x-transition class="mt-2 flex items-center gap-1.5 text-xs font-medium" :class="resultValid ? 'text-emerald-600' : 'text-red-600'">
				<svg x-show="resultValid" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
				<svg x-show="resultValid === false && resultMessage" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
				<span x-text="resultMessage"></span>
			</div>
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
