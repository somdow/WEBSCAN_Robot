<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		$hasInsecureTransportUsed = Schema::hasColumn("scans", "insecure_transport_used");
		$hasScanCreditState = Schema::hasColumn("scans", "credit_state");
		$hasScanPageCreditState = Schema::hasColumn("scan_pages", "credit_state");

		Schema::table("scans", function (Blueprint $table) use ($hasInsecureTransportUsed, $hasScanCreditState) {
			if (!$hasInsecureTransportUsed) {
				$table->boolean("insecure_transport_used")->default(false)->after("fetcher_used");
			}

			if (!$hasScanCreditState) {
				$table->string("credit_state", 15)->default("unclaimed")->after("insecure_transport_used");
			}
		});

		Schema::table("scan_pages", function (Blueprint $table) use ($hasScanPageCreditState) {
			if (!$hasScanPageCreditState) {
				$table->string("credit_state", 15)->default("unclaimed")->after("analysis_status");
			}
		});

		DB::statement("
			UPDATE scan_pages
			SET project_id = (
				SELECT scans.project_id FROM scans WHERE scans.id = scan_pages.scan_id
			)
			WHERE project_id IS NULL AND scan_id IS NOT NULL
		");

		DB::statement("
			UPDATE discovered_pages
			SET project_id = (
				SELECT scans.project_id FROM scans WHERE scans.id = discovered_pages.scan_id
			)
			WHERE project_id IS NULL AND scan_id IS NOT NULL
		");

		$driver = DB::getDriverName();

		if ($driver !== "sqlite") {
			Schema::table("scan_pages", function (Blueprint $table) {
				$table->unsignedBigInteger("project_id")->nullable(false)->change();
				$table->foreign("project_id", "scan_pages_project_id_foreign")->references("id")->on("projects")->cascadeOnDelete();
			});

			Schema::table("discovered_pages", function (Blueprint $table) {
				$table->unsignedBigInteger("project_id")->nullable(false)->change();
				$table->foreign("project_id", "discovered_pages_project_id_foreign")->references("id")->on("projects")->cascadeOnDelete();
			});
		}
	}

	public function down(): void
	{
		$hasScanPageCreditState = Schema::hasColumn("scan_pages", "credit_state");
		$hasScanCreditState = Schema::hasColumn("scans", "credit_state");
		$hasInsecureTransportUsed = Schema::hasColumn("scans", "insecure_transport_used");
		$driver = DB::getDriverName();

		if ($driver !== "sqlite") {
			Schema::table("discovered_pages", function (Blueprint $table) {
				$table->dropForeign("discovered_pages_project_id_foreign");
				$table->unsignedBigInteger("project_id")->nullable()->change();
			});

			Schema::table("scan_pages", function (Blueprint $table) {
				$table->dropForeign("scan_pages_project_id_foreign");
				$table->unsignedBigInteger("project_id")->nullable()->change();
			});
		}

		Schema::table("scan_pages", function (Blueprint $table) use ($hasScanPageCreditState) {
			if ($hasScanPageCreditState) {
				$table->dropColumn("credit_state");
			}
		});

		Schema::table("scans", function (Blueprint $table) use ($hasScanCreditState, $hasInsecureTransportUsed) {
			if ($hasScanCreditState) {
				$table->dropColumn("credit_state");
			}

			if ($hasInsecureTransportUsed) {
				$table->dropColumn("insecure_transport_used");
			}
		});
	}
};
