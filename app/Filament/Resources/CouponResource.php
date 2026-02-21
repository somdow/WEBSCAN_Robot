<?php

namespace App\Filament\Resources;

use App\Enums\DiscountType;
use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Models\Plan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponResource extends Resource
{
	protected static ?string $model = Coupon::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedTicket;

	protected static string | \UnitEnum | null $navigationGroup = "Billing";

	public static function form(Schema $schema): Schema
	{
		return $schema->components(array(
			Section::make("Coupon Details")->schema(array(
				TextInput::make("code")
					->required()
					->maxLength(50)
					->unique(ignoreRecord: true),
				TextInput::make("stripe_coupon_id")
					->label("Stripe Coupon ID")
					->maxLength(255),
				Select::make("discount_type")
					->options(array(
						DiscountType::Percent->value => DiscountType::Percent->label(),
						DiscountType::Fixed->value => DiscountType::Fixed->label(),
						DiscountType::FreeMonths->value => DiscountType::FreeMonths->label(),
					))
					->required(),
				TextInput::make("discount_value")
					->numeric()
					->required()
					->minValue(1),
			))->columns(2),

			Section::make("Restrictions")->schema(array(
				CheckboxList::make("applicable_plan_ids")
					->label("Applicable Plans")
					->options(Plan::pluck("name", "id")->toArray())
					->helperText("Leave empty to apply to all plans."),
				TextInput::make("max_redemptions")
					->numeric()
					->minValue(1)
					->helperText("Leave empty for unlimited."),
				DateTimePicker::make("expires_at")
					->helperText("Leave empty for no expiry."),
				Toggle::make("is_active")
					->default(true),
			))->columns(2),
		));
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("code")
					->searchable()
					->sortable()
					->copyable(),
				TextColumn::make("discount_type")
					->badge()
					->formatStateUsing(fn (DiscountType $state): string => $state->label())
					->color(fn (DiscountType $state): string => match ($state) {
						DiscountType::Percent => "success",
						DiscountType::Fixed => "info",
						DiscountType::FreeMonths => "warning",
					}),
				TextColumn::make("discount_value")
					->numeric()
					->sortable(),
				TextColumn::make("max_redemptions")
					->numeric()
					->placeholder("Unlimited"),
				TextColumn::make("times_redeemed")
					->numeric(),
				TextColumn::make("expires_at")
					->dateTime()
					->sortable()
					->placeholder("Never"),
				IconColumn::make("is_active")
					->boolean()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->recordActions(array(
				\Filament\Actions\EditAction::make(),
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
			"index" => Pages\ListCoupons::route("/"),
			"create" => Pages\CreateCoupon::route("/create"),
			"edit" => Pages\EditCoupon::route("/{record}/edit"),
		);
	}
}
