<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("organizations", function (Blueprint $table) {
			$table->foreignId("original_plan_id")
				->nullable()
				->after("plan_id")
				->constrained("plans")
				->nullOnDelete();
			$table->timestamp("override_expires_at")
				->nullable()
				->after("original_plan_id");
		});
	}

	public function down(): void
	{
		Schema::table("organizations", function (Blueprint $table) {
			$table->dropConstrainedForeignId("original_plan_id");
			$table->dropColumn("override_expires_at");
		});
	}
};
