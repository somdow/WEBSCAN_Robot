<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Jobs\ProcessScanJob;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanTriggerTest extends TestCase
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

	public function test_scan_trigger_requires_authentication(): void
	{
		$this->post(route("scans.store", $this->project))
			->assertRedirect(route("login"));
	}

	public function test_scan_trigger_creates_pending_scan(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->post(route("scans.store", $this->project))
			->assertRedirect();

		$this->assertDatabaseHas("scans", array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
			"status" => ScanStatus::Pending->value,
		));
	}

	public function test_scan_trigger_dispatches_job(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->post(route("scans.store", $this->project));

		Queue::assertPushed(ProcessScanJob::class, function ($job) {
			return $job->scan->project_id === $this->project->id;
		});
	}

	public function test_scan_trigger_redirects_to_project_show(): void
	{
		Queue::fake();

		$response = $this->actingAs($this->user)
			->post(route("scans.store", $this->project));

		$response->assertRedirect(route("projects.show", $this->project));
	}

	public function test_scan_trigger_requires_project_access(): void
	{
		$otherUser = User::factory()->create();
		$otherOrg = Organization::factory()->withPlan($this->plan)->create();
		$otherOrg->users()->attach($otherUser->id, array("role" => "owner"));

		$this->actingAs($otherUser)
			->post(route("scans.store", $this->project))
			->assertForbidden();
	}
}
