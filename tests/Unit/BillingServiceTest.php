<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\SubscriptionUsage;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
	use RefreshDatabase;

	private BillingService $billingService;
	private Organization $organization;
	private Plan $freePlan;
	private Plan $proPlan;
	private Plan $agencyPlan;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seed(\Database\Seeders\PlanSeeder::class);

		$this->billingService = new BillingService();

		$this->freePlan = Plan::where("slug", "free")->first();
		$this->proPlan = Plan::where("slug", "pro")->first();
		$this->agencyPlan = Plan::where("slug", "agency")->first();

		$this->organization = Organization::create(array(
			"name" => "Test Org",
			"slug" => "test-org",
			"plan_id" => $this->freePlan->id,
		));

		$owner = User::factory()->create();
		$this->organization->users()->attach($owner->id, array(
			"role" => OrganizationRole::Owner->value,
		));
	}

	/* ── isStripeConfigured ── */

	public function test_stripe_not_configured_without_secret(): void
	{
		config()->set("cashier.secret", "");

		$this->assertFalse($this->billingService->isStripeConfigured());
	}

	public function test_stripe_configured_with_secret(): void
	{
		config()->set("cashier.secret", "sk_test_fake_key_12345");

		$this->assertTrue($this->billingService->isStripeConfigured());
	}

	/* ── canCreateProject ── */

	public function test_free_plan_can_create_first_project(): void
	{
		$this->assertTrue($this->billingService->canCreateProject($this->organization));
	}

	public function test_free_plan_cannot_exceed_project_limit(): void
	{
		Project::create(array(
			"organization_id" => $this->organization->id,
			"name" => "Project 1",
			"url" => "https://example.com",
		));

		$this->assertFalse($this->billingService->canCreateProject($this->organization));
	}

	public function test_pro_plan_allows_up_to_five_projects(): void
	{
		$this->organization->update(array("plan_id" => $this->proPlan->id));

		for ($i = 1; $i <= 4; $i++) {
			Project::create(array(
				"organization_id" => $this->organization->id,
				"name" => "Project {$i}",
				"url" => "https://example{$i}.com",
			));
		}

		$this->assertTrue($this->billingService->canCreateProject($this->organization));
	}

	public function test_pro_plan_blocks_sixth_project(): void
	{
		$this->organization->update(array("plan_id" => $this->proPlan->id));

		for ($i = 1; $i <= 5; $i++) {
			Project::create(array(
				"organization_id" => $this->organization->id,
				"name" => "Project {$i}",
				"url" => "https://example{$i}.com",
			));
		}

		$this->assertFalse($this->billingService->canCreateProject($this->organization));
	}

	/* ── canTriggerScan ── */

	public function test_can_trigger_scan_when_usage_within_limit(): void
	{
		SubscriptionUsage::create(array(
			"organization_id" => $this->organization->id,
			"period_start" => now()->startOfMonth(),
			"period_end" => now()->endOfMonth(),
			"scans_used" => 5,
			"ai_calls_used" => 0,
			"api_calls_used" => 0,
		));

		$this->assertTrue($this->billingService->canTriggerScan($this->organization));
	}

	public function test_cannot_trigger_scan_when_limit_reached(): void
	{
		SubscriptionUsage::create(array(
			"organization_id" => $this->organization->id,
			"period_start" => now()->startOfMonth(),
			"period_end" => now()->endOfMonth(),
			"scans_used" => $this->freePlan->max_scans_per_month,
			"ai_calls_used" => 0,
			"api_calls_used" => 0,
		));

		$this->assertFalse($this->billingService->canTriggerScan($this->organization));
	}

	public function test_pro_plan_has_higher_scan_limit(): void
	{
		$this->organization->update(array("plan_id" => $this->proPlan->id));

		SubscriptionUsage::create(array(
			"organization_id" => $this->organization->id,
			"period_start" => now()->startOfMonth(),
			"period_end" => now()->endOfMonth(),
			"scans_used" => 50,
			"ai_calls_used" => 0,
			"api_calls_used" => 0,
		));

		$this->assertTrue($this->billingService->canTriggerScan($this->organization));
	}

	/* ── resolveCurrentUsage ── */

	public function test_resolve_current_usage_creates_record(): void
	{
		$usage = $this->billingService->resolveCurrentUsage($this->organization);

		$this->assertInstanceOf(SubscriptionUsage::class, $usage);
		$this->assertEquals(0, $usage->scans_used);
		$this->assertEquals($this->organization->id, $usage->organization_id);
	}

	public function test_resolve_current_usage_returns_existing_record(): void
	{
		$existing = SubscriptionUsage::create(array(
			"organization_id" => $this->organization->id,
			"period_start" => now()->startOfMonth(),
			"period_end" => now()->endOfMonth(),
			"scans_used" => 7,
			"ai_calls_used" => 0,
			"api_calls_used" => 0,
		));

		$usage = $this->billingService->resolveCurrentUsage($this->organization);

		$this->assertEquals($existing->id, $usage->id);
		$this->assertEquals(7, $usage->scans_used);
	}

	/* ── downgradeToFree ── */

	public function test_downgrade_to_free_sets_free_plan(): void
	{
		$this->organization->update(array("plan_id" => $this->proPlan->id));

		$this->billingService->downgradeToFree($this->organization);

		$this->organization->refresh();
		$this->assertEquals($this->freePlan->id, $this->organization->plan_id);
	}

	/* ── Organization model helpers ── */

	public function test_organization_is_on_free_plan(): void
	{
		$this->assertTrue($this->organization->isOnFreePlan());
	}

	public function test_organization_not_on_free_plan_when_pro(): void
	{
		$this->organization->update(array("plan_id" => $this->proPlan->id));
		$this->organization->refresh();

		$this->assertFalse($this->organization->isOnFreePlan());
	}

	public function test_organization_can_access_ai_on_pro(): void
	{
		$this->organization->update(array("plan_id" => $this->proPlan->id));
		$this->organization->refresh();

		$this->assertTrue($this->organization->canAccessAi());
	}

	public function test_organization_cannot_access_ai_on_free(): void
	{
		$this->assertFalse($this->organization->canAccessAi());
	}
}
