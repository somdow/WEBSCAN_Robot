<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create("scan_pages", function (Blueprint $table) {
			$table->id();
			$table->foreignId("scan_id")->constrained()->cascadeOnDelete();
			$table->string("url", 2048);
			$table->unsignedTinyInteger("page_score")->nullable();
			$table->unsignedSmallInteger("http_status_code")->nullable();
			$table->string("content_type", 100)->nullable();
			$table->boolean("is_homepage")->default(false);
			$table->unsignedTinyInteger("crawl_depth")->default(0);
			$table->unsignedInteger("scan_duration_ms")->nullable();
			$table->text("error_message")->nullable();
			$table->timestamps();

			$table->index(array("scan_id", "is_homepage"));
			$table->index(array("scan_id", "crawl_depth"));
		});
	}

	public function down(): void
	{
		Schema::dropIfExists("scan_pages");
	}
};
