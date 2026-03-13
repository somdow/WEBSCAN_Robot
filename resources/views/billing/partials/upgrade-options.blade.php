<section x-data="{
	billingCycle: 'monthly',
	confirmOpen: false,
	confirmPlanName: '',
	confirmAction: '',
	confirmPriceMonthly: '',
	confirmPriceAnnual: '',
	confirmFormEl: null,
	requestChange(formEl, planName, action, priceMonthly, priceAnnual) {
		this.confirmFormEl = formEl;
		this.confirmPlanName = planName;
		this.confirmAction = action;
		this.confirmPriceMonthly = priceMonthly;
		this.confirmPriceAnnual = priceAnnual;
		this.confirmOpen = true;
	},
	submitChange() {
		if (this.confirmFormEl) this.confirmFormEl.submit();
	},
}">
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
						$changeLabel = $isUpgrade ? "Upgrade" : "Downgrade";
						$monthlyPrice = number_format($availablePlan->price_monthly, 0);
						$annualPerMonth = number_format(($availablePlan->price_annual ?? 0) / 12, 0);
					@endphp

					@if($organization->subscribed("default"))
						<form x-ref="changePlanForm{{ $availablePlan->id }}" method="POST" action="{{ route("billing.change-plan") }}" class="mt-5">
							@csrf
							<input type="hidden" name="plan_id" value="{{ $availablePlan->id }}">
							<input type="hidden" name="billing_cycle" x-bind:value="billingCycle">
							<button
								type="button"
								@click="requestChange($refs.changePlanForm{{ $availablePlan->id }}, '{{ $availablePlan->name }}', '{{ $changeLabel }}', '{{ $monthlyPrice }}', '{{ $annualPerMonth }}')"
								class="inline-flex w-full cursor-pointer items-center justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-hover"
							>
								{{ $changeLabel }}
							</button>
						</form>
					@else
						<div class="mt-5">
							<x-primary-button
								@click="$dispatch('open-stripe-checkout', { planId: {{ $availablePlan->id }}, billingCycle: billingCycle })"
								class="w-full justify-center"
							>
								Subscribe
							</x-primary-button>
						</div>
					@endif
				@endif
			</div>
		@endforeach
	</div>
	{{-- Plan change confirmation modal --}}
	<div
		x-show="confirmOpen"
		x-cloak
		class="fixed inset-0 z-50 flex items-center justify-center"
	>
		<div
			x-show="confirmOpen"
			x-transition:enter="transition-opacity duration-200"
			x-transition:enter-start="opacity-0"
			x-transition:enter-end="opacity-100"
			x-transition:leave="transition-opacity duration-150"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			class="absolute inset-0 bg-black/50"
			@click="confirmOpen = false"
		></div>

		<div
			x-show="confirmOpen"
			x-transition:enter="transition duration-200 ease-out"
			x-transition:enter-start="scale-95 opacity-0"
			x-transition:enter-end="scale-100 opacity-100"
			x-transition:leave="transition duration-150 ease-in"
			x-transition:leave-start="scale-100 opacity-100"
			x-transition:leave-end="scale-95 opacity-0"
			class="relative w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl"
			@click.stop
		>
			<h3 class="text-lg font-semibold text-text-primary" x-text="confirmAction + ' to ' + confirmPlanName"></h3>

			<div class="mt-3 rounded-lg border border-border bg-gray-50 p-4">
				<p class="text-sm text-text-secondary">New price:</p>
				<div class="mt-1">
					<span class="text-2xl font-bold text-text-primary" x-show="billingCycle === 'monthly'">$<span x-text="confirmPriceMonthly"></span></span>
					<span class="text-2xl font-bold text-text-primary" x-show="billingCycle === 'annual'">$<span x-text="confirmPriceAnnual"></span></span>
					<span class="text-sm text-text-secondary">/month</span>
					<span class="text-sm text-text-tertiary" x-show="billingCycle === 'annual'">(billed annually)</span>
				</div>
			</div>

			<p class="mt-3 text-xs text-text-tertiary">
				<span x-show="confirmAction === 'Upgrade'">You will be charged the prorated difference immediately.</span>
				<span x-show="confirmAction === 'Downgrade'">The change takes effect at your next billing cycle. You keep current plan access until then.</span>
			</p>

			<div class="mt-5 flex gap-3">
				<button
					@click="confirmOpen = false"
					class="flex-1 cursor-pointer rounded-md border border-border bg-white px-4 py-2.5 text-sm font-medium text-text-primary transition hover:bg-gray-50"
				>Cancel</button>
				<button
					@click="submitChange()"
					class="flex-1 cursor-pointer rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-accent-hover"
					x-text="'Confirm ' + confirmAction"
				></button>
			</div>
		</div>
	</div>
</section>
