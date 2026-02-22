<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\PlanOverrideService;
use Illuminate\Console\Command;

class ExpireOverridesCommand extends Command
{
	protected $signature = "overrides:expire";

	protected $description = "Auto-revert expired plan overrides to their original plans";

	public function handle(PlanOverrideService $overrideService): int
	{
		$expiredOrganizations = Organization::query()
			->whereNotNull("original_plan_id")
			->whereNotNull("override_expires_at")
			->where("override_expires_at", "<=", now())
			->with(array("plan", "originalPlan"))
			->get();

		if ($expiredOrganizations->isEmpty()) {
			$this->info("No expired overrides found.");
			return self::SUCCESS;
		}

		foreach ($expiredOrganizations as $organization) {
			$overrideService->removeOverride(
				$organization,
				null,
				"Auto-expired: override duration elapsed",
				true,
			);

			$restoredPlanName = $organization->originalPlan?->name ?? "original plan";
			$this->info("Restored {$organization->name} to {$restoredPlanName}.");
		}

		$this->info("Processed {$expiredOrganizations->count()} expired override(s).");

		return self::SUCCESS;
	}
}
