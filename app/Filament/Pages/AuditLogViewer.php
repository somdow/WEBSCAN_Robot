<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogViewer extends Page implements HasTable
{
	use InteractsWithTable;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

	protected static string | \UnitEnum | null $navigationGroup = "Monitoring";

	protected static ?string $navigationLabel = "Audit Log";

	protected static ?string $title = "Audit Log";

	protected static ?int $navigationSort = 10;

	protected string $view = "filament.pages.audit-log-viewer";

	public function table(Table $table): Table
	{
		return $table
			->query(AuditLog::query()->with(array("user")))
			->columns(array(
				TextColumn::make("user.name")
					->label("User")
					->searchable()
					->sortable()
					->placeholder("System"),
				TextColumn::make("action")
					->badge()
					->color(fn (string $state): string => match ($state) {
						"created" => "success",
						"updated" => "info",
						"deleted" => "danger",
						"login" => "warning",
						"logout" => "gray",
						default => "gray",
					}),
				TextColumn::make("auditable_type")
					->label("Model")
					->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : "N/A")
					->sortable(),
				TextColumn::make("auditable_id")
					->label("Record ID"),
				TextColumn::make("ip_address")
					->label("IP")
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make("created_at")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->filters(array(
				SelectFilter::make("user_id")
					->label("User")
					->options(fn () => User::pluck("name", "id")->toArray()),
				SelectFilter::make("action")
					->options(array(
						"created" => "Created",
						"updated" => "Updated",
						"deleted" => "Deleted",
						"login" => "Login",
						"logout" => "Logout",
					)),
				SelectFilter::make("auditable_type")
					->label("Model Type")
					->options(fn () => AuditLog::distinct()
						->pluck("auditable_type")
						->filter()
						->mapWithKeys(fn ($type) => array($type => class_basename($type)))
						->toArray()),
			))
			->recordActions(array(
				\Filament\Actions\ViewAction::make()
					->modalContent(fn (AuditLog $record) => view("filament.pages.audit-log-detail", array("record" => $record))),
			));
	}
}
