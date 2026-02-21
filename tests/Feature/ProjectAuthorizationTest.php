<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAuthorizationTest extends TestCase
{
	use RefreshDatabase;

	private User $owner;
	private User $outsider;
	private Organization $organization;
	private Project $project;
	private Plan $plan;

	protected function setUp(): void
	{
		parent::setUp();

		$this->plan = Plan::factory()->free()->create();

		$this->owner = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($this->plan)->create();
		$this->organization->users()->attach($this->owner->id, array("role" => "owner"));
		$this->project = Project::factory()->create(array("organization_id" => $this->organization->id));

		$this->outsider = User::factory()->create();
		$outsiderOrg = Organization::factory()->withPlan($this->plan)->create();
		$outsiderOrg->users()->attach($this->outsider->id, array("role" => "owner"));
	}

	public function test_cannot_view_other_organizations_project(): void
	{
		$this->actingAs($this->outsider)
			->get(route("projects.show", $this->project))
			->assertForbidden();
	}

	public function test_cannot_edit_other_organizations_project(): void
	{
		$this->actingAs($this->outsider)
			->get(route("projects.edit", $this->project))
			->assertForbidden();
	}

	public function test_cannot_update_other_organizations_project(): void
	{
		$this->actingAs($this->outsider)
			->put(route("projects.update", $this->project), array(
				"name" => "Hacked",
				"url" => "https://hacked.com",
			))
			->assertForbidden();
	}

	public function test_cannot_delete_other_organizations_project(): void
	{
		$this->actingAs($this->outsider)
			->delete(route("projects.destroy", $this->project))
			->assertForbidden();
	}

	public function test_cannot_trigger_scan_on_other_organizations_project(): void
	{
		$this->actingAs($this->outsider)
			->post(route("scans.store", $this->project))
			->assertForbidden();
	}
}
