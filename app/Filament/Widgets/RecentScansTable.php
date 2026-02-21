<?php

namespace App\Filament\Widgets;

use App\Enums\ScanStatus;
use App\Models\Scan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentScansTable extends TableWidget
{
	protected static ?int $sort = 2;

	protected int | string | array $columnSpan = "full";

	protected static ?string $heading = "Recent Scans";

	public function table(Table $table): Table
	{
		return $table
			->query(Scan::query()->with(array("project.organization", "triggeredBy"))->latest()->limit(10))
			->columns(array(
				TextColumn::make("project.name")
					->label("Project"),
				TextColumn::make("project.organization.name")
					->label("Organization"),
				TextColumn::make("status")
					->badge()
					->formatStateUsing(fn (ScanStatus $state): string => $state->label())
					->color(fn (ScanStatus $state): string => $state->color()),
				TextColumn::make("overall_score")
					->numeric()
					->placeholder("N/A"),
				TextColumn::make("triggeredBy.name")
					->label("By")
					->placeholder("System"),
				TextColumn::make("created_at")
					->dateTime()
					->since(),
			))
			->paginated(false);
	}
}
