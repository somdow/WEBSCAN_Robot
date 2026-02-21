<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Jobs\ProcessPageAnalysisJob;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AddPageTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;
	private Project $project;
	private Scan $scan;

	protected function setUp(): void
	{
		parent::setUp();

		$plan = Plan::factory()->pro()->create(array("max_additional_pages" => 10));
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
		$this->project = Project::factory()->create(array(
			"organization_id" => $this->organization->id,
			"url" => "https://example.com",
		));
		$this->scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
			"status" => ScanStatus::Completed,
		));

		ScanPage::factory()->homepage()->create(array(
			"project_id" => $this->project->id,
			"scan_id" => $this->scan->id,
			"url" => "https://example.com",
		));
	}

	public function test_add_page_creates_scan_page_and_dispatches_job(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $this->project), array(
				"url" => "https://example.com/about",
			))
			->assertStatus(201)
			->assertJson(array("success" => true));

		$this->assertDatabaseHas("scan_pages", array(
			"project_id" => $this->project->id,
			"url" => "https://example.com/about",
			"source" => "manual",
			"analysis_status" => "pending",
		));

		Queue::assertPushed(ProcessPageAnalysisJob::class);
	}

	public function test_add_page_rejects_cross_domain_url(): void
	{
		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $this->project), array(
				"url" => "https://evil.com/malicious",
			))
			->assertStatus(422)
			->assertJsonFragment(array("error" => "URL must belong to the same domain as the project (example.com)."));
	}

	public function test_add_page_rejects_duplicate_url(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $this->project), array(
				"url" => "https://example.com",
			))
			->assertStatus(422)
			->assertJsonFragment(array("error" => "This page has already been added to this project."));
	}

	public function test_add_page_rejected_for_free_plan(): void
	{
		$freePlan = Plan::factory()->free()->create(array("max_additional_pages" => 0));
		$this->organization->update(array("plan_id" => $freePlan->id));

		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $this->project), array(
				"url" => "https://example.com/about",
			))
			->assertStatus(403);
	}

	public function test_add_page_rejected_without_completed_scan(): void
	{
		$emptyProject = Project::factory()->create(array(
			"organization_id" => $this->organization->id,
			"url" => "https://other.com",
		));

		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $emptyProject), array(
				"url" => "https://other.com/about",
			))
			->assertStatus(422)
			->assertJsonFragment(array("error" => "Run a homepage scan first before adding pages."));
	}

	public function test_add_page_enforces_plan_limit(): void
	{
		$currentPlan = $this->organization->plan;
		$currentPlan->update(array("max_additional_pages" => 1));

		Queue::fake();

		ScanPage::factory()->create(array(
			"project_id" => $this->project->id,
			"scan_id" => $this->scan->id,
			"url" => "https://example.com/first",
			"source" => "manual",
		));

		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $this->project), array(
				"url" => "https://example.com/second",
			))
			->assertStatus(403)
			->assertJsonPath("error", fn($error) => str_contains($error, "plan limit"));
	}

	public function test_add_page_validates_url_format(): void
	{
		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $this->project), array(
				"url" => "not-a-valid-url",
			))
			->assertStatus(422)
			->assertJsonValidationErrors("url");
	}

	public function test_page_progress_returns_status(): void
	{
		$page = ScanPage::factory()->create(array(
			"project_id" => $this->project->id,
			"scan_id" => $this->scan->id,
			"source" => "manual",
			"analysis_status" => "running",
		));

		$this->actingAs($this->user)
			->getJson(route("project-pages.progress", array($this->project, $page)))
			->assertOk()
			->assertJson(array("analysis_status" => "running"));
	}

	public function test_page_progress_requires_authorization(): void
	{
		$page = ScanPage::factory()->create(array(
			"project_id" => $this->project->id,
			"scan_id" => $this->scan->id,
			"source" => "manual",
		));

		$otherUser = User::factory()->create();
		$otherOrg = \App\Models\Organization::factory()->create();
		$otherOrg->users()->attach($otherUser, array("role" => "owner"));
		$otherUser->update(array("current_organization_id" => $otherOrg->id));

		$this->actingAs($otherUser)
			->getJson(route("project-pages.progress", array($this->project, $page)))
			->assertForbidden();
	}

	public function test_add_page_allows_subdomain(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->postJson(route("project-pages.store", $this->project), array(
				"url" => "https://blog.example.com/post/1",
			))
			->assertStatus(201);
	}

	public function test_rescan_page_resets_and_dispatches_job(): void
	{
		Queue::fake();

		$page = ScanPage::factory()->create(array(
			"project_id" => $this->project->id,
			"scan_id" => $this->scan->id,
			"url" => "https://example.com/rescan-me",
			"source" => "manual",
			"analysis_status" => "completed",
			"page_score" => 75,
		));

		$this->actingAs($this->user)
			->postJson(route("project-pages.rescan", array($this->project, $page)))
			->assertOk()
			->assertJson(array("success" => true, "analysis_status" => "pending"));

		$this->assertDatabaseHas("scan_pages", array(
			"id" => $page->id,
			"analysis_status" => "pending",
			"page_score" => null,
		));

		Queue::assertPushed(ProcessPageAnalysisJob::class);
	}
}
