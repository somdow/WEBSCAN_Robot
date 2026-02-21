<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
	public function run(): void
	{
		$this->createSuperAdmin();
		$this->createFreeUser();
	}

	/**
	 * Super admin with full Agency plan access.
	 */
	private function createSuperAdmin(): void
	{
		$agencyPlan = Plan::where("slug", "agency")->first();

		$admin = User::firstOrCreate(
			array("email" => "somdow@gmail.com"),
			array(
				"name" => "Super Admin",
				"password" => Hash::make("pass"),
				"email_verified_at" => now(),
			)
		);

		if (!$admin->is_super_admin) {
			$admin->is_super_admin = true;
			$admin->save();
		}

		if ($admin->organizations()->count() === 0) {
			$organization = Organization::firstOrCreate(
				array("slug" => "hello-web-scans"),
				array(
					"name" => "HELLO WEB_SCANS",
					"plan_id" => $agencyPlan?->id,
				)
			);

			$organization->users()->syncWithoutDetaching(array(
				$admin->id => array("role" => OrganizationRole::Owner->value),
			));
		}
	}

	/**
	 * Free-tier test account for verifying locked features.
	 */
	private function createFreeUser(): void
	{
		$freePlan = Plan::where("slug", "free")->first();

		$freeUser = User::firstOrCreate(
			array("email" => "freeaccount@gmail.com"),
			array(
				"name" => "Free User",
				"password" => Hash::make("password"),
				"email_verified_at" => now(),
			)
		);

		if ($freeUser->organizations()->count() === 0) {
			$organization = Organization::firstOrCreate(
				array("slug" => "free-user-org"),
				array(
					"name" => "Free User Org",
					"plan_id" => $freePlan?->id,
				)
			);

			$organization->users()->syncWithoutDetaching(array(
				$freeUser->id => array("role" => OrganizationRole::Owner->value),
			));
		}
	}
}
