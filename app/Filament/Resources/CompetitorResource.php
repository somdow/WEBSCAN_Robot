<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitorResource\Pages;
use App\Models\Competitor;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompetitorResource extends Resource
{
	protected static ?string $model = Competitor::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedScale;

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
				TextColumn::make("url")
					->label("URL")
					->searchable()
					->limit(50),
				TextColumn::make("name")
					->label("Display Name")
					->searchable()
					->placeholder("—"),
				TextColumn::make("latestScan.overall_score")
					->label("Overall")
					->numeric()
					->sortable()
					->placeholder("N/A"),
				TextColumn::make("latestScan.seo_score")
					->label("SEO")
					->numeric()
					->placeholder("N/A"),
				TextColumn::make("latestScan.health_score")
					->label("Health")
					->numeric()
					->placeholder("N/A"),
				TextColumn::make("latestScan.status")
					->label("Scan Status")
					->badge()
					->formatStateUsing(fn ($state): string => $state?->label() ?? "None")
					->color(fn ($state): string => $state?->color() ?? "gray"),
				TextColumn::make("project.organization.name")
					->label("Organization")
					->sortable(),
				TextColumn::make("created_at")
					->label("Added")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc");
	}

	public static function getRelations(): array
	{
		return array();
	}

	public static function getPages(): array
	{
		return array(
			"index" => Pages\ListCompetitors::route("/"),
			"view" => Pages\ViewCompetitor::route("/{record}"),
		);
	}
}
