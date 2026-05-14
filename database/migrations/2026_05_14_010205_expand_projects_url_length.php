<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Match the projects.url column to the 2048-char validation rule used by
 * StoreProjectRequest, OnboardingController, and the landing-page register
 * prefill flow. Previously the column defaulted to VARCHAR(255), so valid
 * but long URLs would pass form validation and then silently fail on insert.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table("projects", function (Blueprint $table) {
			$table->string("url", 2048)->change();
		});
	}

	public function down(): void
	{
		Schema::table("projects", function (Blueprint $table) {
			$table->string("url")->change();
		});
	}
};
