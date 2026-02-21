<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->unsignedTinyInteger("seo_score")->nullable()->after("overall_score");
			$table->unsignedTinyInteger("health_score")->nullable()->after("seo_score");
		});
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn(array("seo_score", "health_score"));
		});
	}
};
