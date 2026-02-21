<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceListTest extends TestCase
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

	public function test_plans_list_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/plans")
			->assertOk();
	}

	public function test_coupons_list_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/coupons")
			->assertOk();
	}

	public function test_users_list_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/users")
			->assertOk();
	}

	public function test_organizations_list_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/organizations")
			->assertOk();
	}

	public function test_projects_list_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/projects")
			->assertOk();
	}

	public function test_scans_list_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/scans")
			->assertOk();
	}
}
