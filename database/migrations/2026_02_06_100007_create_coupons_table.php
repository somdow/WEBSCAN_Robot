<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("coupons", function (Blueprint $table) {
			$table->id();
			$table->string("code")->unique();
			$table->string("stripe_coupon_id")->nullable();
			$table->string("discount_type", 15);
			$table->decimal("discount_value", 8, 2);
			$table->json("applicable_plan_ids")->nullable();
			$table->unsignedInteger("max_redemptions")->nullable();
			$table->unsignedInteger("times_redeemed")->default(0);
			$table->timestamp("expires_at")->nullable();
			$table->boolean("is_active")->default(true);
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("coupons");
	}
};
