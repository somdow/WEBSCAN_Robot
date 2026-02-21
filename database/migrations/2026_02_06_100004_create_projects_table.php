<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("projects", function (Blueprint $table) {
			$table->id();
			$table->foreignId("organization_id")->constrained()->cascadeOnDelete();
			$table->string("name");
			$table->string("url");
			$table->string("scan_schedule", 10)->nullable();
			$table->timestamps();

			$table->index("organization_id");
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("projects");
	}
};
