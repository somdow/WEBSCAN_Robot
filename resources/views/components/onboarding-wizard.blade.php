<div
	x-data="{
		open: !localStorage.getItem('onboardingDismissed'),
		step: 1,
		totalSteps: 4,
		loading: false,
		form: {
			name: '',
			url: '',
			target_keywords: '',
			trigger_scan: true,
		},
		errors: {},
		dismiss() {
			localStorage.setItem('onboardingDismissed', '1');
			this.open = false;
		},
		nextStep() {
			if (this.step === 2) {
				this.errors = {};
				if (!this.form.name.trim()) this.errors.name = 'Project name is required.';
				if (!this.form.url.trim()) this.errors.url = 'Website URL is required.';
				if (Object.keys(this.errors).length) return;
			}
			if (this.step < this.totalSteps) this.step++;
		},
		prevStep() {
			if (this.step > 1) this.step--;
		},
		async submit() {
			this.loading = true;
			this.errors = {};

			try {
				const response = await fetch('{{ route("onboarding.store") }}', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
						'Accept': 'application/json',
					},
					body: JSON.stringify(this.form),
				});

				const data = await response.json();

				if (!response.ok) {
					if (data.errors) {
						this.errors = {};
						Object.keys(data.errors).forEach(key => {
							this.errors[key] = data.errors[key][0];
						});
						this.step = 2;
					}
					return;
				}

				if (data.success && data.redirect) {
					localStorage.setItem('onboardingDismissed', '1');
					window.location.href = data.redirect;
				}
			} catch (err) {
				this.errors.general = 'Something went wrong. Please try again.';
			} finally {
				this.loading = false;
			}
		}
	}"
	x-show="open"
	x-transition:enter="transition ease-out duration-200"
	x-transition:enter-start="opacity-0 scale-95"
	x-transition:enter-end="opacity-100 scale-100"
	x-transition:leave="transition ease-in duration-150"
	x-transition:leave-start="opacity-100 scale-100"
	x-transition:leave-end="opacity-0 scale-95"
	class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
	style="display: none;"
