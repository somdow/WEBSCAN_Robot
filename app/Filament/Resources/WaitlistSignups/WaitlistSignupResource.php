<?php

namespace App\Filament\Resources\WaitlistSignups;

use App\Filament\Resources\WaitlistSignups\Pages\EditWaitlistSignup;
use App\Filament\Resources\WaitlistSignups\Pages\ListWaitlistSignups;
use App\Filament\Resources\WaitlistSignups\Schemas\WaitlistSignupForm;
use App\Filament\Resources\WaitlistSignups\Tables\WaitlistSignupsTable;
use App\Models\WaitlistSignup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WaitlistSignupResource extends Resource
{
	protected static ?string $model = WaitlistSignup::class;

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

	protected static string|UnitEnum|null $navigationGroup = "Platform";

	protected static ?string $navigationLabel = "Waitlist";

	protected static ?string $modelLabel = "waitlist signup";

	protected static ?string $pluralModelLabel = "waitlist signups";

	protected static ?int $navigationSort = 30;

	public static function form(Schema $schema): Schema
	{
		return WaitlistSignupForm::configure($schema);
	}

	public static function table(Table $table): Table
	{
		return WaitlistSignupsTable::configure($table);
	}

	public static function getRelations(): array
	{
		return array();
	}

	/**
	 * No create route — waitlist signups only originate from the public
	 * landing-page form. Admins can view, edit (e.g., fix a typo), or
	 * delete records but never insert them manually.
	 */
	public static function getPages(): array
	{
		return array(
			"index" => ListWaitlistSignups::route("/"),
			"edit" => EditWaitlistSignup::route("/{record}/edit"),
		);
	}
}
