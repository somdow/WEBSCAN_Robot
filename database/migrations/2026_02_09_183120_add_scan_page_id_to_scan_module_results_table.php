<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasColumn("scan_module_results", "scan_page_id")) {
			return;
		}

		Schema::table("scan_module_results", function (Blueprint $table) {
			$table->foreignId("scan_page_id")->nullable()->after("scan_id")->constrained("scan_pages")->nullOnDelete();
			$table->index(array("scan_page_id", "module_key"));
		});
	}

	public function down(): void
	{
		Schema::table("scan_module_results", function (Blueprint $table) {
			$table->dropForeign(array("scan_page_id"));
			$table->dropIndex(array("scan_page_id", "module_key"));
			$table->dropColumn("scan_page_id");
		});
	}
};
