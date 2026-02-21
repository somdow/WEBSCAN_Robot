<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("discovered_pages", function (Blueprint $table) {
			$table->id();
			$table->foreignId("scan_id")->constrained()->cascadeOnDelete();
			$table->string("url", 2048);
			$table->unsignedTinyInteger("crawl_depth")->default(0);
			$table->boolean("is_analyzed")->default(false);
			$table->timestamps();

			$table->index("scan_id");
		});

		Schema::table("scans", function (Blueprint $table) {
			$table->string("discovery_status", 15)->nullable()->after("homepage_screenshot_path");
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("discovered_pages");

		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn("discovery_status");
		});
	}
};
