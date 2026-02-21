<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("organizations", function (Blueprint $table) {
			$table->string("pdf_company_name", 100)->nullable()->after("logo_path");
			$table->string("brand_color", 7)->nullable()->after("pdf_company_name");
		});
	}

	public function down(): void
	{
		Schema::table("organizations", function (Blueprint $table) {
			$table->dropColumn(array("pdf_company_name", "brand_color"));
		});
	}
};
