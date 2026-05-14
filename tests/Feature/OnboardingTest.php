<?php

namespace Tests\Feature;

use App\Jobs\ProcessScanJob;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\SubscriptionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;

	protected function setUp(): void
	{
		parent::setUp();

		$plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
	}

	public function test_onboarding_creates_project_and_returns_json(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->postJson(route("onboarding.store"), array(
				"name" => "My First Project",
				"url" => "https://example.com",
				"trigger_scan" => false,
			))
			->assertOk()
			->assertJson(array("success" => true));

		$this->assertDatabaseHas("projects", array(
			"name" => "My First Project",
			"url" => "https://example.com",
			"organization_id" => $this->organization->id,
		));
	}

	public function test_onboarding_validates_required_fields(): void
	{
		$this->actingAs($this->user)
			->postJson(route("onboarding.store"), array())
			->assertUnprocessable()
			->assertJsonValidationErrors(array("name", "url"));
	}

	public function test_onboarding_triggers_scan_when_requested(): void
	{
		Queue::fake();

		$this->actingAs($this->user)
			->postJson(route("onboarding.store"), array(
				"name" => "Scan Project",
				"url" => "https://example.com",
				"trigger_scan" => true,
			))
			->assertOk();

		Queue::assertPushed(ProcessScanJob::class);
	}

	public function test_onboarding_creates_project_but_skips_scan_when_credits_exhausted(): void
	{
		Queue::fake();

		/* Fill the organization's monthly scan quota so claimScanCredit fails. */
		$usage = SubscriptionUsage::resolveCurrentPeriod($this->organization);
		$usage->incrementScans($this->organization->plan->max_scans_per_month);

		$response = $this->actingAs($this->user)
			->postJson(route("onboarding.store"), array(
				"name" => "Quota Project",
				"url" => "https://example.com",
				"trigger_scan" => true,
			))
			->assertOk()
			->assertJson(array("success" => true));

		$this->assertDatabaseHas("projects", array(
			"name" => "Quota Project",
			"url" => "https://example.com",
			"organization_id" => $this->organization->id,
		));

		Queue::assertNotPushed(ProcessScanJob::class);

		/* When credits are exhausted, redirect should point at the project view
		   without a scan param (i.e., no &scan= in the URL). */
		$this->assertDoesNotMatchRegularExpression(
			"/[?&]scan=/",
			$response->json("redirect"),
		);
	}
}