>
	<div @click.outside="dismiss()" class="w-full max-w-lg rounded-xl border border-border bg-surface shadow-xl">
		{{-- Header with progress --}}
		<div class="flex items-center justify-between border-b border-border px-6 py-4">
			<h2 class="text-base font-semibold text-text-primary">Welcome to {{ $siteName }}</h2>
			<button @click="dismiss()" class="text-text-tertiary transition hover:text-text-primary">
				<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
				</svg>
			</button>
		</div>

		{{-- Progress dots --}}
		<div class="flex justify-center gap-2 px-6 pt-5">
			<template x-for="i in totalSteps" :key="i">
				<div
					class="h-2 w-2 rounded-full transition-colors"
					:class="i <= step ? 'bg-accent' : 'bg-gray-200'"
				></div>
			</template>
		</div>

		{{-- Step content --}}
		<div class="px-6 py-6">

			{{-- Step 1: Welcome --}}
			<div x-show="step === 1">
				<div class="text-center">
					<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-accent-light">
						<svg class="h-7 w-7 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
						</svg>
					</div>
					<h3 class="mt-4 text-lg font-semibold text-text-primary">Let's set up your first project</h3>
					<p class="mt-2 text-sm text-text-secondary">We'll analyze {{ $analyzerCount }} SEO factors across your website including technical health, content quality, E-E-A-T signals, and more.</p>
					<p class="mt-3 text-sm text-text-secondary">It only takes a minute to get started.</p>
				</div>
			</div>

			{{-- Step 2: Project details --}}
			<div x-show="step === 2">
				<h3 class="text-base font-semibold text-text-primary">Create your project</h3>
				<p class="mt-1 text-sm text-text-secondary">Enter your website details below.</p>

				<div class="mt-5 space-y-4">
					<div>
						<label for="onboard-name" class="block text-sm font-medium text-text-primary">Project Name</label>
						<input
							id="onboard-name"
							type="text"
							x-model="form.name"
							placeholder="My Website"
							class="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-text-primary shadow-sm transition placeholder:text-text-tertiary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
						>
						<p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-500"></p>
					</div>

					<div>
						<label for="onboard-url" class="block text-sm font-medium text-text-primary">Website URL</label>
						<input
							id="onboard-url"
							type="url"
							x-model="form.url"
							placeholder="https://example.com"
							class="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-text-primary shadow-sm transition placeholder:text-text-tertiary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
						>
						<p x-show="errors.url" x-text="errors.url" class="mt-1 text-xs text-red-500"></p>
					</div>
				</div>
			</div>

			{{-- Step 3: Keywords (optional) --}}
			<div x-show="step === 3">
				<h3 class="text-base font-semibold text-text-primary">Target keywords <span class="font-normal text-text-tertiary">(optional)</span></h3>
				<p class="mt-1 text-sm text-text-secondary">Add keywords you want to rank for. We'll check if your content is optimized for them.</p>

				<div class="mt-5">
					<label for="onboard-keywords" class="block text-sm font-medium text-text-primary">Keywords</label>
					<input
						id="onboard-keywords"
						type="text"
						x-model="form.target_keywords"
						placeholder="seo tool, website analyzer, seo audit"
						class="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-text-primary shadow-sm transition placeholder:text-text-tertiary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
					>
					<p class="mt-1.5 text-xs text-text-tertiary">Separate multiple keywords with commas. You can add more later.</p>
				</div>
			</div>

			{{-- Step 4: Confirm --}}
			<div x-show="step === 4">
				<h3 class="text-base font-semibold text-text-primary">Ready to go!</h3>
				<p class="mt-1 text-sm text-text-secondary">Review your project details and launch your first scan.</p>

				<div class="mt-5 rounded-lg border border-border bg-background p-4">
					<dl class="space-y-3 text-sm">
						<div class="flex justify-between">
							<dt class="font-medium text-text-secondary">Project</dt>
							<dd class="text-text-primary" x-text="form.name"></dd>
						</div>
						<div class="flex justify-between">
							<dt class="font-medium text-text-secondary">URL</dt>
							<dd class="max-w-[240px] truncate text-text-primary" x-text="form.url"></dd>
						</div>
						<div x-show="form.target_keywords" class="flex justify-between">
							<dt class="font-medium text-text-secondary">Keywords</dt>
							<dd class="max-w-[240px] truncate text-text-primary" x-text="form.target_keywords"></dd>
						</div>
					</dl>
				</div>

				<label class="mt-4 flex cursor-pointer items-center gap-2">
					<input type="checkbox" x-model="form.trigger_scan" class="rounded border-border text-accent shadow-sm focus:ring-accent">
					<span class="text-sm text-text-primary">Run SEO scan immediately</span>
				</label>

				<p x-show="errors.general" x-text="errors.general" class="mt-3 text-xs text-red-500"></p>
			</div>
		</div>

		{{-- Footer with navigation --}}
		<div class="flex items-center justify-between border-t border-border px-6 py-4">
			<button
				x-show="step > 1"
				@click="prevStep()"
				class="text-sm font-medium text-text-secondary transition hover:text-text-primary"
			>Back</button>
			<span x-show="step === 1"></span>

			<div class="flex items-center gap-3">
				<button
					@click="dismiss()"
					class="text-sm text-text-tertiary transition hover:text-text-secondary"
				>Skip</button>

				<button
					x-show="step < totalSteps"
					@click="nextStep()"
					class="rounded-lg bg-accent px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover"
				>Continue</button>

				<button
					x-show="step === totalSteps"
					@click="submit()"
					:disabled="loading"
					class="rounded-lg bg-accent px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover disabled:opacity-50"
				>
					<span x-show="!loading">Create Project</span>
					<span x-show="loading" class="flex items-center gap-2">
						<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
						</svg>
						Creating...
					</span>
				</button>
			</div>
		</div>
	</div>
</div>
