<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrganizationProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationProvisioningServiceTest extends TestCase
{
	use RefreshDatabase;

	public function test_it_creates_org_with_free_plan_for_user_without_org(): void
	{
		$freePlan = Plan::factory()->free()->create();
		$user = User::factory()->create();

		$organization = app(OrganizationProvisioningService::class)->ensureForUser($user);

		$this->assertNotNull($organization);
		$this->assertSame($freePlan->id, $organization->plan_id);

		$this->assertDatabaseHas("organization_user", array(
			"organization_id" => $organization->id,
			"user_id" => $user->id,
			"role" => OrganizationRole::Owner->value,
		));
	}

	public function test_it_backfills_missing_plan_on_existing_org(): void
	{
		$freePlan = Plan::factory()->free()->create();
		$user = User::factory()->create();
		$organization = \App\Models\Organization::factory()->create(array(
			"plan_id" => null,
		));
		$organization->users()->attach($user->id, array("role" => OrganizationRole::Owner->value));

		$resolved = app(OrganizationProvisioningService::class)->ensureForUser($user);

		$this->assertSame($organization->id, $resolved->id);
		$this->assertSame($freePlan->id, $resolved->plan_id);
	}
}

