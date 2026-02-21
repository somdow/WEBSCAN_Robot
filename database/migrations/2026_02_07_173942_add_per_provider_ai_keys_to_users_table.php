<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("users", function (Blueprint $table) {
			$table->text("ai_gemini_key")->nullable()->after("ai_provider");
			$table->text("ai_openai_key")->nullable()->after("ai_gemini_key");
			$table->text("ai_anthropic_key")->nullable()->after("ai_openai_key");
		});
	}

	public function down(): void
	{
		Schema::table("users", function (Blueprint $table) {
			$table->dropColumn(array("ai_gemini_key", "ai_openai_key", "ai_anthropic_key"));
		});
	}
};
