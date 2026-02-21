<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->string("scan_type", 10)->default("single")->after("status");
			$table->unsignedSmallInteger("pages_crawled")->default(1)->after("scan_duration_ms");
			$table->unsignedSmallInteger("max_pages_requested")->default(1)->after("pages_crawled");
			$table->unsignedTinyInteger("crawl_depth_limit")->default(0)->after("max_pages_requested");
		});

		Schema::table("plans", function (Blueprint $table) {
			$table->unsignedSmallInteger("max_pages_per_scan")->default(1)->after("max_scans_per_month");
		});
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn(array("scan_type", "pages_crawled", "max_pages_requested", "crawl_depth_limit"));
		});

		Schema::table("plans", function (Blueprint $table) {
			$table->dropColumn("max_pages_per_scan");
		});
	}
};
