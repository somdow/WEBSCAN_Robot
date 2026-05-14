<?php

namespace App\Services\Scanning;

use App\Enums\CreditState;
use App\Enums\ScanStatus;
use App\Jobs\ProcessScanJob;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;

/**
 * Encapsulates the "claim credit → create Scan row → dispatch job" pipeline
 * used by every code path that kicks off a project's first scan from outside
 * the manual rescan flow (onboarding, post-email-verification, etc.).
 *
 * Centralizes the column set (scan_type, max_pages_requested, crawl_depth_limit,
 * credit_state) so each caller does not drift apart over time.
 */
class ScanDispatchService
{
	public function __construct(
		private readonly BillingService $billingService,
	) {}

	/**
	 * Claim a scan credit, persist a Scan row, and dispatch its processing job.
	 *
	 * Returns the persisted Scan on success, or null when the organization has
	 * exhausted its monthly credits. Throwable cases are logged and re-thrown
	 * as null so callers can fall back to a sensible redirect.
	 */
	public function dispatchFirstScan(Project $project, User $user, Organization $organization): ?Scan
	{
		if (!$this->billingService->claimScanCredit($organization)) {
			return null;
		}

		try {
			$scan = Scan::create(array(
				"project_id" => $project->id,
				"triggered_by" => $user->id,
				"status" => ScanStatus::Pending,
				"scan_type" => "single",
				"max_pages_requested" => 1,
				"crawl_depth_limit" => 0,
				"credit_state" => CreditState::Claimed->value,
			));

			ProcessScanJob::dispatch($scan);

			return $scan;
		} catch (\Throwable $exception) {
			/* Release the credit we just claimed so the user does not lose
			   monthly quota for a scan that never ran. Mirrors the ScanController
			   rollback pattern. */
			$this->billingService->releaseScanCredit($organization);

			Log::warning("Scan dispatch failed", array(
				"project_id" => $project->id,
				"user_id" => $user->id,
				"error" => $exception->getMessage(),
			));

			return null;
		}
	}
}
