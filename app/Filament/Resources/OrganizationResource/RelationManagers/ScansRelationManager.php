<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\ScanStatus;
use App\Filament\Resources\ScanResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScansRelationManager extends RelationManager
{
	protected static string $relationship = "scans";

	protected static ?string $title = "Scan History";

	public function form(Schema $schema): Schema
	{
		return $schema->components(array());
	}

	public function table(Table $table): Table
	{
		return $table
			->recordUrl(fn ($record) => ScanResource::getUrl("view", array("record" => $record)))
			->columns(array(
				TextColumn::make("project.url")
					->label("URL Scanned")
					->searchable()
					->sortable()
					->limit(60),
				TextColumn::make("project.name")
					->label("Project")
					->searchable()
					->sortable(),
				TextColumn::make("status")
					->badge()
					->formatStateUsing(fn (ScanStatus $state): string => $state->label())
					->color(fn (ScanStatus $state): string => $state->color()),
				TextColumn::make("overall_score")
					->label("Score")
					->numeric()
					->sortable()
					->placeholder("N/A"),
				TextColumn::make("scan_type")
					->label("Type")
					->badge()
					->formatStateUsing(fn (?string $state): string => match ($state) {
						"crawl" => "Crawl",
						default => "Single",
					})
					->color(fn (?string $state): string => match ($state) {
						"crawl" => "info",
						default => "gray",
					}),
				TextColumn::make("scan_duration_ms")
					->label("Duration")
					->formatStateUsing(fn ($state) => $state !== null ? round($state / 1000, 1) . "s" : "N/A")
					->sortable(),
				TextColumn::make("triggeredBy.name")
					->label("Triggered By")
					->placeholder("System"),
				TextColumn::make("created_at")
					->label("Scanned At")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->filters(array(
				SelectFilter::make("status")
					->options(array(
						ScanStatus::Pending->value => ScanStatus::Pending->label(),
						ScanStatus::Running->value => ScanStatus::Running->label(),
						ScanStatus::Completed->value => ScanStatus::Completed->label(),
						ScanStatus::Failed->value => ScanStatus::Failed->label(),
						ScanStatus::Blocked->value => ScanStatus::Blocked->label(),
					)),
			));
	}
}
