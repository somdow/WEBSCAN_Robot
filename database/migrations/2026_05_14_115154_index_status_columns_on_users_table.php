<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index the two status columns used by the admin users page tabs
 * (Unverified / Active / Deactivated). Each tab renders a COUNT()
 * badge per page load, so without these indexes the queries are
 * full table scans at scale.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table("users", function (Blueprint $table) {
			$table->index("email_verified_at");
			$table->index("deactivated_at");
		});
	}

	public function down(): void
	{
		Schema::table("users", function (Blueprint $table) {
			$table->dropIndex(array("email_verified_at"));
			$table->dropIndex(array("deactivated_at"));
		});
	}
};
