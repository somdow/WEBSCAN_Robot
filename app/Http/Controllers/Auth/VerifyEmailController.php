<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Scanning\ScanDispatchService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
	/**
	 * Mark the authenticated user's email address as verified.
	 *
	 * If the user registered via the landing-page hero form, a Project was
	 * created during registration with no scans attached. This is the moment
	 * we dispatch its first scan so the user lands on the project view with
	 * a live progress bar — they see the scan happen rather than discovering
	 * a silently-completed one.
	 */
	public function __invoke(
		EmailVerificationRequest $request,
		ScanDispatchService $scanDispatchService,
	): RedirectResponse
	{
		$wasUnverified = !$request->user()->hasVerifiedEmail();

		if ($wasUnverified) {
			$request->user()->markEmailAsVerified();
			event(new Verified($request->user()));

			$redirectUrl = $this->kickOffPendingScan($request->user(), $scanDispatchService);
			if ($redirectUrl !== null) {
				return redirect($redirectUrl);
			}
		}

		/* Redirect directly to dashboard instead of intended() to avoid looping
		   back to the verify-email page when it was the last "intended" URL. */
		return redirect()->route("dashboard", array("verified" => 1));
	}

	/**
	 * If the user has a pending project (created during registration but with
	 * no scans yet), dispatch its first scan and return the URL to redirect
	 * to. Returns null when nothing is pending or when dispatch returns null
	 * (credit exhaustion or transient failure).
	 */
	private function kickOffPendingScan(User $user, ScanDispatchService $scanDispatchService): ?string
	{
		$organization = $user->currentOrganization();
		if ($organization === null) {
			return null;
		}

		/* Only fire on projects explicitly flagged by the landing-signup flow.
		   "any scanless project" is unsafe — invited users joining an existing
		   org could trigger a scan on a pre-existing project they did not own. */
		$pendingProject = $organization->projects()
			->where("auto_scan_pending", true)
			->doesntHave("scans")
			->latest()
			->first();

		if ($pendingProject === null) {
			return null;
		}

		/* Clear the flag whether or not credit-claim succeeds — we should not
		   re-dispatch on a future verification (e.g., re-verification email). */
		$pendingProject->forceFill(array("auto_scan_pending" => false))->save();

		$scan = $scanDispatchService->dispatchFirstScan($pendingProject, $user, $organization);

		if ($scan === null) {
			return route("projects.show", $pendingProject);
		}

		return route("projects.show", array("project" => $pendingProject, "scan" => $scan));
	}
}
