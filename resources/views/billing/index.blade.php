<x-app-layout>
	<x-slot name="header">
		<h1 class="text-[2.5rem] font-bold leading-tight tracking-tight text-text-primary">Billing & Subscription</h1>
		<p class="mt-1 text-sm text-text-secondary">Manage your plan, payment method, and invoices.</p>
		<x-breadcrumb :items="array(
			array('label' => 'Home', 'url' => route('dashboard')),
			array('label' => 'Billing'),
		)" />
	</x-slot>

	<div class="space-y-6">
		{{-- Current Plan & Usage --}}
		<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
			@include("billing.partials.current-plan")
		</div>

		{{-- Upgrade / Downgrade Options (owner only) --}}
		@if($isOwner && $availablePlans->isNotEmpty())
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				@include("billing.partials.upgrade-options")
			</div>
		@endif

		{{-- Payment Method --}}
		@if(!$organization->isOnFreePlan())
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				@include("billing.partials.payment-method")
			</div>
		@endif

		{{-- Invoices --}}
		@if(!$organization->isOnFreePlan())
			<div class="rounded-lg border border-border bg-surface p-6 shadow-card sm:p-8">
				@include("billing.partials.invoices")
			</div>
		@endif

		{{-- Cancel Subscription (danger zone, owner only) --}}
		@if($isOwner && !$organization->isOnFreePlan())
			<div class="rounded-lg border border-red-200 bg-red-50 p-6 sm:p-8">
				@include("billing.partials.cancel-subscription")
			</div>
		@endif
	</div>
</x-app-layout>
