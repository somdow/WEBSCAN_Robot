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
		<div
			x-data='{
						brandName: @json(old("pdf_company_name", $organization->pdf_company_name ?? "")),
						brandColor: @json(old("brand_color", $organization->brand_color ?? $defaultAccentColor)),
						logoPreview: @json($logoUrl),
						logoServerPreview: @json($logoUrl),
						logoFileName: null,
						handleLogoSelect(event) {
							const file = event.target.files[0];
							if (!file) {
								return;
							}
							this.logoFileName = file.name;
							const reader = new FileReader();
							reader.onload = (loadEvent) => {
								this.logoPreview = (loadEvent.target && loadEvent.target.result) ? loadEvent.target.result : null;
							};
							reader.readAsDataURL(file);
						},
						clearSelectedLogo() {
							this.logoPreview = this.logoServerPreview;
							this.logoFileName = null;
							document.getElementById("logo").value = "";
						},
						confirmLogoRemoval() {
							if (confirm("Remove the logo?")) {
								document.getElementById("delete-logo-form").submit();
							}
						}
				}'
		>
			{{-- Branding Form --}}
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				<h2 class="text-lg font-semibold text-text-primary">PDF Report Branding</h2>
				<p class="mt-1 text-sm text-text-secondary">These settings apply to all PDF reports generated for this organization.</p>

				<form
					method="POST"
					action="{{ route("branding.update") }}"
					enctype="multipart/form-data"
					class="mt-6 space-y-6"
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

						<div class="mt-3 grid gap-4 rounded-xl border border-border bg-gray-50/60 p-4 sm:grid-cols-[1fr_220px]">
							<div>
								<label for="logo" class="block text-sm font-medium text-text-primary">Upload logo file</label>
								<p class="mt-1 text-xs text-text-tertiary">Best results: transparent PNG around 600x200.</p>
								<input
									type="file"
									name="logo"
									id="logo"
									accept="image/jpeg,image/png"
									@change="handleLogoSelect($event)"
									class="mt-3 block w-full text-sm text-text-secondary file:mr-3 file:rounded-md file:border-0 file:bg-accent/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-accent hover:file:bg-accent/20"
								>
								<div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
									<template x-if="logoFileName">
										<span class="rounded-md bg-white px-2 py-1 text-text-secondary" x-text="logoFileName"></span>
									</template>
									<button
										type="button"
										class="font-medium text-text-tertiary transition hover:text-text-secondary"
										@click="clearSelectedLogo()"
									>
										Clear selected file
									</button>
									@if($organization->logo_path)
										<button
											type="button"
											class="font-medium text-red-600 transition hover:text-red-700"
											@click="confirmLogoRemoval()"
										>
											Remove current logo
										</button>
									@endif
								</div>
								@error("logo")
									<p class="mt-2 text-sm text-red-600">{{ $message }}</p>
								@enderror
							</div>

							<div
								class="flex min-h-36 items-center justify-center rounded-lg border border-dashed border-border bg-white p-4"
							>
								<img
									x-show="logoPreview"
									x-bind:src="logoPreview"
									alt="Logo preview"
									class="max-h-24 w-full object-contain"
								>
								<div x-show="!logoPreview" class="text-center text-xs text-text-tertiary">
									<div class="font-medium text-text-secondary">Logo preview</div>
									<div class="mt-1">No logo selected yet</div>
								</div>
							</div>
						</div>
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

			{{-- Live Preview — mirrors scan-pdf.blade.php cover page exactly --}}
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				<h2 class="text-lg font-semibold text-text-primary">Preview</h2>
				<p class="mt-1 text-sm text-text-secondary">This is how the first page of your PDF report will look.</p>

				<div class="mx-auto mt-4 overflow-hidden rounded-lg border border-border bg-white shadow-sm" style="max-width: 720px; font-family: 'DejaVu Sans', sans-serif;">
					{{-- PDF body area with matching margins --}}
					<div style="padding: 40px;">

						{{-- Cover top: brand left, scores right --}}
						<table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
							<tr>
								<td style="vertical-align: top;">
									<div x-show="logoPreview" style="margin-bottom: 6px;">
										<img x-bind:src="logoPreview" style="max-width: 220px; max-height: 70px;" alt="Logo">
									</div>
									<div x-show="!logoPreview" style="font-size: 22px; font-weight: bold; color: #111827;" x-text="brandName || @json($organization->name)"></div>
									<div style="font-size: 9px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 2px;">Website Audit Report</div>
								</td>
								<td style="text-align: right; width: 200px; vertical-align: top;">
									{{-- Overall score --}}
									<div style="text-align: center; padding: 24px 30px; border: 4px solid #10B981; border-radius: 12px; background-color: #F0FDF4; margin-bottom: 8px;">
										<div style="font-size: 80px; font-weight: bold; line-height: 1; color: #059669;">85</div>
										<div style="color: #4B5563; margin-top: 6px; font-size: 13px;">Overall Score</div>
									</div>
									{{-- SEO + Health sub-scores --}}
									<table style="width: 100%; border-collapse: collapse;">
										<tr>
											<td style="width: 50%; padding-right: 3px;">
												<div style="text-align: center; padding: 8px 6px; border: 3px solid #10B981; border-radius: 8px; background-color: #F0FDF4;">
													<div style="font-size: 28px; font-weight: bold; line-height: 1; color: #059669;">78</div>
													<div style="color: #4B5563; margin-top: 4px; font-size: 9px;">SEO</div>
												</div>
											</td>
											<td style="width: 50%; padding-left: 3px;">
												<div style="text-align: center; padding: 8px 6px; border: 3px solid #F59E0B; border-radius: 8px; background-color: #FFFBEB;">
													<div style="font-size: 28px; font-weight: bold; line-height: 1; color: #D97706;">62</div>
													<div style="color: #4B5563; margin-top: 4px; font-size: 9px;">Health</div>
												</div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>

						{{-- "Review of domain.com" --}}
						<div style="font-size: 32px; color: #374151; line-height: 1.3; margin-bottom: 6px;">
							Review of <span style="font-weight: bold;" :style="{ color: brandColor }">example.com</span>
						</div>
						<div style="font-size: 13px; color: #9CA3AF; margin-bottom: 50px;">Generated on {{ now()->format("F j, Y") }}</div>

						{{-- Introduction two-column --}}
						<table style="width: 100%; border-collapse: collapse;">
							<tr>
								<td style="width: 140px; vertical-align: top; padding-right: 20px;">
									<div style="font-size: 14px; color: #9CA3AF; letter-spacing: 0.5px;">Introduction</div>
								</td>
								<td>
									<div style="font-size: 12px; color: #6B7280; line-height: 1.8;">
										This report is a comprehensive audit of example.com, analyzing 52 key factors across SEO, security, performance, content quality, and technical health.
									</div>
								</td>
							</tr>
							<tr>
								<td style="width: 140px; vertical-align: top; padding-right: 20px; padding-top: 60px;">
									<div style="font-size: 14px; color: #9CA3AF; letter-spacing: 0.5px;">Prepared By</div>
								</td>
								<td style="padding-top: 60px;">
									<div style="font-size: 12px; font-weight: bold; color: #111827;" x-text="brandName || @json($organization->name)"></div>
									<div style="font-size: 12px; color: #6B7280;">{{ now()->format("F j, Y") }}</div>
								</td>
							</tr>
						</table>

					</div>

					{{-- Footer --}}
					<div style="padding: 8px 0; font-size: 9px; color: #9CA3AF; text-align: center; border-top: 1px solid #F3F4F6;">
						Generated by {{ $siteName }} &mdash; {{ now()->format("F j, Y") }}
					</div>
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
