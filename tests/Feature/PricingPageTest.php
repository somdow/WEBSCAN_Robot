<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
	use RefreshDatabase;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seed(\Database\Seeders\PlanSeeder::class);
	}

	public function test_pricing_page_loads_for_guests(): void
	{
		$response = $this->get("/pricing");

		$response->assertOk();
		$response->assertSeeText("Free");
		$response->assertSeeText("Pro");
		$response->assertSeeText("Agency");
	}

	public function test_pricing_page_shows_all_public_plans(): void
	{
		$response = $this->get("/pricing");

		$response->assertOk();
		$response->assertSeeText("$0");
		$response->assertSeeText("$49");
		$response->assertSeeText("$149");
	}

	public function test_pricing_page_shows_annual_pricing(): void
	{
		$response = $this->get("/pricing");

		$response->assertOk();
		$response->assertSeeText("Save 20%");
		$response->assertSeeText("billed annually");
	}

	public function test_pricing_page_loads_for_authenticated_users(): void
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)->get("/pricing");

		$response->assertOk();
		$response->assertSeeText("Pro");
	}

	public function test_pricing_page_shows_plan_features(): void
	{
		$response = $this->get("/pricing");

		$response->assertOk();
		$response->assertSeeText("scans/month");
		$response->assertSeeText("project(s)");
		$response->assertSeeText("user(s)");
	}

	public function test_pricing_page_shows_enterprise_cta(): void
	{
		$response = $this->get("/pricing");

		$response->assertOk();
		$response->assertSeeText("Contact us for Enterprise pricing");
	}

	public function test_pricing_page_shows_most_popular_badge_on_pro(): void
	{
		$response = $this->get("/pricing");

		$response->assertOk();
		$response->assertSeeText("Most Popular");
	}
}
