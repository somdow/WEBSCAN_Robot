<?php

namespace App\Http\Controllers;

use App\Enums\CreditState;
use App\Enums\ScanStatus;
use App\Jobs\DiscoverPagesJob;
use App\Jobs\ProcessPageAnalysisJob;
use App\Models\Project;
use App\Models\ScanPage;
use App\Services\BillingService;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class DiscoveryController extends Controller
{
	public function __construct(
		private readonly BillingService $billingService,
	) {}

	/**
	 * Start a lightweight discovery crawl to find page URLs.
	 * POST /projects/{project}/discover
	 */
	public function discover(Request $request, Project $project): JsonResponse
	{
		Gate::authorize("access", $project);

		$hasCompletedScan = $project->ownScans()
			->where("status", ScanStatus::Completed->value)
			->exists();

		if (!$hasCompletedScan) {
			return response()->json(array("error" => "Run a homepage scan first before discovering pages."), 422);
		}

		/* Atomic guard: only one request can transition away from non-running state.
		   Prevents duplicate dispatches from concurrent requests. */
		$locked = Project::where("id", $project->id)
			->where(function ($query) {
				$query->whereNull("discovery_status")
					->orWhere("discovery_status", "!=", "running");
			})
			->update(array("discovery_status" => "running"));

		if ($locked === 0) {
			return response()->json(array("error" => "Discovery is already in progress."), 422);
		}

		$organization = $request->user()->currentOrganization();
		$plan = $organization->plan;

		if (!$plan || $plan->max_additional_pages <= 0) {
			return response()->json(
				array("error" => "Your plan does not support page discovery. Upgrade to unlock this feature."),
				403,
			);
		}

		if (!$this->billingService->claimScanCredit($organization)) {
			return response()->json(
				array("error" => "You've used all your scan credits this month. Discovery costs 1 scan credit."),
				429,
			);
		}

		try {
			$project->discoveredPages()->delete();

			$maxDiscoveryPages = min($plan->max_additional_pages * 3, 150);
			DiscoverPagesJob::dispatch($project, $maxDiscoveryPages, $organization);
		} catch (\Throwable $exception) {
			$this->billingService->releaseScanCredit($organization);
			$project->updateQuietly(array("discovery_status" => "failed"));

			Log::error("Failed to dispatch discovery job after claiming credit", array(
				"project_id" => $project->id,
				"error" => $exception->getMessage(),
			));

			return response()->json(
				array("error" => "Failed to start discovery. Your scan credit has been returned."),
				500,
			);
		}

		return response()->json(array(
			"success" => true,
			"discovery_status" => "running",
		));
	}

	/**
	 * Return the list of discovered page URLs.
	 * GET /projects/{project}/discovered
	 */
	public function discoveredPages(Request $request, Project $project): JsonResponse
	{
		Gate::authorize("access", $project);

		$discoveredPages = $project->discoveredPages()
			->orderBy("crawl_depth")
			->orderBy("url")
			->get()
			->map(fn($page) => array(
				"id" => $page->id,
				"url" => $page->url,
				"crawl_depth" => $page->crawl_depth,
				"is_analyzed" => $page->is_analyzed,
			));

		return response()->json(array(
			"discovery_status" => $project->discovery_status,
			"pages" => $discoveredPages,
		));
	}

	/**
	 * Analyze selected discovered pages (max 5 at a time).
	 * Each page consumes one scan credit.
	 * POST /projects/{project}/analyze-selected
	 */
	public function analyzeSelected(Request $request, Project $project): JsonResponse
	{
		Gate::authorize("access", $project);

		$validated = $request->validate(array(
			"page_ids" => array("required", "array", "min:1", "max:5"),
			"page_ids.*" => array("required", "integer"),
		));

		$organization = $request->user()->currentOrganization();
		$plan = $organization->plan;

		if (!$plan || $plan->max_additional_pages <= 0) {
			return response()->json(array("error" => "Your plan does not support page analysis."), 403);
		}

		$existingAdditionalCount = $project->additionalPages()->count();
		$remainingSlots = max(0, $plan->max_additional_pages - $existingAdditionalCount);
		$selectedCount = count($validated["page_ids"]);

		if ($selectedCount > $remainingSlots) {
			return response()->json(array(
				"error" => "You can only add {$remainingSlots} more page(s) on your current plan.",
			), 403);
		}

		$discoveredPages = $project->discoveredPages()
			->whereIn("id", $validated["page_ids"])
			->where("is_analyzed", false)
			->get();

		if ($discoveredPages->isEmpty()) {
			return response()->json(array("error" => "No valid pages selected."), 422);
		}

		$createdPages = array();

		foreach ($discoveredPages as $discovered) {
			$normalizedUrl = UrlNormalizer::forCrawl($discovered->url);

			$alreadyExists = $project->pages()
				->where("url", $normalizedUrl)
				->exists();

			if ($alreadyExists) {
				$discovered->update(array("is_analyzed" => true));
				$existingPage = $project->pages()->where("url", $normalizedUrl)->first();
				if ($existingPage) {
					if ($existingPage->source === "scan") {
						$existingPage->update(array("source" => "discovery"));
					}
					$createdPages[] = array(
						"id" => $existingPage->id,
						"uuid" => $existingPage->uuid,
						"url" => $existingPage->url,
						"analysis_status" => $existingPage->analysis_status,
						"page_score" => $existingPage->page_score,
						"scanned_at" => $existingPage->updated_at?->toIso8601String(),
					);
				}
				continue;
			}

			if (!$this->billingService->claimScanCredit($organization)) {
				return response()->json(array(
					"error" => "Not enough scan credits to queue all selected pages.",
					"pages" => $createdPages,
				), 429);
			}

			try {
				$scanPage = ScanPage::create(array(
					"project_id" => $project->id,
					"url" => $normalizedUrl,
					"is_homepage" => false,
					"crawl_depth" => $discovered->crawl_depth,
					"source" => "discovery",
					"analysis_status" => "pending",
					"credit_state" => CreditState::Claimed->value,
				));

				$discovered->update(array("is_analyzed" => true));
				ProcessPageAnalysisJob::dispatch($scanPage);
			} catch (\Throwable $exception) {
				$this->billingService->releaseScanCredit($organization);

				Log::error("Failed queuing discovered page analysis", array(
					"project_id" => $project->id,
					"url" => $normalizedUrl,
					"error" => $exception->getMessage(),
				));

				return response()->json(array(
					"error" => "Failed to queue discovered page analysis.",
					"pages" => $createdPages,
				), 500);
			}

			$createdPages[] = array(
				"id" => $scanPage->id,
				"uuid" => $scanPage->uuid,
				"url" => $scanPage->url,
				"analysis_status" => "pending",
				"scanned_at" => $scanPage->updated_at?->toIso8601String(),
			);
		}

		Log::info("Bulk page analysis started", array(
			"project_id" => $project->id,
			"pages_queued" => count($createdPages),
		));

		return response()->json(array(
			"success" => true,
			"pages" => $createdPages,
		), 201);
	}
}
