<?php

namespace Database\Factories;

use App\Enums\ScanSchedule;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
	protected $model = Project::class;

	public function definition(): array
	{
		return array(
			"organization_id" => Organization::factory(),
			"name" => fake()->domainWord() . " Website",
			"url" => fake()->url(),
			"scan_schedule" => fake()->randomElement(ScanSchedule::cases()),
			"target_keywords" => array(fake()->word(), fake()->word(), fake()->word()),
		);
	}

	/**
	 * Project with no scheduled scanning.
	 */
	public function withoutSchedule(): static
	{
		return $this->state(fn () => array(
			"scan_schedule" => null,
		));
	}
}
