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

class ScanResultsTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;
	private Project $project;
	private Plan $plan;

	protected function setUp(): void
	{
		parent::setUp();

		$this->plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($this->plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
		$this->project = Project::factory()->create(array("organization_id" => $this->organization->id));
	}

	public function test_scan_show_requires_authentication(): void
	{
		$scan = Scan::factory()->create(array("project_id" => $this->project->id));

		$this->get(route("scans.show", $scan))
			->assertRedirect(route("login"));
	}

	public function test_scan_show_redirects_to_project_page(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 78,
		));

		$this->actingAs($this->user)
			->get(route("scans.show", $scan))
			->assertRedirect(route("projects.show", array("project" => $this->project, "scan" => $scan)));
	}

	public function test_scan_show_requires_project_access(): void
	{
		$otherUser = User::factory()->create();
		$otherOrg = Organization::factory()->withPlan($this->plan)->create();
		$otherOrg->users()->attach($otherUser->id, array("role" => "owner"));

		$scan = Scan::factory()->create(array("project_id" => $this->project->id));

		$this->actingAs($otherUser)
			->get(route("scans.show", $scan))
			->assertForbidden();
	}

	public function test_project_show_with_scan_param_displays_historical_scan(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 85,
		));

		$this->actingAs($this->user)
			->get(route("projects.show", array("project" => $this->project, "scan" => $scan)))
			->assertOk()
			->assertSee("85");
	}
}
