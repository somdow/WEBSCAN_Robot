<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("projects", function (Blueprint $table) {
			$table->json("target_keywords")->nullable()->after("scan_schedule");
		});
	}

	public function down(): void
	{
		Schema::table("projects", function (Blueprint $table) {
			$table->dropColumn("target_keywords");
		});
	}
};
