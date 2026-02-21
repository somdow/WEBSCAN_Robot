<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeactivationTest extends TestCase
{
	use RefreshDatabase;

	private Plan $freePlan;
	private User $user;
	private Organization $organization;

	protected function setUp(): void
	{
		parent::setUp();

		$this->freePlan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($this->freePlan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
	}

	public function test_profile_destroy_deactivates_user_instead_of_deleting(): void
	{
		$this->actingAs($this->user)
			->delete(route("profile.destroy"), array(
				"password" => "password",
			));

		$this->user->refresh();
		$this->assertNotNull($this->user->deactivated_at);
		$this->assertDatabaseHas("users", array("id" => $this->user->id));
	}

	public function test_profile_destroy_deactivates_organization(): void
	{
		$this->actingAs($this->user)
			->delete(route("profile.destroy"), array(
				"password" => "password",
			));

		$this->organization->refresh();
		$this->assertNotNull($this->organization->deactivated_at);
	}

	public function test_profile_destroy_logs_out_user(): void
	{
		$this->actingAs($this->user)
			->delete(route("profile.destroy"), array(
				"password" => "password",
			));

		$this->assertGuest();
	}

	public function test_profile_destroy_redirects_to_home(): void
	{
		$response = $this->actingAs($this->user)
			->delete(route("profile.destroy"), array(
				"password" => "password",
			));

		$response->assertRedirect("/");
	}

	public function test_profile_destroy_requires_correct_password(): void
	{
		$this->actingAs($this->user)
			->delete(route("profile.destroy"), array(
				"password" => "wrong-password",
			));

		$this->user->refresh();
		$this->assertNull($this->user->deactivated_at);
	}

	public function test_deactivated_user_sees_reactivation_page_on_login(): void
	{
		$this->user->deactivate();
		$this->organization->deactivate();

		$response = $this->post(route("login"), array(
			"email" => $this->user->email,
			"password" => "password",
		));

		$response->assertRedirect(route("reactivate.show"));
		$this->assertGuest();
	}

	public function test_reactivation_page_loads_with_session(): void
	{
		$response = $this->withSession(array("reactivate_user_id" => $this->user->id))
			->get(route("reactivate.show"));

		$response->assertOk();
		$response->assertSee("Account Deactivated");
	}

	public function test_reactivation_page_redirects_without_session(): void
	{
		$this->get(route("reactivate.show"))
			->assertRedirect(route("login"));
	}

	public function test_reactivation_clears_deactivated_at(): void
	{
		$this->user->deactivate();
		$this->organization->deactivate();

		$this->withSession(array("reactivate_user_id" => $this->user->id))
			->post(route("reactivate.store"));

		$this->user->refresh();
		$this->organization->refresh();

		$this->assertNull($this->user->deactivated_at);
		$this->assertNull($this->organization->deactivated_at);
	}

	public function test_reactivation_downgrades_to_free_plan(): void
	{
		$proPlan = Plan::factory()->pro()->create();
		$this->organization->update(array("plan_id" => $proPlan->id));
		$this->user->deactivate();
		$this->organization->deactivate();

		$this->withSession(array("reactivate_user_id" => $this->user->id))
			->post(route("reactivate.store"));

		$this->organization->refresh();
		$this->assertEquals($this->freePlan->id, $this->organization->plan_id);
	}

	public function test_reactivation_logs_user_in(): void
	{
		$this->user->deactivate();
		$this->organization->deactivate();

		$this->withSession(array("reactivate_user_id" => $this->user->id))
			->post(route("reactivate.store"));

		$this->assertAuthenticatedAs($this->user);
	}

	public function test_reactivation_redirects_to_dashboard(): void
	{
		$this->user->deactivate();
		$this->organization->deactivate();

		$response = $this->withSession(array("reactivate_user_id" => $this->user->id))
			->post(route("reactivate.store"));

		$response->assertRedirect(route("dashboard"));
	}

	public function test_setting_model_get_and_set(): void
	{
		Setting::setValue("support_email", "test@example.com");
		$this->assertEquals("test@example.com", Setting::getValue("support_email"));
	}

	public function test_setting_model_returns_default_when_missing(): void
	{
		$this->assertEquals("fallback@test.com", Setting::getValue("nonexistent_key", "fallback@test.com"));
	}

	public function test_legal_pages_use_dynamic_support_email(): void
	{
		Setting::setValue("support_email", "custom@helloseo.com");

		$this->get(route("legal.privacy"))->assertSee("custom@helloseo.com");
		$this->get(route("legal.terms"))->assertSee("custom@helloseo.com");
		$this->get(route("legal.acceptable-use"))->assertSee("custom@helloseo.com");
	}
}
