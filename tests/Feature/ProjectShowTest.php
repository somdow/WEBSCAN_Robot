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

class ProjectShowTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;
	private Project $project;

	protected function setUp(): void
	{
		parent::setUp();

		$plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
		$this->project = Project::factory()->create(array("organization_id" => $this->organization->id));
	}

	public function test_project_show_page_loads(): void
	{
		$this->actingAs($this->user)
			->get(route("projects.show", $this->project))
			->assertOk()
			->assertSee($this->project->name);
	}

	public function test_score_trend_chart_shown_with_multiple_scans(): void
	{
		Scan::factory()->count(3)->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 75,
		));

		$this->actingAs($this->user)
			->get(route("projects.show", $this->project))
			->assertOk()
			->assertSee("Score Trend");
	}

	public function test_score_trend_nudge_with_single_scan(): void
	{
		Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 80,
		));

		$this->actingAs($this->user)
			->get(route("projects.show", $this->project))
			->assertOk()
			->assertSee("Score Trend")
			->assertSee("Run another scan to see your score trend");
	}

	public function test_score_trend_chart_hidden_with_no_scans(): void
	{
		$this->actingAs($this->user)
			->get(route("projects.show", $this->project))
			->assertOk()
			->assertDontSee("Score Trend")
			->assertDontSee("Run another scan");
	}
}
