<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->string("detection_method", 20)->nullable()->after("is_wordpress");
		});
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn("detection_method");
		});
	}
};
