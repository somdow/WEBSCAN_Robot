<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\OrganizationRole;
use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
	protected static string $relationship = "users";

	public function form(Schema $schema): Schema
	{
		return $schema->components(array(
			Select::make("role")
				->options(array(
					OrganizationRole::Owner->value => OrganizationRole::Owner->label(),
					OrganizationRole::Admin->value => OrganizationRole::Admin->label(),
					OrganizationRole::Member->value => OrganizationRole::Member->label(),
					OrganizationRole::Viewer->value => OrganizationRole::Viewer->label(),
				))
				->required(),
		));
	}

	public function table(Table $table): Table
	{
		return $table
			->recordUrl(fn ($record) => UserResource::getUrl("edit", array("record" => $record)))
			->columns(array(
				TextColumn::make("name")
					->searchable()
					->sortable(),
				TextColumn::make("email")
					->searchable(),
				TextColumn::make("role")
					->badge()
					->state(fn ($record) => $record->pivot->role)
					->color(fn (string $state): string => match ($state) {
						"owner" => "warning",
						"admin" => "info",
						"member" => "success",
						default => "gray",
					}),
				TextColumn::make("created_at")
					->label("Joined")
					->dateTime()
					->sortable(),
			))
			->recordActions(array(
				ActionGroup::make(array(
					Action::make("editUser")
						->label("Edit")
						->icon(Heroicon::OutlinedPencilSquare)
						->url(fn ($record) => UserResource::getUrl("edit", array("record" => $record))),
					\Filament\Actions\EditAction::make()
						->label("Change Role")
						->icon(Heroicon::OutlinedArrowsRightLeft),
					\Filament\Actions\DetachAction::make(),
					\Filament\Actions\DeleteAction::make(),
				))
					->icon(Heroicon::OutlinedCog6Tooth)
					->tooltip("Actions"),
			))
			->toolbarActions(array(
				\Filament\Actions\AttachAction::make(),
			));
	}
}
