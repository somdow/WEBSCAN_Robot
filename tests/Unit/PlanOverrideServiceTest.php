<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanOverrideServiceTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_updates_plan_and_writes_audit_log(): void
	{
		$fromPlan = Plan::factory()->free()->create();
		$toPlan = Plan::factory()->pro()->create();
		$organization = Organization::factory()->withPlan($fromPlan)->create();
		$admin = User::factory()->create();

		$service = app(PlanOverrideService::class);
		$service->applyOverride($organization, $toPlan, $admin, "Gifted Pro for partner launch");

		$this->assertSame($toPlan->id, $organization->fresh()->plan_id);

		$this->assertDatabaseHas("audit_logs", array(
			"user_id" => $admin->id,
			"action" => "organization.plan_override",
			"auditable_type" => Organization::class,
			"auditable_id" => $organization->id,
		));
	}

	public function test_it_skips_audit_log_when_plan_is_unchanged(): void
	{
		$plan = Plan::factory()->free()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$admin = User::factory()->create();

		$service = app(PlanOverrideService::class);
		$service->applyOverride($organization, $plan, $admin, "No-op");

		$this->assertDatabaseMissing("audit_logs", array(
			"user_id" => $admin->id,
			"action" => "organization.plan_override",
			"auditable_type" => Organization::class,
			"auditable_id" => $organization->id,
		));
	}
}

