<?php

namespace Database\Factories;

use App\Enums\CreditState;
use App\Models\Scan;
use App\Models\ScanPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanPage>
 */
class ScanPageFactory extends Factory
{
	protected $model = ScanPage::class;

	public function definition(): array
	{
		return array(
			"scan_id" => Scan::factory(),
			"project_id" => function (array $attributes) {
				return Scan::query()->whereKey($attributes["scan_id"])->value("project_id");
			},
			"url" => fake()->url(),
			"page_score" => fake()->numberBetween(20, 100),
			"http_status_code" => 200,
			"content_type" => "text/html",
			"is_homepage" => false,
			"crawl_depth" => fake()->numberBetween(0, 3),
			"scan_duration_ms" => fake()->numberBetween(500, 5000),
			"credit_state" => CreditState::Unclaimed->value,
		);
	}

	public function homepage(): static
	{
		return $this->state(fn () => array(
			"is_homepage" => true,
			"crawl_depth" => 0,
		));
	}

	public function failed(): static
	{
		return $this->state(fn () => array(
			"page_score" => null,
			"http_status_code" => 500,
			"error_message" => "Failed to fetch page",
		));
	}
}
