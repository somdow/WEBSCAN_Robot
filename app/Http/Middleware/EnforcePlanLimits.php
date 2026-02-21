<?php

namespace App\Http\Middleware;

use App\Services\BillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
	public function __construct(
		private readonly BillingService $billingService,
	) {}

	/**
	 * Check plan limits before allowing the request through.
	 *
	 * Usage: middleware('enforce.plan:projects') or middleware('enforce.plan:scans')
	 */
	public function handle(Request $request, Closure $next, string $limitType): Response
	{
		$organization = $request->user()?->currentOrganization();

		if ($organization === null) {
			return $next($request);
		}

		$plan = $organization->plan;

		switch ($limitType) {
			case "projects":
				if (!$this->billingService->canCreateProject($organization)) {
					$maxProjects = $plan?->max_projects ?? 1;
					$message = "You have reached your plan limit of {$maxProjects} project(s). Upgrade your plan to add more.";

					return $this->denyResponse($request, $message);
				}
				break;

			case "scans":
				if (!$this->billingService->canTriggerScan($organization)) {
					$maxScans = $plan?->max_scans_per_month ?? 10;
					$message = "You have used all {$maxScans} scans for this month. Upgrade your plan or wait for the next billing period.";

					return $this->denyResponse($request, $message);
				}
				break;
		}

		return $next($request);
	}

	/**
	 * Return a JSON or redirect response depending on the request type.
	 */
	private function denyResponse(Request $request, string $message): Response
	{
		if ($request->wantsJson()) {
			return response()->json(array(
				"success" => false,
				"error" => $message,
			), 429);
		}

		return redirect()->back()->with("error", $message);
	}
}
