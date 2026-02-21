<?php

use App\Enums\OrganizationRole;
use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
	public function up(): void
	{
		$defaultPlanId = Plan::query()->where("slug", "free")->value("id")
			?? Plan::query()->orderBy("id")->value("id");

		if ($defaultPlanId === null) {
			return;
		}

		DB::table("organizations")
			->whereNull("plan_id")
			->update(array(
				"plan_id" => $defaultPlanId,
				"updated_at" => now(),
			));

		$usersWithoutOrganization = DB::table("users")
			->leftJoin("organization_user", "users.id", "=", "organization_user.user_id")
			->whereNull("organization_user.id")
			->select("users.id", "users.name")
			->get();

		foreach ($usersWithoutOrganization as $user) {
			$baseSlug = Str::slug($user->name ?: "user");
			if ($baseSlug === "") {
				$baseSlug = "user";
			}

			$slug = $baseSlug;
			while (DB::table("organizations")->where("slug", $slug)->exists()) {
				$slug = $baseSlug . "-" . Str::lower(Str::random(4));
			}

			$organizationId = DB::table("organizations")->insertGetId(array(
				"name" => trim(($user->name ?: "My") . "'s Organization"),
				"slug" => $slug,
				"plan_id" => $defaultPlanId,
				"created_at" => now(),
				"updated_at" => now(),
			));

			DB::table("organization_user")->insert(array(
				"organization_id" => $organizationId,
				"user_id" => $user->id,
				"role" => OrganizationRole::Owner->value,
				"created_at" => now(),
				"updated_at" => now(),
			));
		}
	}

	public function down(): void
	{
		//
	}
};

