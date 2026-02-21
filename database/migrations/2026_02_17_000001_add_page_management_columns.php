<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scan_pages", function (Blueprint $table) {
			$table->string("source", 20)->default("scan")->after("error_message");
			$table->string("analysis_status", 15)->default("completed")->after("source");
		});

		Schema::table("plans", function (Blueprint $table) {
			$table->unsignedSmallInteger("max_additional_pages")->default(0)->after("max_pages_per_scan");
		});
	}

	public function down(): void
	{
		Schema::table("scan_pages", function (Blueprint $table) {
			$table->dropColumn(array("source", "analysis_status"));
		});

		Schema::table("plans", function (Blueprint $table) {
			$table->dropColumn("max_additional_pages");
		});
	}
};
