<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
	protected $model = Coupon::class;

	public function definition(): array
	{
		return array(
			"code" => strtoupper(Str::random(8)),
			"discount_type" => DiscountType::Percent,
			"discount_value" => fake()->randomElement(array(10, 15, 20, 25, 50)),
			"max_redemptions" => fake()->optional(0.7)->numberBetween(10, 500),
			"times_redeemed" => 0,
			"expires_at" => fake()->optional(0.5)->dateTimeBetween("+1 month", "+1 year"),
			"is_active" => true,
		);
	}

	/**
	 * A fixed-amount discount coupon.
	 */
	public function fixedAmount(): static
	{
		return $this->state(fn () => array(
			"discount_type" => DiscountType::Fixed,
			"discount_value" => fake()->randomElement(array(5, 10, 25, 50)),
		));
	}

	/**
	 * An expired coupon.
	 */
	public function expired(): static
	{
		return $this->state(fn () => array(
			"expires_at" => now()->subDay(),
		));
	}

	/**
	 * A fully redeemed coupon.
	 */
	public function fullyRedeemed(): static
	{
		return $this->state(fn () => array(
			"max_redemptions" => 10,
			"times_redeemed" => 10,
		));
	}

	/**
	 * A deactivated coupon.
	 */
	public function inactive(): static
	{
		return $this->state(fn () => array(
			"is_active" => false,
		));
	}
}
