<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("scan_module_results", function (Blueprint $table) {
			$table->id();
			$table->foreignId("scan_id")->constrained()->cascadeOnDelete();
			$table->string("module_key", 50);
			$table->string("status", 10)->default("info");
			$table->json("findings");
			$table->json("recommendations");
			$table->text("ai_summary")->nullable();
			$table->text("ai_suggestion")->nullable();
			$table->timestamps();

			$table->index(["scan_id", "module_key"]);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("scan_module_results");
	}
};
