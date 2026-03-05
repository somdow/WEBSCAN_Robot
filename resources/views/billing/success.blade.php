<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Subscription Confirmed</h1>
		<x-breadcrumb :items="array(
			array('label' => 'Home', 'url' => route('dashboard')),
			array('label' => 'Billing', 'url' => route('billing.index')),
			array('label' => 'Success'),
		)" />
	</x-slot>

	<div class="mx-auto max-w-lg">
		<div class="rounded-lg border border-emerald-200 bg-emerald-50 p-8 text-center shadow-card">
			<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
				<svg class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
				</svg>
			</div>

			<h2 class="mt-4 text-xl font-bold text-emerald-900">Welcome to {{ $plan->name ?? "your new plan" }}!</h2>
			<p class="mt-2 text-sm text-emerald-700">
				Your subscription is now active. You have full access to all {{ $plan->name ?? "" }} features.
			</p>

			<div class="mt-6 rounded-lg border border-emerald-200 bg-white/60 p-4 text-left">
				<dl class="space-y-2 text-sm">
					<div class="flex justify-between">
						<dt class="text-emerald-700">Plan</dt>
						<dd class="font-medium text-emerald-900">{{ $plan->name ?? "N/A" }}</dd>
					</div>
					<div class="flex justify-between">
						<dt class="text-emerald-700">Projects</dt>
						<dd class="font-medium text-emerald-900">{{ $plan->max_projects ?? 1 }}</dd>
					</div>
					<div class="flex justify-between">
						<dt class="text-emerald-700">Scans per month</dt>
						<dd class="font-medium text-emerald-900">{{ $plan->max_scans_per_month ?? 10 }}</dd>
					</div>
					<div class="flex justify-between">
						<dt class="text-emerald-700">Team members</dt>
						<dd class="font-medium text-emerald-900">{{ $plan->max_users ?? 1 }}</dd>
					</div>
				</dl>
			</div>

			<div class="mt-6">
				<x-primary-button :href="route('dashboard')">
					Go to Dashboard
				</x-primary-button>
			</div>
		</div>
	</div>
</x-app-layout>
