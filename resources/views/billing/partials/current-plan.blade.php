<section>
	<header>
		<h2 class="text-lg font-semibold text-text-primary">Current Plan</h2>
		<p class="mt-1 text-sm text-text-secondary">Your subscription details and usage for this billing period.</p>
	</header>

	<div class="mt-6">
		{{-- Plan name & status --}}
		<div class="flex items-center gap-3">
			<span class="text-2xl font-bold text-text-primary">{{ $plan->name ?? "Free" }}</span>

			@if($organization->subscriptionOnGracePeriod())
				<span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
					Cancelling
				</span>
			@elseif($organization->onTrial())
				<span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
					Trial
				</span>
			@elseif(!$organization->isOnFreePlan())
				<span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
					Active
				</span>
			@endif
		</div>

		{{-- Price & billing cycle --}}
		@if($plan && $plan->slug !== "free")
			<p class="mt-1 text-sm text-text-secondary">
				${{ number_format($plan->price_monthly, 0) }}/month
				@if($organization->billingCycle() === "annual")
					<span class="text-emerald-600">(billed annually — ${{ number_format($plan->price_annual, 0) }}/year)</span>
				@endif
			</p>
		@else
			<p class="mt-1 text-sm text-text-secondary">Free forever — no credit card required.</p>
		@endif

		@if($organization->subscriptionOnGracePeriod())
			<p class="mt-2 text-sm text-amber-700">
				Your subscription will end on {{ $organization->subscription("default")->ends_at->format("F j, Y") }}.
				You will retain full access until then.
			</p>
		@endif

		{{-- Usage bars --}}
		<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
			{{-- Projects used --}}
			<div>
				<div class="flex items-center justify-between text-sm">
					<span class="text-text-secondary">Projects</span>
					<span class="font-medium text-text-primary">{{ $organization->projects()->count() }} / {{ $plan->max_projects ?? 1 }}</span>
				</div>
				@php
					$projectPercent = min(100, round(($organization->projects()->count() / max(1, $plan->max_projects ?? 1)) * 100));
				@endphp
				<div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100">
					<div class="h-full rounded-full {{ $projectPercent >= 90 ? 'bg-red-500' : ($projectPercent >= 70 ? 'bg-amber-500' : 'bg-accent') }}" style="width: {{ $projectPercent }}%"></div>
				</div>
			</div>

			{{-- Scans used this month --}}
			<div>
				@php
					$maxScans = $plan->max_scans_per_month ?? 10;
					$scanPercent = min(100, round(($usage->scans_used / max(1, $maxScans)) * 100));
				@endphp
				<div class="flex items-center justify-between text-sm">
					<span class="text-text-secondary">Scans this month</span>
					<span class="font-medium text-text-primary">{{ $usage->scans_used }} / {{ $maxScans }}</span>
				</div>
				<div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100">
					<div class="h-full rounded-full {{ $scanPercent >= 90 ? 'bg-red-500' : ($scanPercent >= 70 ? 'bg-amber-500' : 'bg-accent') }}" style="width: {{ $scanPercent }}%"></div>
				</div>
			</div>
		</div>
	</div>
</section>
