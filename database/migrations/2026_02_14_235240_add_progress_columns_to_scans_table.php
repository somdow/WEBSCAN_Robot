<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->unsignedTinyInteger("progress_percent")->nullable()->default(0)->after("status");
			$table->string("progress_label", 255)->nullable()->after("progress_percent");
		});
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn(array("progress_percent", "progress_label"));
		});
	}
};
