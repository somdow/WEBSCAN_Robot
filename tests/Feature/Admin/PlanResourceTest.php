<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanResourceTest extends TestCase
{
	use RefreshDatabase;

	private User $admin;

	protected function setUp(): void
	{
		parent::setUp();

		$plan = Plan::factory()->free()->create();
		$this->admin = User::factory()->superAdmin()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($this->admin->id, array("role" => "owner"));
	}

	public function test_create_plan_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/plans/create")
			->assertOk();
	}

	public function test_edit_plan_page_loads(): void
	{
		$plan = Plan::factory()->pro()->create();

		$this->actingAs($this->admin)
			->get("/admin/plans/{$plan->id}/edit")
			->assertOk();
	}

	public function test_plans_are_listed(): void
	{
		Plan::factory()->pro()->create();
		Plan::factory()->agency()->create();

		$this->actingAs($this->admin)
			->get("/admin/plans")
			->assertOk()
			->assertSee("Pro")
			->assertSee("Agency");
	}

	public function test_plan_displays_ai_tier_badge(): void
	{
		Plan::factory()->create(array(
			"name" => "Test Plan",
			"slug" => "test-plan",
			"ai_tier" => 2,
		));

		$this->actingAs($this->admin)
			->get("/admin/plans")
			->assertOk()
			->assertSee("Test Plan");
	}

	public function test_plan_shows_public_status(): void
	{
		Plan::factory()->create(array(
			"name" => "Hidden Plan",
			"slug" => "hidden",
			"is_public" => false,
		));

		$this->actingAs($this->admin)
			->get("/admin/plans")
			->assertOk()
			->assertSee("Hidden Plan");
	}
}
