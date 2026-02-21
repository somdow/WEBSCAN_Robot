<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
	protected static string $relationship = "projects";

	public function form(Schema $schema): Schema
	{
		return $schema->components(array());
	}

	public function table(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("name")
					->searchable()
					->sortable(),
				TextColumn::make("url")
					->limit(40),
				TextColumn::make("scans_count")
					->counts("scans")
					->label("Scans")
					->sortable(),
				TextColumn::make("created_at")
					->dateTime()
					->sortable(),
			));
	}
}
