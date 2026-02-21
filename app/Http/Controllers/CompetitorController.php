<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use App\Models\Project;
use App\Services\Ai\AiGatewayFactory;
use App\Services\BillingService;
use App\Services\Scanning\CompetitorService;
use App\Services\Scanning\ScanViewDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CompetitorController extends Controller
{
	public function __construct(
		private readonly CompetitorService $competitorService,
		private readonly BillingService $billingService,
		private readonly ScanViewDataService $scanViewDataService,
	) {}

	/**
	 * Add a competitor to a project and trigger its initial scan.
	 * POST /projects/{project}/competitors
	 */
	public function store(Request $request, Project $project): JsonResponse
	{
		Gate::authorize("access", $project);

		$validated = $request->validate(array(
			"url" => "required|url|max:2048",
		));

		$organization = $request->user()->currentOrganization();

		if (!$this->billingService->claimScanCredit($organization)) {
			return response()->json(
				array("error" => "You've used all your scan credits this month."),
				429,
			);
		}

		try {
			$competitor = $this->competitorService->addCompetitor(
				$project,
				$validated["url"],
				$organization,
			);

			$scan = $this->competitorService->triggerScan($competitor, $request->user());

			return response()->json(array(
				"success" => true,
				"competitor" => array(
					"id" => $competitor->id,
					"uuid" => $competitor->uuid,
					"url" => $competitor->url,
					"name" => $competitor->displayName(),
					"scan_uuid" => $scan->uuid,
				),
			), 201);
		} catch (\InvalidArgumentException $exception) {
			$this->billingService->releaseScanCredit($organization);
			return response()->json(array("error" => $exception->getMessage()), 422);
		} catch (\Throwable $exception) {
			$this->billingService->releaseScanCredit($organization);

			Log::error("Failed to add competitor", array(
				"project_id" => $project->id,
				"url" => $validated["url"],
				"error" => $exception->getMessage(),
			));

			return response()->json(
				array("error" => "An unexpected error occurred. Please try again."),
				500,
			);
		}
	}

	/**
	 * Remove a competitor and its associated scans.
	 * DELETE /projects/{project}/competitors/{competitor}
	 */
	public function destroy(Request $request, Project $project, Competitor $competitor): JsonResponse
	{
		Gate::authorize("access", $project);
		abort_unless($competitor->project_id === $project->id, 404);

		$competitor->delete();

		return response()->json(array("success" => true));
	}

	/**
	 * Trigger a rescan for an existing competitor.
	 * POST /projects/{project}/competitors/{competitor}/rescan
	 */
	public function rescan(Request $request, Project $project, Competitor $competitor): JsonResponse
	{
		Gate::authorize("access", $project);
		abort_unless($competitor->project_id === $project->id, 404);

		$latestScan = $competitor->scans()->latest()->first();
		if ($latestScan && !$latestScan->isComplete()) {
			return response()->json(
				array("error" => "A scan is already in progress for this competitor."),
				422,
			);
		}

		$organization = $request->user()->currentOrganization();

		if (!$this->billingService->claimScanCredit($organization)) {
			return response()->json(
				array("error" => "You've used all your scan credits this month."),
				429,
			);
		}

		try {
			$scan = $this->competitorService->triggerScan($competitor, $request->user());

			return response()->json(array(
				"success" => true,
				"scan_uuid" => $scan->uuid,
			));
		} catch (\Throwable $exception) {
			$this->billingService->releaseScanCredit($organization);

			Log::error("Failed to rescan competitor", array(
				"competitor_id" => $competitor->id,
				"error" => $exception->getMessage(),
			));

			return response()->json(
				array("error" => "Failed to start rescan. Please try again."),
				500,
			);
		}
	}

	/**
	 * Display full scan results for a competitor.
	 * GET /projects/{project}/competitors/{competitor}
	 */
	public function show(Request $request, Project $project, Competitor $competitor, AiGatewayFactory $gatewayFactory): View
	{
		Gate::authorize("access", $project);
		abort_unless($competitor->project_id === $project->id, 404);

		$scan = $competitor->latestScan;

		if (!$scan) {
			$scan = $competitor->scans()->latest()->first();
		}

		$scanViewData = null;
		$aiAccess = array("aiAvailable" => false, "hasApiKey" => false);

		if ($scan) {
			$scan->load(array("moduleResults", "pages", "triggeredBy"));
			$scanViewData = $this->scanViewDataService->prepareScanViewData($scan);
			$aiAccess = $this->scanViewDataService->resolveAiAccess($request->user(), $gatewayFactory);
		}

		return view("competitors.show", array(
			"project" => $project,
			"competitor" => $competitor,
			"scan" => $scan,
			"scanViewData" => $scanViewData,
			"aiAvailable" => $aiAccess["aiAvailable"],
			"hasApiKey" => $aiAccess["hasApiKey"],
		));
	}

	/**
	 * Return the current scan progress for a competitor.
	 * GET /projects/{project}/competitors/{competitor}/progress
	 */
	public function progress(Request $request, Project $project, Competitor $competitor): JsonResponse
	{
		Gate::authorize("access", $project);
		abort_unless($competitor->project_id === $project->id, 404);

		$scan = $competitor->scans()->latest()->first();

		if (!$scan) {
			return response()->json(array(
				"status" => "none",
				"progress_percent" => 0,
				"progress_label" => "No scan available",
				"is_complete" => false,
			));
		}

		$categoryScores = $this->competitorService->buildCategoryScores($scan);

		return response()->json(array(
			"status" => $scan->status->value,
			"progress_percent" => $scan->progress_percent ?? 0,
			"progress_label" => $scan->progress_label ?? "Preparing scan...",
			"is_complete" => $scan->isComplete(),
			"scores" => $scan->isComplete() ? array(
				"overall_score" => $scan->overall_score,
				"seo_score" => $scan->seo_score,
				"health_score" => $scan->health_score,
			) : null,
			"category_scores" => $categoryScores,
		));
	}
}
