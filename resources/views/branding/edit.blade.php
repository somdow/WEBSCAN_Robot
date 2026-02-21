<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Branding</h1>
		<p class="mt-1 text-sm text-text-secondary">Customize how your PDF reports look when shared with clients.</p>
		<x-breadcrumb :items="array(
			array('label' => 'Home', 'url' => route('dashboard')),
			array('label' => 'Branding'),
		)" />
	</x-slot>

	<div class="space-y-6">
		@if($canWhiteLabel)
			{{-- Branding Form --}}
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				<h2 class="text-lg font-semibold text-text-primary">PDF Report Branding</h2>
				<p class="mt-1 text-sm text-text-secondary">These settings apply to all PDF reports generated for this organization.</p>

				<form
					method="POST"
					action="{{ route("branding.update") }}"
					enctype="multipart/form-data"
					class="mt-6 space-y-6"
					x-data="{
						brandName: @json(old("pdf_company_name", $organization->pdf_company_name ?? "")),
						brandColor: @json(old("brand_color", $organization->brand_color ?? $defaultAccentColor)),
						logoPreview: @json($organization->logoUrl()),
						handleLogoSelect(event) {
							const file = event.target.files[0];
							if (file) {
								this.logoPreview = URL.createObjectURL(file);
							}
						}
					}"
				>
					@csrf
					@method("PATCH")

					{{-- Company Name --}}
					<div>
						<label for="pdf_company_name" class="block text-sm font-medium text-text-secondary">Company Name</label>
						<p class="mt-0.5 text-xs text-text-tertiary">Appears as the brand name on the PDF cover page and closing page.</p>
						<input
							type="text"
							name="pdf_company_name"
							id="pdf_company_name"
							x-model="brandName"
							maxlength="100"
							placeholder="{{ $organization->name }}"
							value="{{ old("pdf_company_name", $organization->pdf_company_name) }}"
							class="mt-2 block w-full max-w-md rounded-md border border-border bg-white px-3 py-2 text-sm text-text-primary shadow-sm placeholder:text-text-tertiary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
						>
						@error("pdf_company_name")
							<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
						@enderror
					</div>

					{{-- Brand Color --}}
					<div>
						<label for="brand_color" class="block text-sm font-medium text-text-secondary">Accent Color</label>
						<p class="mt-0.5 text-xs text-text-tertiary">Used for accent bars, highlights, and badges throughout the PDF report.</p>
						<div class="mt-2 flex items-center gap-3">
							<input
								type="color"
								name="brand_color"
								id="brand_color"
								x-model="brandColor"
								value="{{ old("brand_color", $organization->brand_color ?? $defaultAccentColor) }}"
								class="h-10 w-14 cursor-pointer rounded border border-border"
							>
							<input
								type="text"
								x-model="brandColor"
								readonly
								class="w-24 rounded-md border border-border bg-gray-50 px-3 py-2 text-sm font-mono text-text-secondary"
							>
							<button
								type="button"
								@click="brandColor = @json($defaultAccentColor); document.getElementById('brand_color').value = @json($defaultAccentColor)"
								class="text-xs font-medium text-text-tertiary transition hover:text-text-secondary"
							>
								Reset to default
							</button>
						</div>
						@error("brand_color")
							<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
						@enderror
					</div>

					{{-- Logo Upload --}}
					<div>
						<label class="block text-sm font-medium text-text-secondary">Logo</label>
						<p class="mt-0.5 text-xs text-text-tertiary">Displayed on the PDF cover page above your company name. JPG or PNG, max 512 KB.</p>

						<div class="mt-2 flex items-start gap-4">
							{{-- Preview --}}
							<div
								class="flex h-16 w-32 items-center justify-center rounded-lg border border-dashed border-border bg-gray-50"
								x-show="logoPreview"
							>
								<img
									x-bind:src="logoPreview"
									alt="Logo preview"
									class="max-h-14 max-w-28 object-contain"
								>
							</div>
							<div
								class="flex h-16 w-32 items-center justify-center rounded-lg border border-dashed border-border bg-gray-50 text-xs text-text-tertiary"
								x-show="!logoPreview"
							>
								No logo
							</div>

							<div class="flex flex-col gap-2">
								<input
									type="file"
									name="logo"
									id="logo"
									accept="image/jpeg,image/png"
									@change="handleLogoSelect"
									class="block w-full text-sm text-text-secondary file:mr-3 file:rounded-md file:border-0 file:bg-accent/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-accent hover:file:bg-accent/20"
								>
								@error("logo")
									<p class="text-sm text-red-600">{{ $message }}</p>
								@enderror
							</div>
						</div>

						@if($organization->logo_path)
							<div class="mt-2">
								<button
									type="button"
									class="text-xs font-medium text-red-600 transition hover:text-red-700"
									@click="if (confirm('Remove the logo?')) { document.getElementById('delete-logo-form').submit(); }"
								>
									Remove current logo
								</button>
							</div>
						@endif
					</div>

					{{-- Save --}}
					<div class="flex items-center gap-4">
						<button
							type="submit"
							class="inline-flex items-center rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent/90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2"
						>
							Save Branding
						</button>

						@if(session("status"))
							<p class="text-sm text-emerald-600">{{ session("status") }}</p>
						@endif
					</div>
				</form>
			</div>

			@if($organization->logo_path)
				<form id="delete-logo-form" method="POST" action="{{ route("branding.destroy-logo") }}" style="display: none;">
					@csrf
					@method("DELETE")
				</form>
			@endif

			{{-- Live Preview --}}
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				<h2 class="text-lg font-semibold text-text-primary">Preview</h2>
				<p class="mt-1 text-sm text-text-secondary">Approximate preview of how your PDF cover page will look.</p>

				<div class="mt-4 rounded-lg border border-border bg-white p-8" style="max-width: 480px;">
					{{-- Mini cover mockup --}}
					<div class="flex items-start justify-between">
						<div>
							<template x-if="logoPreview">
								<img x-bind:src="logoPreview" class="mb-2 max-h-8 max-w-24 object-contain" alt="Logo">
							</template>
							<div class="text-lg font-bold text-gray-900" x-text="brandName || @json($organization->name)"></div>
							<div class="text-[10px] uppercase tracking-widest text-gray-400">Website Audit Report</div>
						</div>
						<div class="flex h-14 w-14 items-center justify-center rounded-lg border-2 border-emerald-400 bg-emerald-50">
							<span class="text-2xl font-bold text-emerald-600">85</span>
						</div>
					</div>
					<div class="mt-2 h-0.5 w-12" :style="{ backgroundColor: brandColor }"></div>
					<div class="mt-4 text-xs text-gray-400">example.com</div>
					<div class="mt-6 border-t border-gray-100 pt-3 text-center text-[9px] text-gray-300">
						Generated by {{ $siteName }}
					</div>
				</div>
			</div>
		@else
			{{-- Upgrade CTA --}}
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				<div class="flex items-start gap-4">
					<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent/10">
						<svg class="h-5 w-5 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
						</svg>
					</div>
					<div>
						<h2 class="text-lg font-semibold text-text-primary">White-Label PDF Reports</h2>
						<p class="mt-1 text-sm text-text-secondary">
							Add your company logo, name, and brand colors to PDF reports you share with clients.
							This feature is available on the <strong>Agency</strong> plan.
						</p>
						<a
							href="{{ route("billing.index") }}"
							class="mt-4 inline-flex items-center gap-2 rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-accent/90"
						>
							<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
							</svg>
							Upgrade Plan
						</a>
					</div>
				</div>
			</div>
		@endif
	</div>
</x-app-layout>
