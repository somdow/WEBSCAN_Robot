<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag projects that were created during a landing-page signup so the
 * post-email-verification handler can dispatch their first scan against the
 * RIGHT project — not any random scanless project the org happens to have.
 * Without this marker, an invited user joining an existing org could trigger
 * a scan on someone else's pending project.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table("projects", function (Blueprint $table) {
			$table->boolean("auto_scan_pending")->default(false)->index();
		});
	}

	public function down(): void
	{
		Schema::table("projects", function (Blueprint $table) {
			$table->dropIndex(array("auto_scan_pending"));
			$table->dropColumn("auto_scan_pending");
		});
	}
};
