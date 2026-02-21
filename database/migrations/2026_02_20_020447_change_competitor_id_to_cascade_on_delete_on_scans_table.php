<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		/* Clean up orphaned ghost scans — competitor scans whose competitor was deleted,
		   leaving competitor_id = NULL and polluting project scan history.
		   MySQL doesn't allow DELETE with subquery on same table, so collect IDs first. */
		$orphanIds = DB::table("scans as s1")
			->join("scans as s2", function ($join) {
				$join->on("s1.project_id", "=", "s2.project_id")
					->whereNotNull("s2.competitor_id")
					->on("s1.overall_score", "=", "s2.overall_score")
					->on("s1.seo_score", "=", "s2.seo_score")
					->on("s1.health_score", "=", "s2.health_score");
			})
			->whereNull("s1.competitor_id")
			->where("s1.id", ">", DB::raw("s2.id - 5"))
			->pluck("s1.id")
			->unique()
			->values()
			->all();

		if (count($orphanIds) > 0) {
			DB::table("scans")->whereIn("id", $orphanIds)->delete();
		}

		/* Switch FK from nullOnDelete to cascadeOnDelete */
		Schema::table("scans", function (Blueprint $table) {
			$table->dropForeign(array("competitor_id"));
		});

		Schema::table("scans", function (Blueprint $table) {
			$table->foreign("competitor_id")
				->references("id")
				->on("competitors")
				->cascadeOnDelete();
		});
	}

	public function down(): void
	{
		Schema::table("scans", function (Blueprint $table) {
			$table->dropForeign(array("competitor_id"));
		});

		Schema::table("scans", function (Blueprint $table) {
			$table->foreign("competitor_id")
				->references("id")
				->on("competitors")
				->nullOnDelete();
		});
	}
};
