<section>
	<header>
		<h2 class="text-lg font-semibold text-text-primary">Payment Method</h2>
		<p class="mt-1 text-sm text-text-secondary">Manage your payment information via Stripe's secure portal.</p>
	</header>

	<div class="mt-6">
		@if($organization->hasDefaultPaymentMethod())
			<div class="flex items-center gap-4">
				<div class="flex h-10 w-16 items-center justify-center rounded-md border border-border bg-gray-50">
					<span class="text-xs font-semibold uppercase text-text-secondary">{{ $organization->pm_type ?? "Card" }}</span>
				</div>
				<div>
					<p class="text-sm font-medium text-text-primary">
						&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; {{ $organization->pm_last_four }}
					</p>
				</div>
			</div>
		@else
			<p class="text-sm text-text-secondary">No payment method on file.</p>
		@endif

		@if($isOwner && $isStripeConfigured)
			<form method="POST" action="{{ route("billing.portal") }}" class="mt-4">
				@csrf
				<button type="submit" class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-border bg-surface px-4 py-2 text-sm font-medium text-text-primary shadow-sm transition hover:bg-gray-50">
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
					</svg>
					Update Payment Method
				</button>
			</form>
		@endif
	</div>
</section>
