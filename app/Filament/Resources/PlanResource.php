<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanResource extends Resource
{
	protected static ?string $model = Plan::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCreditCard;

	protected static string | \UnitEnum | null $navigationGroup = "Platform";

	protected static ?int $navigationSort = 30;

	public static function form(Schema $schema): Schema
	{
		return $schema->components(array(
			Section::make("Identity")->schema(array(
				TextInput::make("name")
					->required()
					->maxLength(255),
				TextInput::make("slug")
					->required()
					->maxLength(255)
					->unique(ignoreRecord: true)
					->disabled(fn (?Plan $record): bool => $record?->slug === "free")
					->dehydrated()
					->helperText(fn (?Plan $record): ?string => $record?->slug === "free" ? "The Free plan slug cannot be changed." : null),
				TextInput::make("description")
					->maxLength(500),
				TextInput::make("sort_order")
					->numeric()
					->default(0),
				Toggle::make("is_public")
					->label("Publicly visible")
					->default(true),
			))->columns(2),

			Section::make("Pricing")->schema(array(
				TextInput::make("price_monthly")
					->numeric()
					->prefix("$")
					->default(0),
				TextInput::make("price_annual")
					->numeric()
					->prefix("$")
					->default(0),
				TextInput::make("stripe_monthly_price_id")
					->label("Stripe Monthly Price ID")
					->maxLength(255),
				TextInput::make("stripe_annual_price_id")
					->label("Stripe Annual Price ID")
					->maxLength(255),
			))->columns(2),

			Section::make("Volume Limits")
				->description("These are the only differentiators between plans. All features are available on every tier.")
				->schema(array(
				TextInput::make("max_users")
					->label("Team Members")
					->numeric()
					->required()
					->default(1),
				TextInput::make("max_projects")
					->numeric()
					->required()
					->default(1),
				TextInput::make("max_scans_per_month")
					->numeric()
					->required()
					->default(5),
				TextInput::make("max_pages_per_scan")
					->label("Pages per Scan")
					->numeric()
					->required()
					->default(1)
					->helperText("1 = single-page scan, >1 = multi-page crawl"),
				TextInput::make("max_crawl_depth")
					->label("Crawl Depth")
					->numeric()
					->required()
					->default(3)
					->helperText("Max link-clicks deep from homepage (0 = homepage only)"),
				TextInput::make("max_additional_pages")
					->label("Additional Pages")
					->numeric()
					->default(50),
				TextInput::make("max_competitors")
					->numeric()
					->default(5),
				TextInput::make("scan_history_days")
					->numeric()
					->default(7)
					->helperText("36500 = effectively unlimited"),
			))->columns(3),

			Section::make("Feature Flags")->schema(array(
				Toggle::make("feature_flags.white_label")
					->label("White Label"),
				))->columns(3),
		));
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("name")
					->searchable()
					->sortable(),
				TextColumn::make("slug")
					->badge()
					->color(fn (string $state): string => $state === "free" ? "success" : "gray")
					->searchable(),
				TextColumn::make("price_monthly")
					->money("usd")
					->sortable(),
				TextColumn::make("max_users")
					->label("Team")
					->numeric()
					->sortable(),
				TextColumn::make("max_projects")
					->label("Projects")
					->numeric()
					->sortable(),
				TextColumn::make("max_scans_per_month")
					->label("Scans/mo")
					->numeric()
					->sortable(),
				TextColumn::make("scan_history_days")
					->label("History")
					->formatStateUsing(fn (int $state): string => $state >= 36500 ? "Unlimited" : "{$state}d")
					->sortable(),
				IconColumn::make("is_public")
					->boolean()
					->sortable(),
			))
			->defaultSort("sort_order")
			->recordActions(array(
				\Filament\Actions\EditAction::make(),
				\Filament\Actions\DeleteAction::make()
					->hidden(fn (Plan $record): bool => $record->slug === "free"),
			))
			->toolbarActions(array(
				\Filament\Actions\BulkActionGroup::make(array(
					\Filament\Actions\DeleteBulkAction::make(),
				)),
			));
	}

	public static function getPages(): array
	{
		return array(
			"index" => Pages\ListPlans::route("/"),
			"create" => Pages\CreatePlan::route("/create"),
			"edit" => Pages\EditPlan::route("/{record}/edit"),
		);
	}
}
