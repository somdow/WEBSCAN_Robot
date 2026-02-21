<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
	protected static ?string $model = Project::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedFolder;

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
				TextColumn::make("name")
					->searchable()
					->sortable(),
				TextColumn::make("url")
					->searchable()
					->url(fn ($record) => $record->url, shouldOpenInNewTab: true)
					->limit(40),
				TextColumn::make("organization.name")
					->label("Organization")
					->sortable(),
				TextColumn::make("scan_schedule")
					->badge()
					->formatStateUsing(fn ($state) => $state?->label() ?? "None")
					->color(fn ($state) => $state !== null ? "info" : "gray"),
				TextColumn::make("scans_count")
					->counts("scans")
					->label("Scans")
					->sortable(),
				TextColumn::make("target_keywords")
					->formatStateUsing(fn ($state) => is_array($state) ? implode(", ", $state) : $state)
					->limit(30)
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make("created_at")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->recordActions(array(
				\Filament\Actions\ViewAction::make(),
			));
	}

	public static function getRelations(): array
	{
		return array(
			RelationManagers\ScansRelationManager::class,
		);
	}

	public static function getPages(): array
	{
		return array(
			"index" => Pages\ListProjects::route("/"),
			"view" => Pages\ViewProject::route("/{record}"),
		);
	}
}
