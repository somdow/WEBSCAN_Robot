<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponResourceTest extends TestCase
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

	public function test_create_coupon_page_loads(): void
	{
		$this->actingAs($this->admin)
			->get("/admin/coupons/create")
			->assertOk();
	}

	public function test_edit_coupon_page_loads(): void
	{
		$coupon = Coupon::factory()->create();

		$this->actingAs($this->admin)
			->get("/admin/coupons/{$coupon->id}/edit")
			->assertOk();
	}

	public function test_coupons_are_listed(): void
	{
		Coupon::factory()->create(array("code" => "SAVE20"));
		Coupon::factory()->create(array("code" => "HALFOFF"));

		$this->actingAs($this->admin)
			->get("/admin/coupons")
			->assertOk()
			->assertSee("SAVE20")
			->assertSee("HALFOFF");
	}

	public function test_expired_coupon_displays(): void
	{
		Coupon::factory()->expired()->create(array("code" => "EXPIRED99"));

		$this->actingAs($this->admin)
			->get("/admin/coupons")
			->assertOk()
			->assertSee("EXPIRED99");
	}
}
