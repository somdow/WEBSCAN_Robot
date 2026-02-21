<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table("organizations", function (Blueprint $table) {
			$table->foreignId("plan_id")
				->nullable()
				->after("logo_path")
				->constrained("plans")
				->nullOnDelete();
		});

		/* Assign all existing organizations to the Free plan */
		$freePlan = Plan::where("slug", "free")->first();

		if ($freePlan) {
			DB::table("organizations")
				->whereNull("plan_id")
				->update(array("plan_id" => $freePlan->id));
		}
	}

	public function down(): void
	{
		Schema::table("organizations", function (Blueprint $table) {
			$table->dropForeign(array("plan_id"));
			$table->dropColumn("plan_id");
		});
	}
};
