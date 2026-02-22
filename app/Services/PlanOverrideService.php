<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterval;

class PlanOverrideService
{
	/**
	 * Apply an admin plan override, preserving the original plan for later restoration.
	 * If an override is already active, the original_plan_id is preserved (true original).
	 */
	public function applyOverride(
		Organization $organization,
		Plan $targetPlan,
		User $actor,
		?string $reason = null,
		?CarbonInterval $duration = null,
	): void {
		$oldPlanId = $organization->plan_id;
		$oldPlanName = $organization->plan?->name;

		if ((int) $oldPlanId === (int) $targetPlan->id) {
			return;
		}

		$overrideExpiresAt = $duration !== null ? now()->add($duration) : null;

		/* Preserve the true original plan — don't overwrite if already overridden */
		$originalPlanId = $organization->hasActiveOverride()
			? $organization->original_plan_id
			: $oldPlanId;

		$organization->update(array(
			"plan_id" => $targetPlan->id,
			"original_plan_id" => $originalPlanId,
			"override_expires_at" => $overrideExpiresAt,
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
				"expires_at" => $overrideExpiresAt?->toIso8601String(),
			),
			"ip_address" => request()?->ip(),
		));
	}

	/**
	 * Remove an active override and restore the organization to its original plan.
	 */
	public function removeOverride(Organization $organization, ?User $actor, ?string $reason = null, bool $autoExpired = false): void
	{
		if (!$organization->hasActiveOverride()) {
			return;
		}

		$overriddenPlanId = $organization->plan_id;
		$overriddenPlanName = $organization->plan?->name;
		$originalPlanId = $organization->original_plan_id;
		$originalPlanName = $organization->originalPlan?->name;

		$organization->update(array(
			"plan_id" => $originalPlanId,
			"original_plan_id" => null,
			"override_expires_at" => null,
		));

		AuditLog::create(array(
			"user_id" => $actor?->id,
			"action" => "organization.plan_override_removed",
			"auditable_type" => Organization::class,
			"auditable_id" => $organization->id,
			"old_values" => array(
				"plan_id" => $overriddenPlanId,
				"plan_name" => $overriddenPlanName,
			),
			"new_values" => array(
				"plan_id" => $originalPlanId,
				"plan_name" => $originalPlanName,
				"reason" => $reason,
				"auto_expired" => $autoExpired,
			),
			"ip_address" => request()?->ip(),
		));
	}
}
