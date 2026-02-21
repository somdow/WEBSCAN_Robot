<?php

namespace App\Filament\Resources\ScanResource\RelationManagers;

use App\Enums\ModuleStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModuleResultsRelationManager extends RelationManager
{
	protected static string $relationship = "moduleResults";

	public function form(Schema $schema): Schema
	{
		return $schema->components(array());
	}

	public function table(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("module_key")
					->label("Module")
					->searchable()
					->sortable(),
				TextColumn::make("status")
					->badge()
					->formatStateUsing(fn (ModuleStatus $state): string => $state->label())
					->color(fn (ModuleStatus $state): string => $state->color()),
				TextColumn::make("findings")
					->formatStateUsing(fn ($state) => is_array($state) ? count($state) . " findings" : "None")
					->limit(30),
				TextColumn::make("recommendations")
					->formatStateUsing(fn ($state) => is_array($state) ? count($state) . " items" : "None")
					->limit(30),
			))
			->defaultSort("module_key")
			->recordActions(array(
				\Filament\Actions\ViewAction::make(),
			));
	}
}
