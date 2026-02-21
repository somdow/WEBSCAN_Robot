<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("competitors", function (Blueprint $table) {
			$table->id();
			$table->char("uuid", 36)->unique();
			$table->foreignId("project_id")->constrained()->cascadeOnDelete();
			$table->string("url", 2048);
			$table->string("name", 255)->nullable();
			$table->foreignId("latest_scan_id")->nullable()->constrained("scans")->nullOnDelete();
			$table->timestamps();

			$table->unique(array("project_id", "url"));
			$table->index("project_id");
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("competitors");
	}
};
