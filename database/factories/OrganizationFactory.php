<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
	protected $model = Organization::class;

	public function definition(): array
	{
		$companyName = fake()->unique()->company();

		return array(
			"name" => $companyName,
			"slug" => str($companyName)->slug()->value(),
			"plan_id" => Plan::factory(),
		);
	}

	/**
	 * Assign a specific plan to this organization.
	 */
	public function withPlan(Plan $plan): static
	{
		return $this->state(fn () => array(
			"plan_id" => $plan->id,
		));
	}

	/**
	 * Organization on the free plan.
	 */
	public function onFreePlan(): static
	{
		return $this->state(fn () => array(
			"plan_id" => Plan::factory()->free(),
		));
	}
}
