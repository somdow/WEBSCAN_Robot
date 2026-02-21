<?php

namespace Tests\Feature;

use App\Enums\CreditState;
use App\Enums\ScanStatus;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\SubscriptionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanUsageTrackingTest extends TestCase
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

	public function test_scan_credit_is_claimed_at_trigger_time(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->post(route("scans.store", $this->project))
			->assertRedirect();

		$usage = SubscriptionUsage::resolveCurrentPeriod($this->organization);
		$this->assertEquals(1, $usage->scans_used);
		$this->assertDatabaseHas("scans", array(
			"project_id" => $this->project->id,
			"credit_state" => CreditState::Claimed->value,
			"status" => ScanStatus::Pending->value,
		));
	}

	public function test_usage_record_created_when_claiming_credit_on_trigger(): void
	{
		$this->assertDatabaseMissing("subscription_usage", array(
			"organization_id" => $this->organization->id,
		));

		Queue::fake();

		$this->actingAs($this->user)
			->post(route("scans.store", $this->project))
			->assertRedirect();

		$this->assertDatabaseHas("subscription_usage", array(
			"organization_id" => $this->organization->id,
			"scans_used" => 1,
		));
	}

	public function test_can_add_member_checks_plan_limit(): void
	{
		$billingService = app(\App\Services\BillingService::class);

		/* Free plan max_users = 1, org already has 1 user */
		$this->assertFalse($billingService->canAddMember($this->organization));

		/* Create a plan with higher limit */
		$proPlan = Plan::factory()->pro()->create();
		$this->organization->update(array("plan_id" => $proPlan->id));
		$this->organization->refresh();

		$this->assertTrue($billingService->canAddMember($this->organization));
	}
}
