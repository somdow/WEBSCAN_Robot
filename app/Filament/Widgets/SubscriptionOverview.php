<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use App\Models\Plan;
use App\Services\BillingService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionOverview extends StatsOverviewWidget
{
	protected static ?int $sort = 5;

	protected function getStats(): array
	{
		if (!app(BillingService::class)->isStripeConfigured()) {
			return array(
				Stat::make("Stripe Status", "Not Configured")
					->description("Add STRIPE_SECRET to enable billing metrics")
					->color("warning"),
			);
		}

		$freePlan = Plan::where("slug", "free")->first();
		$freeCount = $freePlan
			? Organization::where("plan_id", $freePlan->id)->count()
			: 0;

		$paidOrgs = Organization::whereHas("plan", function ($query) {
			$query->where("slug", "!=", "free");
		})->with(array("plan", "subscriptions"))->get();

		$activeSubscriptions = 0;
		$graceCount = 0;
		$estimatedMrr = 0;

		foreach ($paidOrgs as $organization) {
			if ($organization->subscribed("default")) {
				$activeSubscriptions++;

				$monthlyPrice = $organization->plan->price_monthly ?? 0;
				$estimatedMrr += $monthlyPrice;
			}

			if ($organization->subscriptionOnGracePeriod()) {
				$graceCount++;
			}
		}

		return array(
			Stat::make("Active Subscriptions", $activeSubscriptions)
				->color("success"),
			Stat::make("Estimated MRR", "$" . number_format($estimatedMrr, 2))
				->color("success"),
			Stat::make("Free Accounts", $freeCount)
				->color("gray"),
			Stat::make("Grace Period", $graceCount)
				->description("Cancelled but still active")
				->color($graceCount > 0 ? "warning" : "gray"),
		);
	}
}
