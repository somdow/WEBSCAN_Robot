<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
	use RefreshDatabase;

	private function createSuperAdmin(): User
	{
		$plan = Plan::factory()->free()->create();
		$user = User::factory()->superAdmin()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		return $user;
	}

	private function createRegularUser(): User
	{
		$plan = Plan::factory()->free()->create();
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		return $user;
	}

	public function test_super_admin_can_access_admin_dashboard(): void
	{
		$admin = $this->createSuperAdmin();

		$this->actingAs($admin)
			->get("/admin")
			->assertOk();
	}

	public function test_regular_user_cannot_access_admin_panel(): void
	{
		$user = $this->createRegularUser();

		$this->actingAs($user)
			->get("/admin")
			->assertForbidden();
	}

	public function test_guest_is_redirected_to_admin_login(): void
	{
		$this->get("/admin")
			->assertRedirect("/admin/login");
	}

	public function test_admin_login_page_renders(): void
	{
		$this->get("/admin/login")
			->assertOk();
	}
}
