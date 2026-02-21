<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make scan_id nullable on scan_pages and discovered_pages.
 * Pages now belong to projects — scan_id tracks "which scan last analyzed this page"
 * and may be null for newly added pages awaiting their first analysis.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scan_pages", function (Blueprint $table) {
			$table->unsignedBigInteger("scan_id")->nullable()->change();
		});

		Schema::table("discovered_pages", function (Blueprint $table) {
			$table->unsignedBigInteger("scan_id")->nullable()->change();
		});
	}

	public function down(): void
	{
		Schema::table("scan_pages", function (Blueprint $table) {
			$table->unsignedBigInteger("scan_id")->nullable(false)->change();
		});

		Schema::table("discovered_pages", function (Blueprint $table) {
			$table->unsignedBigInteger("scan_id")->nullable(false)->change();
		});
	}
};
