<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingActionsTest extends TestCase
{
	use RefreshDatabase;

	private Plan $plan;

	protected function setUp(): void
	{
		parent::setUp();

		$this->plan = Plan::factory()->free()->create();
	}

	public function test_billing_index_loads_for_owner(): void
	{
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$this->actingAs($user)
			->get(route("billing.index"))
			->assertOk();
	}

	public function test_change_plan_requires_owner(): void
	{
		$owner = User::factory()->create();
		$member = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($owner->id, array("role" => "owner"));
		$organization->users()->attach($member->id, array("role" => "member"));

		$proPlan = Plan::factory()->pro()->create();

		$this->actingAs($member)
			->post(route("billing.change-plan"), array(
				"plan_id" => $proPlan->id,
				"billing_cycle" => "monthly",
			))
			->assertForbidden();
	}

	public function test_cancel_requires_owner(): void
	{
		$owner = User::factory()->create();
		$member = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($owner->id, array("role" => "owner"));
		$organization->users()->attach($member->id, array("role" => "member"));

		$this->actingAs($member)
			->post(route("billing.cancel"))
			->assertForbidden();
	}

	public function test_resume_requires_owner(): void
	{
		$owner = User::factory()->create();
		$member = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($owner->id, array("role" => "owner"));
		$organization->users()->attach($member->id, array("role" => "member"));

		$this->actingAs($member)
			->post(route("billing.resume"))
			->assertForbidden();
	}

	public function test_portal_redirects_without_stripe_configured(): void
	{
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($this->plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$this->actingAs($user)
			->get(route("billing.portal"))
			->assertRedirect(route("billing.index"));
	}
}
