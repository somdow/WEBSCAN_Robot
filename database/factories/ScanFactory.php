<?php

namespace Database\Factories;

use App\Enums\CreditState;
use App\Enums\ScanStatus;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scan>
 */
class ScanFactory extends Factory
{
	protected $model = Scan::class;

	public function definition(): array
	{
		return array(
			"project_id" => Project::factory(),
			"triggered_by" => User::factory(),
			"status" => ScanStatus::Completed,
			"scan_type" => "single",
			"overall_score" => fake()->numberBetween(20, 100),
			"scan_duration_ms" => fake()->numberBetween(1000, 30000),
			"is_wordpress" => fake()->boolean(30),
			"insecure_transport_used" => false,
			"credit_state" => CreditState::Unclaimed->value,
		);
	}

	/**
	 * A scan still in progress.
	 */
	public function running(): static
	{
		return $this->state(fn () => array(
			"status" => ScanStatus::Running,
			"overall_score" => null,
			"scan_duration_ms" => null,
		));
	}

	/**
	 * A failed scan.
	 */
	public function failed(): static
	{
		return $this->state(fn () => array(
			"status" => ScanStatus::Failed,
			"overall_score" => null,
		));
	}

	/**
	 * A scan that detected WordPress.
	 */
	public function wordpress(): static
	{
		return $this->state(fn () => array(
			"is_wordpress" => true,
		));
	}

	public function crawl(int $maxPages = 25): static
	{
		return $this->state(fn () => array(
			"scan_type" => "crawl",
			"max_pages_requested" => $maxPages,
			"crawl_depth_limit" => 3,
		));
	}
}
