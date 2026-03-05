<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\ScanModuleResult;
use App\Models\SubscriptionUsage;
use App\Services\Ai\AiGatewayFactory;
use App\Services\Ai\OnDemandAiOptimizer;
use App\Services\Ai\Prompts\ModulePromptFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AiOptimizationController extends Controller
{
	public function __construct(
		private readonly OnDemandAiOptimizer $optimizer,
		private readonly AiGatewayFactory $gatewayFactory,
	) {}

	/**
	 * Optimize a single module with AI.
	 * POST /scans/{scan}/ai/module/{scanModuleResult}
	 */
	public function optimizeModule(Request $request, Scan $scan, ScanModuleResult $scanModuleResult): JsonResponse
	{
		Gate::authorize("access", $scan->project);
		$this->validateScanCompleted($scan);
		$this->validateModuleBelongsToScan($scanModuleResult, $scan);
		$this->validateModuleAiEligible($scanModuleResult);
		$this->validateAiAvailable($request);

		$result = $this->optimizer->optimizeModule($scanModuleResult, $request->user());

		return response()->json($result, $result["success"] ? 200 : 422);
	}

	/**
	 * Generate the executive summary for a scan.
	 * POST /scans/{scan}/ai/executive-summary
	 */
	public function generateExecutiveSummary(Request $request, Scan $scan): JsonResponse
	{
		Gate::authorize("access", $scan->project);
		$this->validateScanCompleted($scan);
		$this->validateAiAvailable($request);

		$result = $this->optimizer->generateExecutiveSummary($scan, $request->user());

		return response()->json($result, $result["success"] ? 200 : 422);
	}

	/**
	 * Check AI availability and which modules already have insights.
	 * GET /scans/{scan}/ai/status
	 */
	public function checkAiStatus(Request $request, Scan $scan): JsonResponse
	{
		Gate::authorize("access", $scan->project);

		$optimizedModuleIds = $scan->moduleResults()
			->whereNotNull("ai_suggestion")
			->pluck("id")
			->toArray();

		return response()->json(array(
			"aiAvailable" => $this->gatewayFactory->isAvailable($request->user()),
			"optimizedModuleIds" => $optimizedModuleIds,
			"hasExecutiveSummary" => $scan->ai_executive_summary !== null,
		));
	}

	private function validateScanCompleted(Scan $scan): void
	{
		abort_unless($scan->isComplete(), 422, "Scan must be completed before AI optimization.");
	}

	private function validateModuleBelongsToScan(ScanModuleResult $moduleResult, Scan $scan): void
	{
		abort_unless(
			$moduleResult->scan_id === $scan->id,
			404,
			"Module result does not belong to this scan.",
		);
	}

	private function validateModuleAiEligible(ScanModuleResult $moduleResult): void
	{
		abort_unless(
			ModulePromptFactory::isEligible($moduleResult->module_key),
			422,
			"AI optimization is not available for this module.",
		);
	}

	private function validateAiAvailable(Request $request): void
	{
		$organization = $request->user()->currentOrganization();
		$usage = $organization ? SubscriptionUsage::resolveCurrentPeriod($organization) : null;
		$maxAiCalls = (int) config("ai.limits.max_monthly_ai_calls", 500);

		abort_unless(
			$usage === null || $usage->ai_calls_used < $maxAiCalls,
			429,
			"AI monthly usage limit reached. Upgrade your plan or wait until your next billing period.",
		);

		abort_unless(
			$this->gatewayFactory->isAvailable($request->user()),
			422,
			"No AI API key configured. Add one in your account settings.",
		);
	}
}
