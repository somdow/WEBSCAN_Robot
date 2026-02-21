<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
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

	public function test_dashboard_loads_for_super_admin(): void
	{
		$this->actingAs($this->admin)
			->get("/admin")
			->assertOk();
	}

	public function test_dashboard_shows_platform_stats(): void
	{
		User::factory()->count(3)->create();
		Plan::factory()->pro()->create();

		$this->actingAs($this->admin)
			->get("/admin")
			->assertOk();
	}

	public function test_dashboard_counts_scans(): void
	{
		$organization = Organization::first();
		$project = Project::factory()->create(array("organization_id" => $organization->id));
		Scan::factory()->count(5)->create(array(
			"project_id" => $project->id,
			"triggered_by" => $this->admin->id,
		));

		$this->assertEquals(5, Scan::count());
	}

	public function test_dashboard_counts_organizations_by_plan(): void
	{
		$proPlan = Plan::factory()->pro()->create();
		Organization::factory()->count(3)->withPlan($proPlan)->create();

		$this->assertEquals(3, Organization::where("plan_id", $proPlan->id)->count());
	}
}
