<x-app-layout>
	<x-slot name="header">
		<div class="text-center">
			<h1 class="text-3xl font-bold tracking-tight text-text-primary">Pick the plan that fits your needs</h1>
			<p class="mt-2 text-base text-text-secondary">Every plan includes all features. Upgrade for more volume.</p>
		</div>
	</x-slot>

	<div x-data="{ billingCycle: 'monthly' }" class="mx-auto max-w-4xl">
		{{-- Billing cycle toggle --}}
		<div class="flex justify-center">
			<div class="inline-flex items-center rounded-lg border border-border bg-surface p-1 shadow-card">
				<button
					@click="billingCycle = 'monthly'"
					:class="billingCycle === 'monthly' ? 'bg-accent text-white shadow-sm' : 'text-text-secondary hover:text-text-primary'"
					class="cursor-pointer rounded-md px-5 py-2 text-sm font-medium transition"
				>Monthly</button>
				<button
					@click="billingCycle = 'annual'"
					:class="billingCycle === 'annual' ? 'bg-accent text-white shadow-sm' : 'text-text-secondary hover:text-text-primary'"
					class="cursor-pointer rounded-md px-5 py-2 text-sm font-medium transition"
				>
					Annual
					<span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-semibold text-emerald-700">{{ $annualDiscountText }}</span>
				</button>
			</div>
		</div>

		{{-- Plan cards --}}
		<div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
			@foreach($plans as $planItem)
				@php
					$isCurrent = $currentPlanId !== null && $planItem->id === $currentPlanId;
					$isHighlighted = $planItem->slug === "pro";
					$isFree = $planItem->slug === "free";
					$historyLabel = $planItem->scan_history_days >= 36500 ? "Unlimited history" : "{$planItem->scan_history_days}-day scan history";
				@endphp

				<div class="relative flex flex-col rounded-xl border {{ $isHighlighted ? 'border-accent ring-2 ring-accent' : 'border-border' }} bg-surface p-6 shadow-card">
					@if($isHighlighted)
						<div class="absolute -top-3 left-1/2 -translate-x-1/2">
							<span class="rounded-full bg-accent px-3 py-1 text-xs font-semibold text-white">Most Popular</span>
						</div>
					@endif

					@if($isCurrent)
						<div class="absolute -top-3 right-4">
							<span class="rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white">Current Plan</span>
						</div>
					@endif

					<h3 class="text-lg font-semibold text-text-primary">{{ $planItem->name }}</h3>

					<div class="mt-4">
						@if($isFree)
							<span class="text-4xl font-bold text-text-primary">$0</span>
							<span class="text-sm text-text-secondary">/month</span>
						@else
							<span class="text-4xl font-bold text-text-primary" x-show="billingCycle === 'monthly'">${{ number_format($planItem->price_monthly, 0) }}</span>
							<span class="text-4xl font-bold text-text-primary" x-show="billingCycle === 'annual'" style="display: none;">${{ number_format(($planItem->price_annual ?? 0) / 12, 0) }}</span>
							<span class="text-sm text-text-secondary">/month</span>
							<p class="mt-1 text-xs text-text-tertiary" x-show="billingCycle === 'annual'" style="display: none;">
								${{ number_format($planItem->price_annual ?? 0, 0) }} billed annually
							</p>
						@endif
					</div>

					<p class="mt-3 text-sm text-text-secondary">{{ $planItem->description }}</p>

					<ul class="mt-6 flex-1 space-y-3 text-sm">
						<li class="flex items-center gap-2 text-text-primary">
							<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
							<strong>{{ $planItem->max_scans_per_month }}</strong>&nbsp;scans/month
						</li>
						<li class="flex items-center gap-2 text-text-primary">
							<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
							<strong>{{ $planItem->max_projects }}</strong>&nbsp;project{{ $planItem->max_projects > 1 ? "s" : "" }}
						</li>
						<li class="flex items-center gap-2 text-text-primary">
							<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
							<strong>{{ $planItem->max_users }}</strong>&nbsp;team member{{ $planItem->max_users > 1 ? "s" : "" }}
						</li>
						<li class="flex items-center gap-2 text-text-primary">
							<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
							{{ $historyLabel }}
						</li>
						<li class="flex items-center gap-2 text-text-primary">
							<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
							AI optimization (BYOK)
						</li>
						<li class="flex items-center gap-2 text-text-primary">
							<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
							All 48 scan modules
						</li>
						@if($planItem->hasFeature("white_label"))
							<li class="flex items-center gap-2 text-text-primary">
								<svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
								White-label PDF reports
							</li>
						@endif
					</ul>

					<div class="mt-6">
						@if($isCurrent)
							<span class="block w-full rounded-md border border-border bg-gray-50 py-2.5 text-center text-sm font-medium text-text-tertiary">
								Current Plan
							</span>
						@elseif($isFree)
							<a href="{{ \App\Models\Setting::getValue("registration_enabled", "0") === "1" ? route("register") : route("login") }}" class="block w-full rounded-md border border-border bg-surface py-2.5 text-center text-sm font-semibold text-text-primary shadow-sm transition hover:bg-gray-50">
								Get Started
							</a>
						@elseif(auth()->check())
							<button
								@click="$dispatch('open-stripe-checkout', { planId: {{ $planItem->id }}, billingCycle: billingCycle })"
								class="w-full cursor-pointer rounded-md {{ $isHighlighted ? 'bg-accent text-white hover:bg-accent-hover' : 'bg-text-primary text-white hover:bg-gray-800' }} py-2.5 text-sm font-semibold shadow-sm transition"
							>
								Start {{ $trialDays }}-Day Trial
							</button>
						@else
							<a href="{{ \App\Models\Setting::getValue("registration_enabled", "0") === "1" ? route("register") : route("login") }}" class="block w-full rounded-md {{ $isHighlighted ? 'bg-accent text-white hover:bg-accent-hover' : 'bg-text-primary text-white hover:bg-gray-800' }} py-2.5 text-center text-sm font-semibold shadow-sm transition">
								Start {{ $trialDays }}-Day Trial
							</a>
						@endif
					</div>
				</div>
			@endforeach
		</div>

		{{-- Enterprise CTA --}}
		<div class="mt-10 text-center">
			<p class="text-sm text-text-secondary">
				Need more? <a href="mailto:{{ $enterpriseEmail }}" class="font-medium text-accent hover:text-accent-hover">Contact us for Enterprise pricing</a>
			</p>
		</div>

		<x-footer-links />
	</div>
</x-app-layout>
