<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
	protected $model = Plan::class;

	public function definition(): array
	{
		return array(
			"name" => fake()->unique()->randomElement(array("Starter", "Growth", "Scale", "Enterprise")),
			"slug" => fn (array $attributes) => str($attributes["name"])->slug()->value(),
			"description" => fake()->sentence(),
			"price_monthly" => fake()->randomFloat(2, 0, 199),
			"price_annual" => fn (array $attributes) => round($attributes["price_monthly"] * 10, 2),
			"max_users" => fake()->randomElement(array(1, 5, 15, 50)),
			"max_projects" => fake()->randomElement(array(1, 5, 25, 100)),
			"max_scans_per_month" => fake()->randomElement(array(5, 50, 200, 1000)),
			"max_pages_per_scan" => fake()->randomElement(array(1, 10, 25, 100)),
			"max_competitors" => fake()->randomElement(array(0, 3, 10, 25)),
			"scan_history_days" => fake()->randomElement(array(7, 30, 90, 365)),
			"ai_tier" => fake()->numberBetween(0, 3),
			"feature_flags" => array(
				"branded_pdf" => false,
				"white_label" => false,
				"api_access" => false,
				"scheduled_scans" => false,
				"leadgen" => false,
			),
			"is_public" => true,
			"max_additional_pages" => 0,
		"sort_order" => fake()->numberBetween(0, 10),
		);
	}

	/**
	 * A free-tier plan with minimal limits.
	 */
	public function free(): static
	{
		return $this->state(fn () => array(
			"name" => "Free",
			"slug" => "free",
			"price_monthly" => 0,
			"price_annual" => 0,
			"max_users" => 1,
			"max_projects" => 1,
			"max_scans_per_month" => 5,
			"max_pages_per_scan" => 1,
			"max_competitors" => 0,
			"scan_history_days" => 7,
			"ai_tier" => 0,
			"max_additional_pages" => 0,
			"sort_order" => 0,
		));
	}

	/**
	 * A paid pro-tier plan.
	 */
	public function pro(): static
	{
		return $this->state(fn () => array(
			"name" => "Pro",
			"slug" => "pro",
			"price_monthly" => 29.00,
			"price_annual" => 290.00,
			"max_users" => 5,
			"max_projects" => 10,
			"max_scans_per_month" => 100,
			"max_pages_per_scan" => 25,
			"max_competitors" => 5,
			"scan_history_days" => 90,
			"ai_tier" => 2,
			"feature_flags" => array(
				"branded_pdf" => true,
				"white_label" => false,
				"api_access" => true,
				"scheduled_scans" => true,
				"leadgen" => false,
			),
			"max_additional_pages" => 10,
			"sort_order" => 1,
		));
	}

	/**
	 * A top-tier agency plan with all features.
	 */
	public function agency(): static
	{
		return $this->state(fn () => array(
			"name" => "Agency",
			"slug" => "agency",
			"price_monthly" => 99.00,
			"price_annual" => 990.00,
			"max_users" => 25,
			"max_projects" => 50,
			"max_scans_per_month" => 500,
			"max_pages_per_scan" => 100,
			"max_competitors" => 25,
			"scan_history_days" => 365,
			"ai_tier" => 3,
			"feature_flags" => array(
				"branded_pdf" => true,
				"white_label" => true,
				"api_access" => true,
				"scheduled_scans" => true,
				"leadgen" => true,
			),
			"max_additional_pages" => 50,
			"sort_order" => 2,
		));
	}
}
