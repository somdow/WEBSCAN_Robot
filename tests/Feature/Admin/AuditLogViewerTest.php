<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
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

	public function test_audit_log_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/audit-log-viewer")
			->assertOk();
	}

	public function test_audit_log_displays_entries(): void
	{
		AuditLog::factory()->create(array(
			"user_id" => $this->admin->id,
			"action" => "created",
			"auditable_type" => "App\\Models\\Project",
			"auditable_id" => 1,
		));

		$this->actingAs($this->admin)
			->get("/admin/audit-log-viewer")
			->assertOk()
			->assertSee("created");
	}

	public function test_audit_log_shows_user_names(): void
	{
		AuditLog::factory()->create(array(
			"user_id" => $this->admin->id,
			"action" => "updated",
		));

		$this->actingAs($this->admin)
			->get("/admin/audit-log-viewer")
			->assertOk()
			->assertSee($this->admin->name);
	}

	public function test_regular_user_cannot_access_audit_log(): void
	{
		$user = User::factory()->create();
		$plan = Plan::first();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));

		$this->actingAs($user)
			->get("/admin/audit-log-viewer")
			->assertForbidden();
	}
}
