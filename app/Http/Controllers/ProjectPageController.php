<?php

namespace App\Http\Controllers;

use App\Enums\CreditState;
use App\Http\Requests\AddPageRequest;
use App\Jobs\ProcessPageAnalysisJob;
use App\Models\Project;
use App\Models\ScanPage;
use App\Services\BillingService;
use App\Services\Scanning\PageAdditionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Manages project-level pages: add, check progress, and rescan.
 * Pages belong to the project and persist across homepage rescans.
 */
class ProjectPageController extends Controller
{
	public function __construct(
		private readonly PageAdditionService $pageAdditionService,
		private readonly BillingService $billingService,
	) {}

	/**
	 * Add a page URL to a project for background analysis.
	 * POST /projects/{project}/pages
	 */
	public function store(AddPageRequest $request, Project $project): JsonResponse
	{
		Gate::authorize("access", $project);

		$latestScan = $project->ownScans()
			->where("status", \App\Enums\ScanStatus::Completed->value)
			->latest()
			->first();

		if (!$latestScan) {
			return response()->json(
				array("error" => "Run a homepage scan first before adding pages."),
				422,
			);
		}

		$organization = $request->user()->currentOrganization();
		$plan = $organization->plan;

		if (!$plan || $plan->max_additional_pages <= 0) {
			return response()->json(
				array("error" => "Your plan does not allow adding additional pages. Upgrade to unlock this feature."),
				403,
			);
		}

		$creditClaimed = $this->billingService->claimScanCredit($organization);
		if (!$creditClaimed) {
			return response()->json(
				array("error" => "You've used all your scan credits this month."),
				429,
			);
		}

		try {
			$scanPage = $this->pageAdditionService->addPage($project, $request->validated()["url"], $plan);

			return response()->json(array(
				"success" => true,
				"page" => array(
					"id" => $scanPage->id,
					"uuid" => $scanPage->uuid,
					"url" => $scanPage->url,
					"source" => $scanPage->source,
					"analysis_status" => $scanPage->analysis_status,
					"scanned_at" => $scanPage->updated_at?->toIso8601String(),
				),
			), 201);
		} catch (\InvalidArgumentException $exception) {
			$this->billingService->releaseScanCredit($organization);
			return response()->json(array("error" => $exception->getMessage()), 422);
		} catch (\OverflowException $exception) {
			$this->billingService->releaseScanCredit($organization);
			return response()->json(array("error" => $exception->getMessage()), 403);
		} catch (\Throwable $exception) {
			$this->billingService->releaseScanCredit($organization);
			Log::error("Failed to add page", array(
				"project_id" => $project->id,
				"url" => $request->validated()["url"],
				"error" => $exception->getMessage(),
			));

			return response()->json(
				array("error" => "An unexpected error occurred. Please try again."),
				500,
			);
		}
	}

	/**
	 * Return the current analysis status of a specific page.
	 * GET /projects/{project}/pages/{scanPage}/progress
	 */
	public function progress(Request $request, Project $project, ScanPage $scanPage): JsonResponse
	{
		Gate::authorize("access", $project);
		abort_unless($scanPage->project_id === $project->id, 404);

		$responseData = array(
			"analysis_status" => $scanPage->analysis_status,
			"page_score" => $scanPage->page_score,
			"error_message" => $scanPage->error_message,
			"url" => $scanPage->url,
			"scanned_at" => $scanPage->updated_at?->toIso8601String(),
		);

		if ($scanPage->analysis_status === "completed" && $scanPage->scan) {
			$scan = $scanPage->scan->fresh();
			$responseData["scan_scores"] = array(
				"overall_score" => $scan->overall_score,
				"seo_score" => $scan->seo_score,
				"health_score" => $scan->health_score,
			);
			$responseData["scan_page_url"] = route("scans.show-page", array($scan, $scanPage));
		}

		return response()->json($responseData);
	}

	/**
	 * Re-analyze a previously analyzed page. Clears old results and dispatches a new analysis job.
	 * POST /projects/{project}/pages/{scanPage}/rescan
	 */
	public function rescan(Request $request, Project $project, ScanPage $scanPage): JsonResponse
	{
		Gate::authorize("access", $project);
		abort_unless($scanPage->project_id === $project->id, 404);

		if ($scanPage->analysis_status === "running" || $scanPage->analysis_status === "pending") {
			return response()->json(
				array("error" => "This page is already being analyzed."),
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
			$scanPage->moduleResults()->delete();
			$scanPage->update(array(
				"analysis_status" => "pending",
				"page_score" => null,
				"error_message" => null,
				"credit_state" => CreditState::Claimed->value,
			));

			ProcessPageAnalysisJob::dispatch($scanPage);
		} catch (\Throwable $exception) {
			$this->billingService->releaseScanCredit($organization);

			Log::error("Failed to start page rescan", array(
				"project_id" => $project->id,
				"scan_page_id" => $scanPage->id,
				"error" => $exception->getMessage(),
			));

			return response()->json(
				array("error" => "Failed to start rescan. Please try again."),
				500,
			);
		}

		Log::info("Page rescan started", array(
			"project_id" => $project->id,
			"scan_page_id" => $scanPage->id,
			"url" => $scanPage->url,
		));

		return response()->json(array(
			"success" => true,
			"analysis_status" => "pending",
		));
	}
}
