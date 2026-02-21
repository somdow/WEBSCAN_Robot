<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
	protected $model = AuditLog::class;

	public function definition(): array
	{
		return array(
			"user_id" => User::factory(),
			"action" => fake()->randomElement(array("created", "updated", "deleted", "login", "logout")),
			"auditable_type" => fake()->randomElement(array("App\\Models\\Project", "App\\Models\\Organization", "App\\Models\\Scan")),
			"auditable_id" => fake()->numberBetween(1, 100),
			"old_values" => null,
			"new_values" => array("name" => fake()->word()),
			"ip_address" => fake()->ipv4(),
		);
	}

	/**
	 * An update action with old and new values captured.
	 */
	public function updateAction(): static
	{
		$fieldName = fake()->randomElement(array("name", "email", "url"));
		return $this->state(fn () => array(
			"action" => "updated",
			"old_values" => array($fieldName => fake()->word()),
			"new_values" => array($fieldName => fake()->word()),
		));
	}
}
