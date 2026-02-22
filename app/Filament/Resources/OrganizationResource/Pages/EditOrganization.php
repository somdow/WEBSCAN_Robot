<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\PlanOverrideService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditOrganization extends EditRecord
{
	protected static string $resource = OrganizationResource::class;

	public function mount(int | string $record): void
	{
		parent::mount($record);

		if ($this->record->isOverrideExpired()) {
			app(PlanOverrideService::class)->removeOverride(
				$this->record,
				null,
				"Auto-expired: override duration elapsed",
				true,
			);

			$this->record->refresh();
		}
	}

	protected function getHeaderActions(): array
	{
		return array(
			Action::make("overridePlan")
				->label("Override Plan")
				->icon(Heroicon::OutlinedGift)
				->color("warning")
				->fillForm(fn (): array => array(
					"target_plan_id" => $this->record->plan_id,
				))
				->schema(array(
					Select::make("target_plan_id")
						->label("New Plan")
						->options(Plan::query()->ordered()->pluck("name", "id")->toArray())
						->required(),
					Select::make("duration")
						->label("Duration")
						->options(array(
							"" => "No expiration",
							"5_minutes" => "5 minutes",
							"1_hour" => "1 hour",
							"4_hours" => "4 hours",
							"24_hours" => "24 hours",
							"3_days" => "3 days",
							"7_days" => "7 days",
							"14_days" => "14 days",
							"30_days" => "30 days",
						))
						->default("")
						->helperText("Override auto-reverts to the original plan after this duration."),
					Textarea::make("reason")
						->label("Reason / Notes")
						->rows(3)
						->maxLength(1000)
						->required(),
				))
				->action(function (array $data): void {
					$targetPlan = Plan::findOrFail($data["target_plan_id"]);
					$actor = auth()->user();
					abort_unless($actor instanceof \App\Models\User, 403);

					$duration = OrganizationResource::parseDuration($data["duration"] ?? "");

					app(PlanOverrideService::class)->applyOverride(
						$this->record,
						$targetPlan,
						$actor,
						$data["reason"] ?? null,
						$duration,
					);

					$expiryLabel = $duration !== null ? " (expires in " . $duration->forHumans() . ")" : "";
					Notification::make()
						->title("Plan overridden")
						->body("{$this->record->name} is now on {$targetPlan->name}.{$expiryLabel}")
						->success()
						->send();

					$this->refreshFormData(array("plan_id", "original_plan_id", "override_expires_at"));
				}),
			Action::make("removeOverride")
				->label("Remove Override")
				->icon(Heroicon::OutlinedArrowUturnLeft)
				->color("danger")
				->hidden(fn (): bool => !$this->record->hasActiveOverride())
				->modalHeading("Remove Plan Override")
				->modalDescription(fn (): string =>
					"This will restore {$this->record->name} from {$this->record->plan?->name} back to {$this->record->originalPlan?->name}."
				)
				->schema(array(
					Textarea::make("reason")
						->label("Reason / Notes")
						->rows(2)
						->maxLength(1000),
				))
				->action(function (array $data): void {
					$actor = auth()->user();
					abort_unless($actor instanceof \App\Models\User, 403);
					$originalPlanName = $this->record->originalPlan?->name ?? "original plan";

					app(PlanOverrideService::class)->removeOverride(
						$this->record,
						$actor,
						$data["reason"] ?? null,
					);

					Notification::make()
						->title("Override removed")
						->body("{$this->record->name} restored to {$originalPlanName}.")
						->success()
						->send();

					$this->refreshFormData(array("plan_id", "original_plan_id", "override_expires_at"));
				}),
			DeleteAction::make(),
		);
	}
}
