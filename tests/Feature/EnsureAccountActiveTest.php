<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureAccountActiveTest extends TestCase
{
	use RefreshDatabase;

	private Plan $plan;

	protected function setUp(): void
	{
		parent::setUp();

		$this->plan = Plan::factory()->free()->create();
	}

	public function test_active_user_can_access_dashboard(): void
	{
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$this->actingAs($user)
			->get(route("dashboard"))
			->assertOk();
	}

	public function test_deactivated_user_is_logged_out_and_redirected(): void
	{
		$user = User::factory()->create(array("deactivated_at" => now()));
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$response = $this->actingAs($user)
			->get(route("dashboard"));

		$response->assertRedirect(route("login"));
		$response->assertSessionHas("error");
		$this->assertGuest();
	}

	public function test_deactivated_organization_blocks_access(): void
	{
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create(array(
			"deactivated_at" => now(),
		));
		$organization->users()->attach($user->id, array("role" => "owner"));

		$response = $this->actingAs($user)
			->get(route("dashboard"));

		$response->assertRedirect(route("login"));
		$response->assertSessionHas("error");
		$this->assertGuest();
	}

	public function test_deactivated_user_cannot_access_projects(): void
	{
		$user = User::factory()->create(array("deactivated_at" => now()));
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$response = $this->actingAs($user)
			->get(route("projects.index"));

		$response->assertRedirect(route("login"));
		$this->assertGuest();
	}

	public function test_deactivated_user_cannot_access_billing(): void
	{
		$user = User::factory()->create(array("deactivated_at" => now()));
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$response = $this->actingAs($user)
			->get(route("billing.index"));

		$response->assertRedirect(route("login"));
		$this->assertGuest();
	}

	public function test_deactivated_org_user_cannot_access_profile(): void
	{
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create(array(
			"deactivated_at" => now(),
		));
		$organization->users()->attach($user->id, array("role" => "owner"));

		$response = $this->actingAs($user)
			->get(route("profile.edit"));

		$response->assertRedirect(route("login"));
		$this->assertGuest();
	}
}
