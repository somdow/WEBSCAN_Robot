<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;
	private Plan $plan;

	protected function setUp(): void
	{
		parent::setUp();

		$this->plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($this->plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
	}

	public function test_projects_index_requires_authentication(): void
	{
		$this->get(route("projects.index"))
			->assertRedirect(route("login"));
	}

	public function test_projects_index_lists_user_projects(): void
	{
		$project = Project::factory()->create(array("organization_id" => $this->organization->id));

		$this->actingAs($this->user)
			->get(route("projects.index"))
			->assertOk()
			->assertSee($project->name);
	}

	public function test_projects_index_scoped_to_organization(): void
	{
		$otherUser = User::factory()->create();
		$otherOrg = Organization::factory()->withPlan($this->plan)->create();
		$otherOrg->users()->attach($otherUser->id, array("role" => "owner"));
		$otherProject = Project::factory()->create(array("organization_id" => $otherOrg->id));

		$this->actingAs($this->user)
			->get(route("projects.index"))
			->assertOk()
			->assertDontSee($otherProject->name);
	}

	public function test_create_page_loads(): void
	{
		$this->actingAs($this->user)
			->get(route("projects.create"))
			->assertOk()
			->assertSee("New Project");
	}

	public function test_store_creates_project_and_redirects(): void
	{
		$this->actingAs($this->user)
			->post(route("projects.store"), array(
				"name" => "Test Project",
				"url" => "https://example.com",
				"target_keywords" => "seo, testing",
			))
			->assertRedirect();

		$this->assertDatabaseHas("projects", array(
			"name" => "Test Project",
			"url" => "https://example.com",
			"organization_id" => $this->organization->id,
		));
	}

	public function test_store_validates_required_fields(): void
	{
		$this->actingAs($this->user)
			->post(route("projects.store"), array())
			->assertSessionHasErrors(array("name", "url"));
	}

	public function test_update_modifies_project(): void
	{
		$project = Project::factory()->create(array("organization_id" => $this->organization->id));

		$this->actingAs($this->user)
			->put(route("projects.update", $project), array(
				"name" => "Updated Name",
				"url" => "https://updated.com",
			))
			->assertRedirect();

		$this->assertDatabaseHas("projects", array(
			"id" => $project->id,
			"name" => "Updated Name",
		));
	}

	public function test_destroy_deletes_project(): void
	{
		$project = Project::factory()->create(array("organization_id" => $this->organization->id));

		$this->actingAs($this->user)
			->delete(route("projects.destroy", $project))
			->assertRedirect();

		$this->assertDatabaseMissing("projects", array("id" => $project->id));
	}
}
