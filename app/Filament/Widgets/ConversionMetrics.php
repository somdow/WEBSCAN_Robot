<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\SubscriptionUsage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConversionMetrics extends StatsOverviewWidget
{
	protected static ?int $sort = 6;

	protected ?string $heading = "Conversion Signals";

	protected function getStats(): array
	{
		$freePlan = Plan::where("slug", "free")->first();

		if ($freePlan === null) {
			return array(
				Stat::make("Free Plan", "Not Found")
					->description("Run PlanSeeder to create plans")
					->color("warning"),
			);
		}

		$freeOrgs = Organization::where("plan_id", $freePlan->id)->get();
		$freeOrgCount = $freeOrgs->count();

		$atScanLimit = 0;
		$atProjectLimit = 0;
		$activeThisMonth = 0;

		foreach ($freeOrgs as $freeOrg) {
			$usage = SubscriptionUsage::where("organization_id", $freeOrg->id)
				->where("period_start", "<=", now())
				->where("period_end", ">=", now())
				->first();

			if ($usage !== null) {
				$activeThisMonth++;

				if ($usage->scans_used >= $freePlan->max_scans_per_month) {
					$atScanLimit++;
				}
			}

			if ($freeOrg->projects()->count() >= $freePlan->max_projects) {
				$atProjectLimit++;
			}
		}

		$conversionRate = $freeOrgCount > 0
			? round(($atScanLimit / $freeOrgCount) * 100, 1)
			: 0;

		return array(
			Stat::make("Free Orgs Active", $activeThisMonth)
				->description("Used scans this billing period")
				->color("primary"),
			Stat::make("At Scan Limit", $atScanLimit)
				->description("{$conversionRate}% of free orgs — upgrade candidates")
				->color($atScanLimit > 0 ? "warning" : "gray"),
			Stat::make("At Project Limit", $atProjectLimit)
				->description("Need more projects — upgrade candidates")
				->color($atProjectLimit > 0 ? "warning" : "gray"),
		);
	}
}
