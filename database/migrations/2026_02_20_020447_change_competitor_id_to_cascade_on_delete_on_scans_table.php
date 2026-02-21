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
		   These have no matching competitor record but are clearly not real project scans. */
		DB::table("scans")
			->whereNull("competitor_id")
			->whereIn("id", function ($query) {
				/* Find scans that share exact scores with a known competitor scan for the same project,
				   but are NOT the project's original scans (they appeared after competitor scans started). */
				$query->select("s1.id")
					->from("scans as s1")
					->join("scans as s2", function ($join) {
						$join->on("s1.project_id", "=", "s2.project_id")
							->whereNotNull("s2.competitor_id")
							->on("s1.overall_score", "=", "s2.overall_score")
							->on("s1.seo_score", "=", "s2.seo_score")
							->on("s1.health_score", "=", "s2.health_score");
					})
					->whereNull("s1.competitor_id")
					->where("s1.id", ">", DB::raw("s2.id - 5"));
			})
			->delete();

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
