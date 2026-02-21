<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("users", function (Blueprint $table) {
			$table->timestamp("deactivated_at")->nullable()->after("is_super_admin");
		});

		Schema::table("organizations", function (Blueprint $table) {
			$table->timestamp("deactivated_at")->nullable()->after("plan_id");
		});
	}

	public function down(): void
	{
		Schema::table("users", function (Blueprint $table) {
			$table->dropColumn("deactivated_at");
		});

		Schema::table("organizations", function (Blueprint $table) {
			$table->dropColumn("deactivated_at");
		});
	}
};
