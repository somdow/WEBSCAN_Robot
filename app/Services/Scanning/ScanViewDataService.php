<?php

namespace App\Services\Scanning;

use App\Models\Scan;
use App\Models\User;
use App\Services\Ai\AiGatewayFactory;
use Illuminate\Support\Collection;

/**
 * Prepares scan result data for display in views.
 * Used by both ScanController (historical view) and ProjectController (combined view).
 */
class ScanViewDataService
{
	public function __construct(
		private readonly ModuleRegistry $moduleRegistry,
	) {}

	/**
	 * Build all view data needed for displaying a completed scan's results.
	 * Returns null if the scan has no results to display.
	 *
	 * @return array{type: string, ...}|null
	 */
	public function prepareScanViewData(Scan $scan): ?array
	{
		if (!$scan->isComplete() || $scan->moduleResults->isEmpty()) {
			return null;
		}

		return $this->prepareSinglePageData($scan);
	}

	/**
	 * Resolve AI access flags for the current user.
	 *
	 * @return array{aiAvailable: bool, hasApiKey: bool}
	 */
	public function resolveAiAccess(User $user, AiGatewayFactory $gatewayFactory): array
	{
		$aiAvailable = $user->canAccessAi();

		return array(
			"aiAvailable" => $aiAvailable,
			"hasApiKey" => $aiAvailable && $gatewayFactory->isAvailable($user),
		);
	}

	/**
	 * Extract the SERP Preview module from grouped results so it can be rendered
	 * as a dedicated sidebar panel instead of a generic module card.
	 *
	 * Mutates $groupedResults (and optionally $statusGroupedResults) by removing
	 * the serpPreview module. Returns the extracted module result and its data,
	 * or nulls if no SERP Preview exists.
	 *
	 * @return array{result: \App\Models\ScanModuleResult|null, data: array|null}
	 */
	public function extractSerpPreview(Collection $groupedResults, ?Collection $statusGroupedResults = null): array
	{
		if (!$groupedResults->has("Extras")) {
			return array("result" => null, "data" => null);
		}

		$extrasModules = $groupedResults->get("Extras");
		$serpResult = $extrasModules->first(fn($r) => $r->module_key === "serpPreview");

		if (!$serpResult) {
			return array("result" => null, "data" => null);
		}

		/* Remove from the Extras category (drop the category entirely if now empty) */
		$filteredExtras = $extrasModules->reject(fn($r) => $r->module_key === "serpPreview");
		$filteredExtras->isNotEmpty()
			? $groupedResults->put("Extras", $filteredExtras)
			: $groupedResults->forget("Extras");

		/* Remove from status-grouped results when provided (single-page views) */
		if ($statusGroupedResults !== null) {
			$statusKey = $serpResult->status->value;
			if ($statusGroupedResults->has($statusKey)) {
				$filtered = $statusGroupedResults->get($statusKey)->reject(fn($r) => $r->module_key === "serpPreview");
				$filtered->isNotEmpty()
					? $statusGroupedResults->put($statusKey, $filtered)
					: $statusGroupedResults->forget($statusKey);
			}
		}

		/* Extract the structured SERP data from findings */
		$serpData = null;
		foreach ($serpResult->findings ?? array() as $finding) {
			if (($finding["type"] ?? "") === "data" && ($finding["key"] ?? "") === "serpPreview") {
				$serpData = $finding["value"];
				break;
			}
		}

		return array("result" => $serpResult, "data" => $serpData);
	}

	/**
	 * Reject WordPress modules from a result collection when the scan target is not a WP site.
	 */
	public function filterVisibleResults(Collection $results, Scan $scan): Collection
	{
		return $results->when(
			!$scan->is_wordpress,
			fn($collection) => $collection->reject(
				fn($result) => in_array($result->module_key, ModuleRegistry::WORDPRESS_MODULE_KEYS, true)
			)
		);
	}

	/**
	 * Build view data for a single-page scan.
	 */
	private function prepareSinglePageData(Scan $scan): array
	{
		$homepageScanPageId = $scan->pages()
			->where("is_homepage", true)
			->latest("id")
			->value("id");

		$overviewResults = $scan->moduleResults->filter(function ($result) use ($homepageScanPageId) {
			if ($result->scan_page_id === null) {
				return true;
			}

			if ($homepageScanPageId === null) {
				return false;
			}

			return (int) $result->scan_page_id === (int) $homepageScanPageId;
		});

		$visibleResults = $this->filterVisibleResults($overviewResults, $scan);

		$groupedResults = $visibleResults
			->groupBy(fn($result) => $this->moduleRegistry->resolveCategory($result->module_key))
			->map(fn($modules) => $modules->sortBy(
				fn($result) => $this->moduleRegistry->displayOrder($result->module_key)
			)->values());

		$statusGroupedResults = $visibleResults
			->groupBy(fn($result) => $result->status->value);

		$statusCounts = $visibleResults
			->groupBy(fn($result) => $result->status->value)
			->map->count()
			->toArray();

		return array(
			"type" => "single",
			"groupedResults" => $groupedResults,
			"statusGroupedResults" => $statusGroupedResults,
			"statusCounts" => $statusCounts,
			"moduleLabels" => $this->moduleRegistry->labelMap(),
		);
	}

}
