<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("subscription_usage", function (Blueprint $table) {
			$table->id();
			$table->foreignId("organization_id")->constrained()->cascadeOnDelete();
			$table->date("period_start");
			$table->date("period_end");
			$table->unsignedInteger("scans_used")->default(0);
			$table->unsignedInteger("ai_calls_used")->default(0);
			$table->unsignedInteger("api_calls_used")->default(0);
			$table->timestamps();

			$table->index(["organization_id", "period_start", "period_end"]);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("subscription_usage");
	}
};
