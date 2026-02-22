<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\PlanOverrideService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganizations extends ListRecords
{
	protected static string $resource = OrganizationResource::class;

	public function mount(): void
	{
		parent::mount();

		self::expireStaleOverrides();
	}

	protected function getHeaderActions(): array
	{
		return array(
			CreateAction::make(),
		);
	}

	/**
	 * Batch-expire any overrides past their expiration before rendering the table.
	 * Runs once per page load — not per row — as a fallback for when the scheduler isn't active.
	 */
	private static function expireStaleOverrides(): void
	{
		$expiredOrganizations = Organization::query()
			->whereNotNull("original_plan_id")
			->whereNotNull("override_expires_at")
			->where("override_expires_at", "<=", now())
			->with(array("plan", "originalPlan"))
			->get();

		if ($expiredOrganizations->isEmpty()) {
			return;
		}

		$overrideService = app(PlanOverrideService::class);

		foreach ($expiredOrganizations as $organization) {
			$overrideService->removeOverride(
				$organization,
				null,
				"Auto-expired: override duration elapsed",
				true,
			);
		}
	}
}
