<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->string("fetcher_used", 20)->nullable()->after("detection_method");
		});

		DB::table("scans")->whereNull("fetcher_used")->update(array("fetcher_used" => "guzzle"));
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn("fetcher_used");
		});
	}
};
