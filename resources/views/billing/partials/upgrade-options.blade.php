<section x-data="{ billingCycle: 'monthly' }">
	<header>
		<h2 class="text-lg font-semibold text-text-primary">Change Plan</h2>
		<p class="mt-1 text-sm text-text-secondary">Upgrade or downgrade your subscription.</p>
	</header>

	{{-- Monthly / Annual toggle --}}
	<div class="mt-6 flex items-center gap-3">
		<button
			@click="billingCycle = 'monthly'"
			:class="billingCycle === 'monthly' ? 'bg-accent text-white' : 'bg-gray-100 text-text-secondary hover:text-text-primary'"
			class="min-h-[44px] cursor-pointer rounded-md px-4 py-2 text-sm font-medium transition"
		>Monthly</button>
		<button
			@click="billingCycle = 'annual'"
			:class="billingCycle === 'annual' ? 'bg-accent text-white' : 'bg-gray-100 text-text-secondary hover:text-text-primary'"
			class="min-h-[44px] cursor-pointer rounded-md px-4 py-2 text-sm font-medium transition"
		>
			Annual
			<span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-semibold text-emerald-700">{{ $annualDiscountText }}</span>
		</button>
	</div>

	{{-- Plan cards --}}
	<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
		@foreach($availablePlans as $availablePlan)
			@php
				$historyLabel = $availablePlan->scan_history_days >= 36500 ? "Unlimited history" : "{$availablePlan->scan_history_days}-day history";
			@endphp
			<div class="rounded-lg border border-border p-5 {{ $availablePlan->slug === 'pro' ? 'ring-2 ring-accent' : '' }}">
				<h3 class="text-base font-semibold text-text-primary">{{ $availablePlan->name }}</h3>

				<div class="mt-2">
					<span class="text-2xl font-bold text-text-primary" x-show="billingCycle === 'monthly'">${{ number_format($availablePlan->price_monthly, 0) }}</span>
					<span class="text-2xl font-bold text-text-primary" x-show="billingCycle === 'annual'" style="display: none;">${{ number_format(($availablePlan->price_annual ?? 0) / 12, 0) }}</span>
					<span class="text-sm text-text-secondary">/month</span>
				</div>

				<ul class="mt-4 space-y-2 text-sm text-text-secondary">
					<li class="flex items-center gap-2">
						<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
						<strong>{{ $availablePlan->max_scans_per_month }}</strong>&nbsp;scans/month
					</li>
					<li class="flex items-center gap-2">
						<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
						<strong>{{ $availablePlan->max_projects }}</strong>&nbsp;project{{ $availablePlan->max_projects > 1 ? "s" : "" }}
					</li>
					<li class="flex items-center gap-2">
						<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
						<strong>{{ $availablePlan->max_users }}</strong>&nbsp;team member{{ $availablePlan->max_users > 1 ? "s" : "" }}
					</li>
					<li class="flex items-center gap-2">
						<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
						{{ $historyLabel }}
					</li>
				</ul>

				@if($isOwner)
					@php
						$isCurrent = $plan && $availablePlan->id === $plan->id;
						$isUpgrade = $plan && $availablePlan->price_monthly > $plan->price_monthly;
					@endphp

					@if($organization->subscribed("default"))
						<form method="POST" action="{{ route("billing.change-plan") }}" class="mt-5">
							@csrf
							<input type="hidden" name="plan_id" value="{{ $availablePlan->id }}">
							<input type="hidden" name="billing_cycle" x-bind:value="billingCycle">
							<x-primary-button type="submit" class="w-full justify-center">
								{{ $isUpgrade ? "Upgrade" : "Downgrade" }}
							</x-primary-button>
						</form>
					@else
						<form method="POST" action="{{ route("billing.checkout") }}" class="mt-5">
							@csrf
							<input type="hidden" name="plan_id" value="{{ $availablePlan->id }}">
							<input type="hidden" name="billing_cycle" x-bind:value="billingCycle">
							<x-primary-button type="submit" class="w-full justify-center">
								Subscribe
							</x-primary-button>
						</form>
					@endif
				@endif
			</div>
		@endforeach
	</div>
</section>
