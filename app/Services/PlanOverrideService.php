<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;

class PlanOverrideService
{
	/**
	 * Apply an admin plan override and persist an audit log entry.
	 */
	public function applyOverride(Organization $organization, Plan $targetPlan, User $actor, ?string $reason = null): void
	{
		$oldPlanId = $organization->plan_id;
		$oldPlanName = $organization->plan?->name;

		if ((int) $oldPlanId === (int) $targetPlan->id) {
			return;
		}

		$organization->update(array(
			"plan_id" => $targetPlan->id,
		));

		AuditLog::create(array(
			"user_id" => $actor->id,
			"action" => "organization.plan_override",
			"auditable_type" => Organization::class,
			"auditable_id" => $organization->id,
			"old_values" => array(
				"plan_id" => $oldPlanId,
				"plan_name" => $oldPlanName,
			),
			"new_values" => array(
				"plan_id" => $targetPlan->id,
				"plan_name" => $targetPlan->name,
				"reason" => $reason,
			),
			"ip_address" => request()?->ip(),
		));
	}
}

