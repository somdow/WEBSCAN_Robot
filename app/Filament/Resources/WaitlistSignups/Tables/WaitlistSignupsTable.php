<?php

namespace App\Filament\Resources\WaitlistSignups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WaitlistSignupsTable
{
	public static function configure(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("email")
					->label("Email")
					->searchable()
					->sortable()
					->copyable(),
				TextColumn::make("desired_url")
					->label("Wanted to scan")
					->searchable()
					->placeholder("—")
					->wrap()
					->limit(60),
				TextColumn::make("ip_address")
					->label("IP")
					->searchable()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make("created_at")
					->label("Signed up")
					->dateTime()
					->since()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->recordActions(array(
				DeleteAction::make(),
			))
			->toolbarActions(array(
				BulkActionGroup::make(array(
					DeleteBulkAction::make(),
				)),
			));
	}
}
