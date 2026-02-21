<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\SubscriptionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlanEnforcementTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;
	private Plan $freePlan;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seed(\Database\Seeders\PlanSeeder::class);

		$this->freePlan = Plan::where("slug", "free")->first();

		$this->organization = Organization::create(array(
			"name" => "Test Org",
			"slug" => "test-org",
			"plan_id" => $this->freePlan->id,
		));

		$this->user = User::factory()->create();
		$this->organization->users()->attach($this->user->id, array(
			"role" => OrganizationRole::Owner->value,
		));
	}

	public function test_user_can_create_project_within_limit(): void
	{
		$response = $this->actingAs($this->user)->post("/projects", array(
			"name" => "First Project",
			"url" => "https://example.com",
		));

		$response->assertRedirect();
		$response->assertSessionMissing("error");
		$this->assertDatabaseHas("projects", array(
			"organization_id" => $this->organization->id,
			"name" => "First Project",
		));
	}

	public function test_user_cannot_create_project_beyond_limit(): void
	{
		Project::create(array(
			"organization_id" => $this->organization->id,
			"name" => "Existing Project",
			"url" => "https://existing.com",
		));

		$response = $this->actingAs($this->user)
			->from("/projects/create")
			->post("/projects", array(
				"name" => "Second Project",
				"url" => "https://second.com",
			));

		$response->assertRedirect("/projects/create");
		$response->assertSessionHas("error");
		$this->assertDatabaseMissing("projects", array("name" => "Second Project"));
	}

	public function test_pro_plan_allows_more_projects(): void
	{
		$proPlan = Plan::where("slug", "pro")->first();
		$this->organization->update(array("plan_id" => $proPlan->id));

		Project::create(array(
			"organization_id" => $this->organization->id,
			"name" => "Project 1",
			"url" => "https://one.com",
		));

		$response = $this->actingAs($this->user)->post("/projects", array(
			"name" => "Project 2",
			"url" => "https://two.com",
		));

		$response->assertRedirect();
		$response->assertSessionMissing("error");
		$this->assertDatabaseHas("projects", array("name" => "Project 2"));
	}

	public function test_scan_limit_blocks_when_exceeded(): void
	{
		$project = Project::create(array(
			"organization_id" => $this->organization->id,
			"name" => "Test Project",
			"url" => "https://test.com",
		));

		SubscriptionUsage::create(array(
			"organization_id" => $this->organization->id,
			"period_start" => now()->startOfMonth(),
			"period_end" => now()->endOfMonth(),
			"scans_used" => $this->freePlan->max_scans_per_month,
			"ai_calls_used" => 0,
			"api_calls_used" => 0,
		));

		$response = $this->actingAs($this->user)
			->from(route("projects.show", $project))
			->post(route("scans.store", $project));

		$response->assertRedirect(route("projects.show", $project));
		$response->assertSessionHas("error");
	}

	public function test_scan_allowed_when_within_limit(): void
	{
		Queue::fake();

		$project = Project::create(array(
			"organization_id" => $this->organization->id,
			"name" => "Test Project",
			"url" => "https://test.com",
		));

		SubscriptionUsage::create(array(
			"organization_id" => $this->organization->id,
			"period_start" => now()->startOfMonth(),
			"period_end" => now()->endOfMonth(),
			"scans_used" => 0,
			"ai_calls_used" => 0,
			"api_calls_used" => 0,
		));

		$response = $this->actingAs($this->user)
			->post(route("scans.store", $project));

		/* Middleware allowed through — the scan was created and job dispatched.
		   The redirect goes to scans.show (success), not back with an error. */
		$response->assertRedirect();
		$response->assertSessionMissing("error");
		$this->assertDatabaseHas("scans", array("project_id" => $project->id));
	}
}
