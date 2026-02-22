<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
	protected static string $resource = PlanResource::class;

	public function getTitle(): string
	{
		return "Edit Plan: {$this->record->name}";
	}

	public function getSubheading(): ?string
	{
		$priceLabel = $this->record->price_monthly > 0
			? "\${$this->record->price_monthly}/mo"
			: "Free";

		return "{$this->record->slug} — {$priceLabel}";
	}

	public function getBreadcrumbs(): array
	{
		return array(
			PlanResource::getUrl() => "Plans",
			"Edit: {$this->record->name}",
		);
	}

	protected function getHeaderActions(): array
	{
		return array(
			DeleteAction::make(),
		);
	}
}
