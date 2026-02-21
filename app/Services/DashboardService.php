<?php

namespace App\Services;

use App\Enums\ScanStatus;
use App\Models\Organization;
use App\Models\Scan;
use Illuminate\Support\Collection;

class DashboardService
{
	/**
	 * Gather all dashboard statistics for an organization.
	 *
	 * @return array{projectCount: int, scansThisMonth: int, averageScore: int|null}
	 */
	public function gatherStats(Organization $organization): array
	{
		return array(
			"projectCount" => $this->countProjects($organization),
			"scansThisMonth" => $this->countScansThisMonth($organization),
			"averageScore" => $this->calculateAverageScore($organization),
		);
	}

	/**
	 * Fetch the most recent completed scans across all projects.
	 */
	public function recentScans(Organization $organization, int $limit = 5): Collection
	{
		return Scan::whereHas("project", function ($query) use ($organization) {
			$query->where("organization_id", $organization->id);
		})
			->where("status", ScanStatus::Completed)
			->with("project")
			->orderBy("created_at", "desc")
			->limit($limit)
			->get();
	}

	/**
	 * Determine getting-started checklist completion status.
	 *
	 * @return array{hasProject: bool, hasCompletedScan: bool}
	 */
	public function gettingStartedStatus(Organization $organization): array
	{
		$hasProject = $organization->projects()->exists();

		$hasCompletedScan = $hasProject && Scan::whereHas("project", function ($query) use ($organization) {
			$query->where("organization_id", $organization->id);
		})->where("status", ScanStatus::Completed)->exists();

		return array(
			"hasProject" => $hasProject,
			"hasCompletedScan" => $hasCompletedScan,
		);
	}

	private function countProjects(Organization $organization): int
	{
		return $organization->projects()->count();
	}

	private function countScansThisMonth(Organization $organization): int
	{
		return $organization->projects()
			->withCount(array("scans" => function ($query) {
				$query->whereMonth("created_at", now()->month)
					->whereYear("created_at", now()->year);
			}))
			->get()
			->sum("scans_count");
	}

	private function calculateAverageScore(Organization $organization): ?int
	{
		$average = $organization->projects()
			->join("scans", "projects.id", "=", "scans.project_id")
			->whereNotNull("scans.overall_score")
			->avg("scans.overall_score");

		return $average !== null ? (int) round($average) : null;
	}
}
