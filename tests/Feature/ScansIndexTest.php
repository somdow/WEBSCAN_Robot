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

class ScansIndexTest extends TestCase
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

	public function test_scans_index_requires_authentication(): void
	{
		$this->get(route("scans.index"))
			->assertRedirect(route("login"));
	}

	public function test_scans_index_page_loads(): void
	{
		$this->actingAs($this->user)
			->get(route("scans.index"))
			->assertOk()
			->assertSee("Scans");
	}

	public function test_scans_index_shows_empty_state(): void
	{
		$this->actingAs($this->user)
			->get(route("scans.index"))
			->assertOk()
			->assertSee("No scans yet");
	}

	public function test_scans_index_lists_scans(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 82,
		));

		$this->actingAs($this->user)
			->get(route("scans.index"))
			->assertOk()
			->assertSee($this->project->name)
			->assertDontSee("No scans yet");
	}

	public function test_scans_index_scoped_to_organization(): void
	{
		$otherUser = User::factory()->create();
		$otherOrg = Organization::factory()->withPlan($this->plan)->create();
		$otherOrg->users()->attach($otherUser->id, array("role" => "owner"));
		$otherProject = Project::factory()->create(array("organization_id" => $otherOrg->id));

		Scan::factory()->create(array(
			"project_id" => $otherProject->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 90,
		));

		$this->actingAs($this->user)
			->get(route("scans.index"))
			->assertOk()
			->assertDontSee($otherProject->name);
	}
}
