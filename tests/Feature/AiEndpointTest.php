<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanModuleResult;
use App\Models\SubscriptionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiEndpointTest extends TestCase
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

	public function test_ai_status_returns_json_with_expected_keys(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 75,
		));

		$response = $this->actingAs($this->user)
			->getJson(route("ai.status", $scan));

		$response->assertOk()
			->assertJsonStructure(array("aiAvailable", "optimizedModuleIds", "hasExecutiveSummary"));
	}

	public function test_ai_status_requires_project_access(): void
	{
		$otherUser = User::factory()->create();
		$otherOrg = Organization::factory()->withPlan($this->plan)->create();
		$otherOrg->users()->attach($otherUser->id, array("role" => "owner"));

		$scan = Scan::factory()->create(array("project_id" => $this->project->id));

		$this->actingAs($otherUser)
			->getJson(route("ai.status", $scan))
			->assertForbidden();
	}

	public function test_optimize_module_requires_api_key(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 75,
		));
		$moduleResult = ScanModuleResult::factory()->create(array(
			"scan_id" => $scan->id,
			"module_key" => "titleTag",
		));

		$this->actingAs($this->user)
			->postJson(route("ai.optimize-module", array($scan, $moduleResult)))
			->assertStatus(422);
	}

	public function test_executive_summary_requires_api_key(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"status" => ScanStatus::Completed,
			"overall_score" => 75,
		));

		$this->actingAs($this->user)
			->postJson(route("ai.executive-summary", $scan))
			->assertStatus(422);
	}

	public function test_optimize_module_requires_completed_scan(): void
	{
		$proPlan = Plan::factory()->pro()->create();
		$proOrg = Organization::factory()->withPlan($proPlan)->create();
		$proUser = User::factory()->create();
		$proOrg->users()->attach($proUser->id, array("role" => "owner"));

		$project = Project::factory()->create(array("organization_id" => $proOrg->id));
		$scan = Scan::factory()->running()->create(array("project_id" => $project->id));
		$moduleResult = ScanModuleResult::factory()->create(array("scan_id" => $scan->id));

		$this->actingAs($proUser)
			->postJson(route("ai.optimize-module", array($scan, $moduleResult)))
			->assertStatus(422);
	}

	public function test_ai_requests_are_blocked_when_monthly_ai_limit_is_reached(): void
	{
		config()->set("ai.limits.max_monthly_ai_calls", 1);

		$proPlan = Plan::factory()->pro()->create();
		$proUser = User::factory()->create(array(
			"ai_gemini_key" => "test-key",
		));
		$proOrg = Organization::factory()->withPlan($proPlan)->create();
		$proOrg->users()->attach($proUser->id, array("role" => "owner"));

		$usage = SubscriptionUsage::resolveCurrentPeriod($proOrg);
		$usage->update(array("ai_calls_used" => 1));

		$project = Project::factory()->create(array("organization_id" => $proOrg->id));
		$scan = Scan::factory()->create(array(
			"project_id" => $project->id,
			"status" => ScanStatus::Completed,
		));
		$moduleResult = ScanModuleResult::factory()->create(array(
			"scan_id" => $scan->id,
			"module_key" => "titleTag",
		));

		$this->actingAs($proUser)
			->postJson(route("ai.optimize-module", array($scan, $moduleResult)))
			->assertStatus(429);
	}
}
