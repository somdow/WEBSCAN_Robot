<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;

class OrganizationProvisioningService
{
	/**
	 * Ensure a user has a current organization with a valid plan.
	 */
	public function ensureForUser(User $user): Organization
	{
		$organization = $user->currentOrganization();

		if ($organization !== null) {
			if ($organization->plan_id === null) {
				$organization->update(array(
					"plan_id" => $this->resolveDefaultPlanId(),
				));
			}

			return $organization->fresh();
		}

		$organizationName = trim(($user->name ?: "My") . "'s Organization");
		$baseSlug = Str::slug($user->name ?: "user");
		if ($baseSlug === "") {
			$baseSlug = "user";
		}

		$organization = Organization::create(array(
			"name" => $organizationName,
			"slug" => $this->buildUniqueOrganizationSlug($baseSlug),
			"plan_id" => $this->resolveDefaultPlanId(),
		));

		$organization->users()->syncWithoutDetaching(array(
			$user->id => array("role" => OrganizationRole::Owner->value),
		));

		/* Bust the in-memory org cache so subsequent calls to currentOrganization()
		   see the newly created org instead of returning stale null. */
		$user->resetOrganizationCache();

		return $organization;
	}

	/**
	 * Resolve the default plan ID (prefer free plan).
	 */
	public function resolveDefaultPlanId(): int
	{
		$freePlanId = Plan::query()
			->where("slug", "free")
			->value("id");

		if ($freePlanId !== null) {
			return (int) $freePlanId;
		}

		$firstPlanId = Plan::query()->orderBy("id")->value("id");

		if ($firstPlanId !== null) {
			return (int) $firstPlanId;
		}

		$fallbackFreePlan = Plan::query()->create(array(
			"name" => "Free",
			"slug" => "free",
			"description" => "Auto-generated fallback free plan.",
			"price_monthly" => 0,
			"price_annual" => 0,
			"max_users" => 1,
			"max_projects" => 1,
			"max_scans_per_month" => 5,
			"max_pages_per_scan" => 1,
			"max_additional_pages" => 0,
			"max_crawl_depth" => 3,
			"max_competitors" => 0,
			"scan_history_days" => 7,
			"ai_tier" => 0,
			"feature_flags" => array(),
			"is_public" => true,
			"sort_order" => 0,
		));

		return (int) $fallbackFreePlan->id;
	}

	/**
	 * Build a unique organization slug.
	 */
	private function buildUniqueOrganizationSlug(string $baseSlug): string
	{
		$slug = $baseSlug;

		while (Organization::where("slug", $slug)->exists()) {
			$slug = $baseSlug . "-" . Str::lower(Str::random(4));
		}

		return $slug;
	}
}
