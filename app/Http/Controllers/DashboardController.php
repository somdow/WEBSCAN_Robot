<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
	public function __construct(
		private readonly DashboardService $dashboardService,
	) {}

	public function __invoke(Request $request): View
	{
		$user = $request->user();
		$organization = $user->currentOrganization();
		$plan = $organization?->plan;

		$stats = $organization
			? $this->dashboardService->gatherStats($organization)
			: array("projectCount" => 0, "scansThisMonth" => 0, "averageScore" => null);

		$recentScans = $organization
			? $this->dashboardService->recentScans($organization)
			: collect();

		$gettingStarted = $organization
			? $this->dashboardService->gettingStartedStatus($organization)
			: array("hasProject" => false, "hasCompletedScan" => false);

		return view("dashboard", array(
			"projectCount" => $stats["projectCount"],
			"scansThisMonth" => $stats["scansThisMonth"],
			"maxScans" => $plan->max_scans_per_month ?? 10,
			"averageScore" => $stats["averageScore"],
			"plan" => $plan,
			"recentScans" => $recentScans,
			"gettingStarted" => $gettingStarted,
		));
	}
}
