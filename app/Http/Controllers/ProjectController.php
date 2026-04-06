<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatus;
use App\Enums\CreditState;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Jobs\ProcessScanJob;
use App\Models\Scan;
use App\Models\ScanPage;
use App\Services\Ai\AiGatewayFactory;
use App\Services\BillingService;
use App\Services\ProjectService;
use App\Services\Scanning\ScanViewDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProjectController extends Controller
{
	public function __construct(
		private readonly ProjectService $projectService,
		private readonly ScanViewDataService $scanViewDataService,
		private readonly BillingService $billingService,
	) {}

	public function index(Request $request): View
	{
		$organization = $request->user()->currentOrganization();
		$projects = $organization->projects()
			->with("latestScan")
			->latest()
			->paginate(15);

		return view("projects.index", array(
			"projects" => $projects,
		));
	}

	public function create(): View
	{
		return view("projects.create");
	}

	public function store(StoreProjectRequest $request): RedirectResponse
	{
		$organization = $request->user()->currentOrganization();
		$project = $this->projectService->createProject($organization, $request->validated());

		/* Auto-trigger first scan if the user has credits available */
		if ($this->billingService->claimScanCredit($organization)) {
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

				return redirect()
					->route("projects.show", $project)
					->with("success", "Project created! Scan started automatically.");
			} catch (\Exception $exception) {
				$this->billingService->releaseScanCredit($organization);

				Log::error("Failed to auto-trigger scan for new project", array(
					"project_id" => $project->id,
					"error" => $exception->getMessage(),
				));
			}
		}

		return redirect()
			->route("projects.show", $project)
			->with("success", "Project created successfully.");
	}

	public function show(Request $request, Project $project, AiGatewayFactory $gatewayFactory): View
	{
		Gate::authorize("access", $project);

		$project->load(array("latestScan", "ownScans" => function ($query) {
			$query->orderBy("created_at", "desc")->limit(20);
		}));

		$latestScan = $project->latestScan;
		$activeScan = null;
		$displayScan = null;
		$scanViewData = null;
		$aiAccess = array("aiAvailable" => false, "hasApiKey" => false);

		/* Check for a specific historical scan requested via ?scan={id} */
		$requestedScanId = $request->query("scan");
		$requestedScan = null;
		if ($requestedScanId) {
			$requestedScan = $project->ownScans()->where("id", $requestedScanId)->first();
		}

		if ($latestScan && !$latestScan->isComplete()) {
			/* Scan is running — show progress bar + previous results blurred behind it */
			$activeScan = $latestScan;
			$displayScan = $requestedScan && $requestedScan->isComplete()
				? $requestedScan
				: $this->findPreviousCompletedScan($project, $latestScan->id);
		} elseif ($requestedScan && $requestedScan->isComplete()) {
			$displayScan = $requestedScan;
		} elseif ($latestScan) {
			$displayScan = $latestScan;
		}

		if ($displayScan) {
			$displayScan->load(array("moduleResults", "pages", "triggeredBy"));
			$scanViewData = $this->scanViewDataService->prepareScanViewData($displayScan);
			$aiAccess = $this->scanViewDataService->resolveAiAccess($request->user(), $gatewayFactory);
		}

		$completedScans = $project->ownScans->filter(
			fn($scan) => $scan->status === ScanStatus::Completed && $scan->overall_score !== null
		);

		$organization = $request->user()->currentOrganization();
		$plan = $organization->plan;
		$canAddPages = $plan && $plan->max_additional_pages > 0;
		$maxCompetitors = $plan?->max_competitors ?? 0;
		$competitors = $project->competitors()->with("latestScan.moduleResults")->get();
		$usage = $this->billingService->resolveCurrentUsage($organization);
		$scansUsed = $usage->scans_used;
		$scansMax = $plan?->max_scans_per_month ?? 10;
		$latestPageIds = $project->additionalPages()
			->selectRaw("MAX(id) as id")
			->groupBy("url")
			->pluck("id");
		$additionalPages = ScanPage::whereIn("id", $latestPageIds)
			->orderByDesc("created_at")
			->get();

		return view("projects.show", array(
			"project" => $project,
			"latestScan" => $latestScan,
			"activeScan" => $activeScan,
			"displayScan" => $displayScan,
			"requestedScan" => $requestedScan,
			"scanViewData" => $scanViewData,
			"completedScans" => $completedScans,
			"aiAvailable" => $aiAccess["aiAvailable"],
			"hasApiKey" => $aiAccess["hasApiKey"],
			"canAddPages" => $canAddPages,
			"additionalPages" => $additionalPages,
			"maxAdditionalPages" => $plan?->max_additional_pages ?? 0,
			"scansUsed" => $scansUsed,
			"scansMax" => $scansMax,
			"competitors" => $competitors,
			"maxCompetitors" => $maxCompetitors,
			"showCompetitorsTab" => $maxCompetitors > 0,
		));
	}

	/**
	 * Find the most recent completed scan for a project, excluding a specific scan.
	 */
	private function findPreviousCompletedScan(Project $project, int $excludeScanId): ?Scan
	{
		return $project->ownScans()
			->where("status", ScanStatus::Completed->value)
			->where("id", "!=", $excludeScanId)
			->latest()
			->first();
	}

	public function edit(Request $request, Project $project): View
	{
		Gate::authorize("access", $project);

		return view("projects.edit", array("project" => $project));
	}

	public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
	{
		Gate::authorize("access", $project);

		$this->projectService->updateProject($project, $request->validated());

		return redirect()
			->route("projects.show", $project)
			->with("success", "Project updated successfully.");
	}

	public function destroy(Request $request, Project $project): RedirectResponse
	{
		Gate::authorize("access", $project);

		$project->delete();

		return redirect()
			->route("projects.index")
			->with("success", "Project deleted.");
	}
}
