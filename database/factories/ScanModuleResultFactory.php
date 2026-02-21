<?php

namespace Database\Factories;

use App\Enums\ModuleStatus;
use App\Models\Scan;
use App\Models\ScanModuleResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanModuleResult>
 */
class ScanModuleResultFactory extends Factory
{
	protected $model = ScanModuleResult::class;

	public function definition(): array
	{
		return array(
			"scan_id" => Scan::factory(),
			"module_key" => "title_tag",
			"status" => ModuleStatus::Ok,
			"findings" => array(
				array("type" => "info", "message" => "Title tag found."),
			),
			"recommendations" => array(),
		);
	}
}
