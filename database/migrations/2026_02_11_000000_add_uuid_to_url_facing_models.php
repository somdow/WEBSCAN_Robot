<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
	/**
	 * Add uuid columns to models that appear in public-facing URLs.
	 * Keeps integer primary keys for internal foreign key references.
	 */
	public function up(): void
	{
		$tables = array("projects", "scans", "scan_pages", "scan_module_results");

		foreach ($tables as $tableName) {
			Schema::table($tableName, function (Blueprint $table) {
				$table->char("uuid", 36)->after("id")->nullable();
			});

			/** Populate existing rows with ordered UUIDs */
			$rows = DB::table($tableName)->select("id")->get();
			foreach ($rows as $row) {
				DB::table($tableName)
					->where("id", $row->id)
					->update(array("uuid" => (string) Str::orderedUuid()));
			}

			/** Now make it non-nullable and unique */
			Schema::table($tableName, function (Blueprint $table) {
				$table->char("uuid", 36)->nullable(false)->unique()->change();
			});
		}
	}

	public function down(): void
	{
		$tables = array("projects", "scans", "scan_pages", "scan_module_results");

		foreach ($tables as $tableName) {
			Schema::table($tableName, function (Blueprint $table) {
				$table->dropColumn("uuid");
			});
		}
	}
};
