<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Profile</h1>
		<p class="mt-1 text-sm text-text-secondary">Manage your account settings and preferences.</p>
		<x-breadcrumb :items="array(
			array('label' => 'Home', 'url' => route('dashboard')),
			array('label' => 'Settings'),
		)" />
	</x-slot>

	<div class="space-y-6">
		<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
			<div class="max-w-xl">
				@include("profile.partials.update-profile-information-form")
			</div>
		</div>

		<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
			<div class="max-w-xl">
				@include("profile.partials.update-password-form")
			</div>
		</div>

		<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
			<div class="max-w-xl">
				@include("profile.partials.update-ai-settings-form")
			</div>
		</div>

		<div class="rounded-lg border border-red-200 bg-red-50 p-6 sm:p-8">
			<div class="max-w-xl">
				@include("profile.partials.delete-user-form")
			</div>
		</div>
	</div>
</x-app-layout>
