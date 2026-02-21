<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrate pages from scan-level to project-level ownership.
 *
 * Pages persist across rescans — they belong to the project, not individual scans.
 * scan_id becomes nullable (tracks "which scan last analyzed this page").
 * discovery_status moves from scans to projects.
 */
return new class extends Migration
{
	public function up(): void
	{
		/* ── scan_pages: add project_id, make scan_id nullable ── */
		Schema::table("scan_pages", function (Blueprint $table) {
			$table->unsignedBigInteger("project_id")->nullable()->after("id");
			$table->unsignedBigInteger("scan_id")->nullable()->change();
		});

		DB::statement("
			UPDATE scan_pages
			SET project_id = (
				SELECT scans.project_id FROM scans WHERE scans.id = scan_pages.scan_id
			)
		");

		/* ── discovered_pages: add project_id, make scan_id nullable ── */
		Schema::table("discovered_pages", function (Blueprint $table) {
			$table->unsignedBigInteger("project_id")->nullable()->after("id");
			$table->unsignedBigInteger("scan_id")->nullable()->change();
		});

		DB::statement("
			UPDATE discovered_pages
			SET project_id = (
				SELECT scans.project_id FROM scans WHERE scans.id = discovered_pages.scan_id
			)
		");

		/* ── projects: add discovery_status ── */
		Schema::table("projects", function (Blueprint $table) {
			$table->string("discovery_status", 15)->nullable()->after("target_keywords");
		});

		/* ── Backfill discovery_status from scans to projects ── */
		DB::statement("
			UPDATE projects
			SET discovery_status = (
				SELECT scans.discovery_status FROM scans
				WHERE scans.project_id = projects.id
				AND scans.discovery_status IS NOT NULL
				ORDER BY scans.created_at DESC
				LIMIT 1
			)
		");

		/* ── scans: drop discovery_status ── */
		Schema::table("scans", function (Blueprint $table) {
			$table->dropColumn("discovery_status");
		});

		/* ── Add indexes for project-level queries ── */
		Schema::table("scan_pages", function (Blueprint $table) {
			$table->index("project_id", "scan_pages_project_id_index");
		});

		Schema::table("discovered_pages", function (Blueprint $table) {
			$table->index("project_id", "discovered_pages_project_id_index");
		});
	}

	public function down(): void
	{
		/* ── Restore discovery_status on scans ── */
		Schema::table("scans", function (Blueprint $table) {
			$table->string("discovery_status", 15)->nullable()->after("homepage_screenshot_path");
		});

		/* ── Backfill discovery_status from projects back to latest scan ── */
		DB::statement("
			UPDATE scans
			SET discovery_status = (
				SELECT projects.discovery_status FROM projects
				WHERE projects.id = scans.project_id
			)
			WHERE id IN (
				SELECT MAX(id) FROM scans GROUP BY project_id
			)
		");

		/* ── Drop project columns ── */
		Schema::table("projects", function (Blueprint $table) {
			$table->dropColumn("discovery_status");
		});

		Schema::table("discovered_pages", function (Blueprint $table) {
			$table->dropIndex("discovered_pages_project_id_index");
			$table->dropColumn("project_id");
		});

		Schema::table("scan_pages", function (Blueprint $table) {
			$table->dropIndex("scan_pages_project_id_index");
			$table->dropColumn("project_id");
		});
	}
};
