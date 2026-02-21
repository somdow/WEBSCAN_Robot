<?php

namespace App\Http\Controllers;

use App\Enums\CreditState;
use App\Enums\ScanStatus;
use App\Http\Requests\TriggerScanRequest;
use App\Jobs\ProcessScanJob;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanPage;
use App\Services\Ai\AiGatewayFactory;
use App\Services\BillingService;
use App\Services\Scanning\ModuleRegistry;
use App\Services\Scanning\ScanViewDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ScanController extends Controller
{
	public function __construct(
		private readonly ScanViewDataService $scanViewDataService,
		private readonly BillingService $billingService,
	) {}

	/**
	 * List all scans across all projects for the current organization.
	 * GET /scans
	 */
	public function index(Request $request): View
	{
		$organization = $request->user()->currentOrganization();

		$scans = Scan::whereHas("project", function ($query) use ($organization) {
			$query->where("organization_id", $organization->id);
		})
			->whereNull("competitor_id")
			->with(array("project", "triggeredBy"))
			->orderBy("created_at", "desc")
			->paginate(20);

		return view("scans.index", array("scans" => $scans));
	}

	/**
	 * Trigger a new scan for a project.
	 * POST /projects/{project}/scan
	 */
	public function store(TriggerScanRequest $request, Project $project): RedirectResponse|JsonResponse
	{
		$organization = $request->user()->currentOrganization();

		if (!$this->billingService->claimScanCredit($organization)) {
			$message = "You've used all your scan credits this month.";
			if ($request->wantsJson()) {
				return response()->json(array(
					"success" => false,
					"error" => $message,
				), 429);
			}

			return redirect()
				->route("projects.show", $project)
				->with("error", $message);
		}

		try {
			$scan = Scan::create(array(
				"project_id" => $project->id,
				"triggered_by" => $request->user()->id,
				"status" => ScanStatus::Pending,
				"scan_type" => "single",
				"max_pages_requested" => 1,
				"crawl_depth_limit" => 0,
				"credit_state" => CreditState::Claimed->value,
			));

			ProcessScanJob::dispatch($scan);

			if ($request->wantsJson()) {
				return response()->json(array(
					"success" => true,
					"scan_id" => $scan->getRouteKey(),
				));
			}

			return redirect()
				->route("projects.show", $project)
				->with("success", "Scan started! Results will appear shortly.");
		} catch (\Exception $exception) {
			$this->billingService->releaseScanCredit($organization);

			Log::error("Failed to create scan", array(
				"project_id" => $project->id,
				"user_id" => $request->user()->id,
				"error" => $exception->getMessage(),
			));

			if ($request->wantsJson()) {
				return response()->json(array(
					"success" => false,
					"error" => "Failed to start scan. Please try again.",
				), 500);
			}

			return redirect()
				->route("projects.show", $project)
				->with("error", "Failed to start scan. Please try again.");
		}
	}

	/**
	 * Return current scan progress as JSON for the frontend progress bar.
	 * GET /scans/{scan}/progress
	 */
	public function progress(Request $request, Scan $scan): JsonResponse
	{
		Gate::authorize("access", $scan->project);

		return response()->json(array(
			"status" => $scan->status->value,
			"progress_percent" => $scan->progress_percent ?? 0,
			"progress_label" => $scan->progress_label ?? "Preparing scan...",
			"is_complete" => $scan->isComplete(),
		));
	}

	/**
	 * Redirect to the project page with scan context.
	 * GET /scans/{scan}
	 */
	public function show(Request $request, Scan $scan): RedirectResponse
	{
		Gate::authorize("access", $scan->project);

		return redirect()->route("projects.show", array(
			"project" => $scan->project,
			"scan" => $scan->getRouteKey(),
		));
	}

	/**
	 * Display module results for a specific crawled page.
	 * GET /scans/{scan}/page/{scanPage}
	 */
	public function showPage(
		Request $request,
		Scan $scan,
		ScanPage $scanPage,
		ModuleRegistry $moduleRegistry,
		AiGatewayFactory $gatewayFactory,
	): View {
		Gate::authorize("access", $scan->project);

		abort_unless($scanPage->scan_id === $scan->id, 404);

		$scan->load(array("project", "triggeredBy"));

		$pageResults = $scanPage->moduleResults()->get();
		$visibleResults = $this->scanViewDataService->filterVisibleResults($pageResults, $scan);

		$groupedResults = $visibleResults
			->groupBy(fn($result) => $moduleRegistry->resolveCategory($result->module_key));

		$statusGroupedResults = $visibleResults
			->groupBy(fn($result) => $result->status->value);

		$statusCounts = $statusGroupedResults
			->map->count()
			->toArray();

		$moduleLabels = $moduleRegistry->labelMap();
		$aiAccess = $this->scanViewDataService->resolveAiAccess($request->user(), $gatewayFactory);

		return view("scans.show-page", array(
			"scan" => $scan,
			"scanPage" => $scanPage,
			"groupedResults" => $groupedResults,
			"statusGroupedResults" => $statusGroupedResults,
			"statusCounts" => $statusCounts,
			"moduleLabels" => $moduleLabels,
			"aiAvailable" => $aiAccess["aiAvailable"],
			"hasApiKey" => $aiAccess["hasApiKey"],
		));
	}
}
