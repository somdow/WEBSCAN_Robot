<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEnhancementTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;

	protected function setUp(): void
	{
		parent::setUp();

		$plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
	}

	public function test_dashboard_shows_empty_state_for_new_user(): void
	{
		$this->actingAs($this->user)
			->get(route("dashboard"))
			->assertOk()
			->assertSee("No scans yet")
			->assertSee("Getting Started");
	}

	public function test_dashboard_shows_recent_scans(): void
	{
		$project = Project::factory()->create(array("organization_id" => $this->organization->id));
		Scan::factory()->create(array(
			"project_id" => $project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 78,
		));

		$this->actingAs($this->user)
			->get(route("dashboard"))
			->assertOk()
			->assertSee($project->name)
			->assertSee("Recent Scans");
	}

	public function test_dashboard_hides_getting_started_when_complete(): void
	{
		$project = Project::factory()->create(array("organization_id" => $this->organization->id));
		Scan::factory()->create(array(
			"project_id" => $project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 85,
		));

		$this->actingAs($this->user)
			->get(route("dashboard"))
			->assertOk()
			->assertDontSee("Getting Started");
	}
}
