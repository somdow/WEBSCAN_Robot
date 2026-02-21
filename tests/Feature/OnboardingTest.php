<?php

namespace Tests\Feature;

use App\Jobs\ProcessScanJob;
use App\Models\Organization;
use App\Models\Plan;
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
}
