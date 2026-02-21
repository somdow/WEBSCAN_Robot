<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("scans", function (Blueprint $table) {
			$table->id();
			$table->foreignId("project_id")->constrained()->cascadeOnDelete();
			$table->foreignId("triggered_by")->nullable()->constrained("users")->nullOnDelete();
			$table->string("status", 15)->default("pending");
			$table->unsignedTinyInteger("overall_score")->nullable();
			$table->unsignedInteger("scan_duration_ms")->nullable();
			$table->boolean("is_wordpress")->default(false);
			$table->timestamps();

			$table->index(["project_id", "created_at"]);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("scans");
	}
};
