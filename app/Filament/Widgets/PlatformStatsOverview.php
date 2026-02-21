<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
	protected static ?int $sort = 1;

	protected function getStats(): array
	{
		$usersThisMonth = User::where("created_at", ">=", now()->startOfMonth())->count();
		$orgsThisMonth = Organization::where("created_at", ">=", now()->startOfMonth())->count();
		$projectsThisMonth = Project::where("created_at", ">=", now()->startOfMonth())->count();
		$scansThisMonth = Scan::where("created_at", ">=", now()->startOfMonth())->count();

		return array(
			Stat::make("Total Users", User::count())
				->description("{$usersThisMonth} this month")
				->color("primary"),
			Stat::make("Organizations", Organization::count())
				->description("{$orgsThisMonth} this month")
				->color("primary"),
			Stat::make("Projects", Project::count())
				->description("{$projectsThisMonth} this month")
				->color("success"),
			Stat::make("Scans", Scan::count())
				->description("{$scansThisMonth} this month")
				->color("success"),
		);
	}
}
