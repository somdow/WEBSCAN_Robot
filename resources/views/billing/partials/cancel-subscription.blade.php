<section>
	<header>
		<h2 class="text-lg font-semibold text-red-800">Cancel Subscription</h2>
		<p class="mt-1 text-sm text-red-700/70">
			@if($organization->subscriptionOnGracePeriod())
				Your subscription is already cancelled and will end on {{ $organization->subscription("default")->ends_at->format("F j, Y") }}.
			@else
				Once cancelled, your subscription will remain active until the end of the current billing period. After that, you will be downgraded to the Free plan.
			@endif
		</p>
	</header>

	<div class="mt-6">
		@if($organization->subscriptionOnGracePeriod())
			<form method="POST" action="{{ route("billing.resume") }}">
				@csrf
				<x-primary-button type="submit">
					Resume Subscription
				</x-primary-button>
			</form>
		@elseif($organization->subscribed("default"))
			<form method="POST" action="{{ route("billing.cancel") }}" x-data="{ confirming: false }">
				@csrf
				<div x-show="!confirming">
					<x-danger-button type="button" @click="confirming = true">
						Cancel Subscription
					</x-danger-button>
				</div>

				<div x-show="confirming" x-cloak class="flex items-center gap-3">
					<p class="text-sm text-red-700">Are you sure? This cannot be undone immediately.</p>
					<x-danger-button type="submit">
						Yes, Cancel
					</x-danger-button>
					<button type="button" @click="confirming = false" class="cursor-pointer text-sm font-medium text-text-secondary hover:text-text-primary">
						Never Mind
					</button>
				</div>
			</form>
		@endif
	</div>
</section>
