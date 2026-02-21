<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
	use RefreshDatabase;

	private User $ownerUser;
	private User $memberUser;
	private Organization $organization;
	private Plan $freePlan;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seed(\Database\Seeders\PlanSeeder::class);

		$this->freePlan = Plan::where("slug", "free")->first();

		$this->organization = Organization::create(array(
			"name" => "Test Org",
			"slug" => "test-org",
			"plan_id" => $this->freePlan->id,
		));

		$this->ownerUser = User::factory()->create();
		$this->organization->users()->attach($this->ownerUser->id, array(
			"role" => OrganizationRole::Owner->value,
		));

		$this->memberUser = User::factory()->create();
		$this->organization->users()->attach($this->memberUser->id, array(
			"role" => OrganizationRole::Member->value,
		));
	}

	public function test_billing_page_loads_for_owner(): void
	{
		$response = $this->actingAs($this->ownerUser)->get("/billing");

		$response->assertOk();
		$response->assertSee("Billing & Subscription", escape: false);
		$response->assertSeeText("Free");
	}

	public function test_billing_page_loads_for_member(): void
	{
		$response = $this->actingAs($this->memberUser)->get("/billing");

		$response->assertOk();
		$response->assertSeeText("Current Plan");
	}

	public function test_billing_page_redirects_guests_to_login(): void
	{
		$response = $this->get("/billing");

		$response->assertRedirect("/login");
	}

	public function test_billing_page_shows_current_plan_details(): void
	{
		$response = $this->actingAs($this->ownerUser)->get("/billing");

		$response->assertOk();
		$response->assertSeeText("Free");
		$response->assertSeeText("Scans this month");
		$response->assertSeeText("Projects");
	}

	public function test_billing_page_shows_upgrade_options_for_owner(): void
	{
		$response = $this->actingAs($this->ownerUser)->get("/billing");

		$response->assertOk();
		$response->assertSeeText("Change Plan");
	}

	public function test_billing_page_hides_upgrade_options_for_member(): void
	{
		$response = $this->actingAs($this->memberUser)->get("/billing");

		$response->assertOk();
		$response->assertDontSeeText("Change Plan");
	}

	public function test_billing_checkout_requires_owner(): void
	{
		$proPlan = Plan::where("slug", "pro")->first();

		$response = $this->actingAs($this->memberUser)->post("/billing/checkout", array(
			"plan_id" => $proPlan->id,
			"billing_cycle" => "monthly",
		));

		$response->assertForbidden();
	}

	public function test_billing_cancel_requires_owner(): void
	{
		$response = $this->actingAs($this->memberUser)->post("/billing/cancel");

		$response->assertForbidden();
	}

	public function test_billing_success_page_loads(): void
	{
		$response = $this->actingAs($this->ownerUser)->get("/billing/success");

		$response->assertOk();
		$response->assertSeeText("Subscription Confirmed");
	}

	public function test_checkout_returns_error_when_stripe_not_configured(): void
	{
		$proPlan = Plan::where("slug", "pro")->first();

		$response = $this->actingAs($this->ownerUser)->post("/billing/checkout", array(
			"plan_id" => $proPlan->id,
			"billing_cycle" => "monthly",
		));

		$response->assertRedirect(route("billing.index"));
		$response->assertSessionHas("error", "Stripe is not configured yet. Please add your Stripe API keys to the .env file.");
	}

	public function test_checkout_validates_required_fields(): void
	{
		$response = $this->actingAs($this->ownerUser)->post("/billing/checkout", array());

		$response->assertSessionHasErrors(array("plan_id", "billing_cycle"));
	}

	public function test_checkout_rejects_free_plan(): void
	{
		$response = $this->actingAs($this->ownerUser)->post("/billing/checkout", array(
			"plan_id" => $this->freePlan->id,
			"billing_cycle" => "monthly",
		));

		$response->assertSessionHasErrors(array("plan_id"));
	}
}
