<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use App\Models\Plan;
use Filament\Widgets\ChartWidget;

class PlanDistributionChart extends ChartWidget
{
	protected static ?int $sort = 4;

	protected ?string $heading = "Organizations by Plan";

	protected ?string $maxHeight = "300px";

	protected int | string | array $columnSpan = 1;

	protected function getType(): string
	{
		return "doughnut";
	}

	protected function getData(): array
	{
		$plans = Plan::withCount("organizations")->ordered()->get();

		return array(
			"datasets" => array(
				array(
					"data" => $plans->pluck("organizations_count")->toArray(),
					"backgroundColor" => array(
						"#6366f1",
						"#8b5cf6",
						"#a78bfa",
						"#c4b5fd",
						"#ddd6fe",
					),
				),
			),
			"labels" => $plans->pluck("name")->toArray(),
		);
	}
}
