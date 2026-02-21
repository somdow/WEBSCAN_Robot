<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestAuthorizationTest extends TestCase
{
	use RefreshDatabase;

	private Plan $plan;
	private User $owner;
	private Organization $organization;

	protected function setUp(): void
	{
		parent::setUp();

		$this->plan = Plan::factory()->free()->create();
		$this->owner = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($this->plan)->create();
		$this->organization->users()->attach($this->owner->id, array("role" => "owner"));
	}

	public function test_non_owner_cannot_change_plan(): void
	{
		$member = User::factory()->create();
		$this->organization->users()->attach($member->id, array("role" => "member"));

		$targetPlan = Plan::factory()->pro()->create();

		$this->actingAs($member)
			->post(route("billing.change-plan"), array(
				"plan_id" => $targetPlan->id,
				"billing_cycle" => "monthly",
			))
			->assertForbidden();
	}

	public function test_owner_passes_change_plan_authorization(): void
	{
		$targetPlan = Plan::factory()->pro()->create();

		/* Will fail at Stripe level but should pass authorization */
		$response = $this->actingAs($this->owner)
			->post(route("billing.change-plan"), array(
				"plan_id" => $targetPlan->id,
				"billing_cycle" => "monthly",
			));

		/* Should not be 403 — may redirect with error since no Stripe subscription */
		$this->assertNotEquals(403, $response->getStatusCode());
	}

	public function test_non_owner_cannot_checkout(): void
	{
		$member = User::factory()->create();
		$this->organization->users()->attach($member->id, array("role" => "member"));

		$targetPlan = Plan::factory()->pro()->create();

		$this->actingAs($member)
			->post(route("billing.checkout"), array(
				"plan_id" => $targetPlan->id,
				"billing_cycle" => "monthly",
			))
			->assertForbidden();
	}

	public function test_non_member_cannot_update_project(): void
	{
		$outsider = User::factory()->create();
		$outsiderOrg = Organization::factory()->withPlan($this->plan)->create();
		$outsiderOrg->users()->attach($outsider->id, array("role" => "owner"));

		$project = Project::factory()->create(array(
			"organization_id" => $this->organization->id,
		));

		$this->actingAs($outsider)
			->put(route("projects.update", $project), array(
				"name" => "Hacked Project",
				"url" => "https://example.com",
			))
			->assertForbidden();
	}

	public function test_member_can_update_own_project(): void
	{
		$project = Project::factory()->create(array(
			"organization_id" => $this->organization->id,
		));

		$this->actingAs($this->owner)
			->put(route("projects.update", $project), array(
				"name" => "Updated Name",
				"url" => "https://example.com",
			))
			->assertRedirect(route("projects.show", $project));
	}
}
