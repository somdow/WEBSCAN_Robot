<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\ScanStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScansRelationManager extends RelationManager
{
	protected static string $relationship = "scans";

	public function form(Schema $schema): Schema
	{
		return $schema->components(array());
	}

	public function table(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("status")
					->badge()
					->formatStateUsing(fn (ScanStatus $state): string => $state->label())
					->color(fn (ScanStatus $state): string => $state->color()),
				TextColumn::make("overall_score")
					->numeric()
					->sortable()
					->placeholder("N/A"),
				TextColumn::make("scan_duration_ms")
					->label("Duration")
					->formatStateUsing(fn ($state) => $state !== null ? round($state / 1000, 1) . "s" : "N/A"),
				TextColumn::make("triggeredBy.name")
					->label("Triggered By")
					->placeholder("System"),
				TextColumn::make("created_at")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc");
	}
}
