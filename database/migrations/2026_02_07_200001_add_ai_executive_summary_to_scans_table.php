<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->json("ai_executive_summary")->nullable()->after("is_wordpress");
		});
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn("ai_executive_summary");
		});
	}
};
