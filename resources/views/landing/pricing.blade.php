<section class="pricing" id="pricing">
	<div class="head">
		<div class="kicker">Pricing</div>
		<h2>Pricing that grows with your client roster.</h2>
		<p class="pricing-sub">Start free. Add clients as you win them. Transparent limits, no per-seat surprises, cancel anytime.</p>
	</div>
	<div class="p-grid">
		@foreach($plans as $plan)
			@php
				$isFree = (float) $plan->price_monthly === 0.0;
				$isFeatured = $plan->slug === "pro";
				$hasWhiteLabel = $plan->hasFeature("white_label");
				$annualPerMonth = ($plan->price_annual ?? 0) > 0 ? (int) round($plan->price_annual / 12) : 0;
				$annualSavings = $plan->annualSavingsPercent();
				$historyLabel = $plan->scan_history_days >= 36500
					? "Unlimited scan history"
					: "{$plan->scan_history_days}-day scan history";
				$aiTierLabel = array(1 => "basic", 2 => "advanced", 3 => "premium")[$plan->ai_tier] ?? "standard";
				$ctaLabel = $registrationEnabled
					? ($isFree ? "Start free →" : "Choose " . $plan->name . " →")
					: "Get notified →";
				$ctaHref = $registrationEnabled ? route("register") : "#waitlistForm";
				$ctaClass = $isFeatured ? "btn accent" : "btn primary";
			@endphp

			<div class="p-card{{ $isFeatured ? " featured" : "" }}">
				@if($isFeatured)
					<span class="featured-pill">★ Popular</span>
				@endif

				<div class="tier">{{ $plan->name }}</div>
				<div class="price">${{ (int) $plan->price_monthly }}<small>/mo</small></div>

				@if(!$isFree && $annualPerMonth > 0)
					<div class="price-annual">${{ $annualPerMonth }}/mo billed annually · save {{ (int) $annualSavings }}%</div>
				@endif

				<div class="p-tagline">{{ $plan->description }}</div>

				<ul class="p-features">
					<li>
						<span class="feat-strong">{{ $plan->max_projects }} {{ Str::plural("project", $plan->max_projects) }}</span>
						· up to {{ $plan->max_users }} {{ $plan->max_users === 1 ? "user" : "team members" }}
					</li>
					<li><span class="feat-strong">{{ $plan->max_scans_per_month }} scans</span> per month</li>
					<li>Up to {{ $plan->max_pages_per_scan }} pages per scan</li>
					<li>{{ $plan->max_competitors }} competitor benchmarks</li>
					<li>AI insights ({{ $aiTierLabel }})</li>
					<li>
						@if($plan->scan_history_days >= 36500)
							<span class="feat-strong">Unlimited</span> scan history
						@else
							{{ $historyLabel }}
						@endif
					</li>
					<li>
						@if($hasWhiteLabel)
							Download unlimited <span class="feat-strong">white-label</span> PDFs
						@else
							Download unlimited PDFs
						@endif
					</li>
				</ul>

				<a class="{{ $ctaClass }}" href="{{ $ctaHref }}">{{ $ctaLabel }}</a>
			</div>
		@endforeach
	</div>
</section>
