<?php

namespace App\Filament\Resources;

use App\Enums\ScanStatus;
use App\Filament\Resources\ScanResource\Pages;
use App\Filament\Resources\ScanResource\RelationManagers;
use App\Models\Scan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScanResource extends Resource
{
	protected static ?string $model = Scan::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

	protected static string | \UnitEnum | null $navigationGroup = "Content";

	public static function canCreate(): bool
	{
		return false;
	}

	public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
	{
		return false;
	}

	public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
	{
		return false;
	}

	public static function form(Schema $schema): Schema
	{
		return $schema->components(array());
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("project.name")
					->label("Project")
					->searchable()
					->sortable(),
				TextColumn::make("project.organization.name")
					->label("Organization")
					->sortable(),
				TextColumn::make("status")
					->badge()
					->formatStateUsing(fn (ScanStatus $state): string => $state->label())
					->color(fn (ScanStatus $state): string => $state->color()),
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
				TextColumn::make("overall_score")
					->label("Scan Score")
					->numeric()
					->sortable()
					->placeholder("N/A"),
				TextColumn::make("pages_crawled")
					->label("Pages")
					->numeric()
					->sortable()
					->placeholder("—"),
				TextColumn::make("scan_duration_ms")
					->label("Duration")
					->formatStateUsing(fn ($state) => $state !== null ? round($state / 1000, 1) . "s" : "N/A")
					->sortable(),
				TextColumn::make("fetcher_used")
					->label("Fetcher")
					->badge()
					->formatStateUsing(fn (?string $state): string => match ($state) {
						"guzzle" => "Guzzle",
						"zyte" => "Zyte API",
						default => "—",
					})
					->color(fn (?string $state): string => match ($state) {
						"guzzle" => "gray",
						"zyte" => "info",
						default => "gray",
					}),
				TextColumn::make("detection_method")
					->label("CMS Detection")
					->badge()
					->formatStateUsing(fn (?string $state): string => match ($state) {
						"whatcms_api" => "WhatCMS API",
						"html_signals" => "HTML Signals",
						"rss_feed" => "RSS Feed",
						default => "—",
					})
					->color(fn (?string $state): string => match ($state) {
						"whatcms_api" => "success",
						"html_signals" => "warning",
						"rss_feed" => "info",
						default => "gray",
					}),
				TextColumn::make("triggeredBy.name")
					->label("Triggered By")
					->placeholder("System"),
				TextColumn::make("created_at")
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

	public static function getRelations(): array
	{
		return array(
			RelationManagers\ModuleResultsRelationManager::class,
		);
	}

	public static function getPages(): array
	{
		return array(
			"index" => Pages\ListScans::route("/"),
			"view" => Pages\ViewScan::route("/{record}"),
		);
	}
}
