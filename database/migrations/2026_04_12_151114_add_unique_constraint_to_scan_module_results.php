<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Replace the non-unique index with a unique constraint to prevent
	 * duplicate module results per scan + page combination.
	 */
	public function up(): void
	{
		Schema::table("scan_module_results", function (Blueprint $table) {
			$table->dropIndex(["scan_id", "module_key"]);
			$table->unique(["scan_id", "scan_page_id", "module_key"], "scan_module_results_unique");
		});
	}

	public function down(): void
	{
		Schema::table("scan_module_results", function (Blueprint $table) {
			$table->dropUnique("scan_module_results_unique");
			$table->index(["scan_id", "module_key"]);
		});
	}
};
