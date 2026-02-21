<?php

namespace App\Filament\Widgets;

use App\Models\Plan;
use Filament\Widgets\ChartWidget;

class ProjectsPerPlanChart extends ChartWidget
{
	protected static ?int $sort = 3;

	protected ?string $heading = "Avg Projects per Plan";

	protected ?string $maxHeight = "300px";

	protected int | string | array $columnSpan = 1;

	protected function getType(): string
	{
		return "bar";
	}

	protected function getData(): array
	{
		$plans = Plan::withCount(array("organizations"))
			->withSum("organizations", "id")
			->ordered()
			->get()
			->map(function (Plan $plan) {
				$organizationCount = $plan->organizations_count;

				if ($organizationCount === 0) {
					return array("name" => $plan->name, "average" => 0, "limit" => $plan->max_projects);
				}

				$projectCount = $plan->organizations()->withCount("projects")->get()->sum("projects_count");
				$average = round($projectCount / $organizationCount, 1);

				return array("name" => $plan->name, "average" => $average, "limit" => $plan->max_projects);
			});

		return array(
			"datasets" => array(
				array(
					"label" => "Avg Projects",
					"data" => $plans->pluck("average")->toArray(),
					"backgroundColor" => "#6366f1",
				),
				array(
					"label" => "Plan Limit",
					"data" => $plans->pluck("limit")->toArray(),
					"backgroundColor" => "#e5e7eb",
				),
			),
			"labels" => $plans->pluck("name")->toArray(),
		);
	}
}
