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
	/**
	 * MySQL refuses to drop an index that backs a foreign key. Rather than
	 * relying on MySQL's auto-rebind behavior when a replacement covering
	 * index appears in the same ALTER TABLE, we drop the FK explicitly,
	 * swap the index, then re-create the FK to point at the new constraint.
	 * Original FK from create_scan_module_results_table:
	 *     $table->foreignId("scan_id")->constrained()->cascadeOnDelete();
	 */
	public function up(): void
	{
		Schema::table("scan_module_results", function (Blueprint $table) {
			$table->dropForeign(array("scan_id"));
			$table->dropIndex(array("scan_id", "module_key"));
			$table->unique(array("scan_id", "scan_page_id", "module_key"), "scan_module_results_unique");
			$table->foreign("scan_id")
				->references("id")
				->on("scans")
				->cascadeOnDelete();
		});
	}

	public function down(): void
	{
		Schema::table("scan_module_results", function (Blueprint $table) {
			$table->dropForeign(array("scan_id"));
			$table->dropUnique("scan_module_results_unique");
			$table->index(array("scan_id", "module_key"));
			$table->foreign("scan_id")
				->references("id")
				->on("scans")
				->cascadeOnDelete();
		});
	}
};
