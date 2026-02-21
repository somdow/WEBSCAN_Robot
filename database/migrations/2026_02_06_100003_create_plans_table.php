<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("plans", function (Blueprint $table) {
			$table->id();
			$table->string("name");
			$table->string("slug")->unique();
			$table->text("description")->nullable();
			$table->string("stripe_monthly_price_id")->nullable();
			$table->string("stripe_annual_price_id")->nullable();
			$table->decimal("price_monthly", 8, 2);
			$table->decimal("price_annual", 8, 2)->nullable();
			$table->unsignedInteger("max_users");
			$table->unsignedInteger("max_projects");
			$table->unsignedInteger("max_scans_per_month");
			$table->unsignedInteger("max_competitors")->default(0);
			$table->unsignedInteger("scan_history_days")->default(7);
			$table->tinyInteger("ai_tier")->default(1);
			$table->json("feature_flags")->nullable();
			$table->boolean("is_public")->default(true);
			$table->unsignedSmallInteger("sort_order")->default(0);
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("plans");
	}
};
