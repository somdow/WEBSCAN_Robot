<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
	use RefreshDatabase;

	public function test_ai_settings_requires_authentication(): void
	{
		$this->patch(route("ai-settings.update"))
			->assertRedirect(route("login"));
	}

	public function test_ai_settings_requires_pro_plan(): void
	{
		$plan = Plan::factory()->free()->create();
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$this->actingAs($user)
			->patch(route("ai-settings.update"), array(
				"ai_provider" => "openai",
			))
			->assertForbidden();
	}

	public function test_pro_user_can_save_ai_provider(): void
	{
		$proPlan = Plan::factory()->pro()->create();
		$proUser = User::factory()->create();
		$proOrg = Organization::factory()->withPlan($proPlan)->create();
		$proOrg->users()->attach($proUser->id, array("role" => "owner"));

		$this->actingAs($proUser)
			->patch(route("ai-settings.update"), array(
				"ai_provider" => "openai",
			))
			->assertRedirect(route("profile.edit"));

		$this->assertDatabaseHas("users", array(
			"id" => $proUser->id,
			"ai_provider" => "openai",
		));
	}
}
