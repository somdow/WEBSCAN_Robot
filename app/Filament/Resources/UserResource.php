<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserResource extends Resource
{
	protected static ?string $model = User::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

	protected static string | \UnitEnum | null $navigationGroup = "Platform";

	protected static ?int $navigationSort = 20;

	public static function form(Schema $schema): Schema
	{
		return $schema->components(array(
			Section::make("User Information")->schema(array(
				TextInput::make("name")
					->required()
					->maxLength(255),
				TextInput::make("email")
					->email()
					->required()
					->maxLength(255)
					->unique(ignoreRecord: true),
				TextInput::make("password")
					->password()
					->required(fn (string $operation): bool => $operation === "create")
					->minLength(8)
					->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
					->dehydrated(fn (?string $state): bool => filled($state))
					->formatStateUsing(fn (string $operation): ?string => $operation === "edit" ? "" : null)
					->helperText(fn (string $operation): ?string => $operation === "edit"
						? "Leave empty to keep current password."
						: null
					)
					->hiddenOn("view"),
				Toggle::make("is_super_admin")
					->label("Super Admin")
					->hiddenOn("view"),
			))->columns(2),
		));
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns(array(
				TextColumn::make("name")
					->searchable()
					->sortable(),
				TextColumn::make("email")
					->searchable()
					->sortable(),
				IconColumn::make("is_super_admin")
					->label("Admin")
					->boolean()
					->sortable(),
				TextColumn::make("status")
					->label("Status")
					->badge()
					->state(fn (User $record): string => $record->isActive() ? "Active" : "Deactivated")
					->color(fn (string $state): string => $state === "Active" ? "success" : "danger"),
				TextColumn::make("organizations.name")
					->label("Organization")
					->url(fn (User $record): ?string => $record->organizations->first()
						? OrganizationResource::getUrl("edit", array("record" => $record->organizations->first()))
						: null
					)
					->color("primary")
					->sortable(),
				TextColumn::make("created_at")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->filters(array(
				TernaryFilter::make("is_super_admin")
					->label("Super Admin"),
				TernaryFilter::make("active")
					->label("Status")
					->queries(
						true: fn ($query) => $query->whereNull("deactivated_at"),
						false: fn ($query) => $query->whereNotNull("deactivated_at"),
					),
				TernaryFilter::make("email_verified")
					->label("Email Verified")
					->queries(
						true: fn ($query) => $query->whereNotNull("email_verified_at"),
						false: fn ($query) => $query->whereNull("email_verified_at"),
					),
			))
			->recordActions(array(
				ActionGroup::make(array(
					\Filament\Actions\EditAction::make(),
					Action::make("sendPasswordReset")
						->label("Send Password Reset")
						->icon(Heroicon::OutlinedEnvelope)
						->color("warning")
						->requiresConfirmation()
						->modalHeading("Send Password Reset Link")
						->modalDescription(fn (User $record) => "A password reset link will be sent to {$record->email}.")
						->action(function (User $record) {
							$status = Password::sendResetLink(
								array("email" => $record->email)
							);

							if ($status === Password::RESET_LINK_SENT) {
								Notification::make()
									->title("Password reset link sent")
									->success()
									->send();
							} else {
								Notification::make()
									->title("Failed to send reset link")
									->body(__($status))
									->danger()
									->send();
							}
						}),
					Action::make("deactivate")
						->label("Deactivate")
						->icon(Heroicon::OutlinedNoSymbol)
						->color("danger")
						->requiresConfirmation()
						->action(fn (User $record) => $record->deactivate())
						->hidden(fn (User $record): bool => !$record->isActive() || $record->id === auth()->id()),
					Action::make("reactivate")
						->label("Reactivate")
						->icon(Heroicon::OutlinedCheckCircle)
						->color("success")
						->requiresConfirmation()
						->action(fn (User $record) => $record->reactivate())
						->hidden(fn (User $record): bool => $record->isActive()),
					\Filament\Actions\DeleteAction::make()
						->hidden(fn (User $record): bool => $record->id === auth()->id()),
				))
					->icon(Heroicon::OutlinedCog6Tooth)
					->tooltip("Actions"),
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
			"index" => Pages\ListUsers::route("/"),
			"create" => Pages\CreateUser::route("/create"),
			"edit" => Pages\EditUser::route("/{record}/edit"),
		);
	}
}
