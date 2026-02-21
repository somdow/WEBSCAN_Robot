<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->foreignId("competitor_id")->nullable()->after("project_id")->constrained("competitors")->nullOnDelete();
			$table->index("competitor_id");
		});
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropForeign(array("competitor_id"));
			$table->dropIndex(array("competitor_id"));
			$table->dropColumn("competitor_id");
		});
	}
};
