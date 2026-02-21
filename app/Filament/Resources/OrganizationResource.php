<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Filament\Resources\OrganizationResource\RelationManagers;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\PlanOverrideService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class OrganizationResource extends Resource
{
	protected static ?string $model = Organization::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBuildingOffice;

	protected static string | \UnitEnum | null $navigationGroup = "Platform";

	protected static ?int $navigationSort = 10;

	public static function form(Schema $schema): Schema
	{
		return $schema->components(array(
			Section::make("Organization Details")->schema(array(
				TextInput::make("name")
					->required()
					->maxLength(255)
					->live(onBlur: true)
					->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
						if ($operation === "create" && $state) {
							$set("slug", Str::slug($state));
						}
					}),
				TextInput::make("slug")
					->required()
					->maxLength(255)
					->unique(ignoreRecord: true)
					->disabledOn("edit"),
				Select::make("plan_id")
					->label("Plan")
					->options(Plan::pluck("name", "id")->toArray())
					->required()
					->disabledOn("edit")
					->helperText("Plan changes on existing organizations must be done via the 'Override Plan' action for audit tracking."),
			))->columns(2),

			Section::make("Stripe")->schema(array(
				TextInput::make("stripe_id")
					->label("Stripe Customer ID")
					->disabled(),
				TextInput::make("pm_type")
					->label("Payment Method Type")
					->disabled(),
				TextInput::make("pm_last_four")
					->label("Card Last Four")
					->disabled(),
			))->columns(3)->hiddenOn("create"),

			Section::make("Plan Override History")
				->schema(array(
					\Filament\Forms\Components\Placeholder::make("plan_override_history")
						->label("")
						->content(fn (?Organization $record) => self::renderOverrideHistory($record)),
				))
				->hiddenOn("create"),
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
					->searchable(),
				TextColumn::make("plan.name")
					->badge()
					->sortable(),
				TextColumn::make("last_override_at")
					->label("Last Override")
					->state(fn (Organization $record): string => self::resolveLastOverrideAt($record))
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make("status")
					->label("Status")
					->badge()
					->state(fn (Organization $record): string => $record->isActive() ? "Active" : "Deactivated")
					->color(fn (string $state): string => $state === "Active" ? "success" : "danger"),
				TextColumn::make("users_count")
					->counts("users")
					->label("Members")
					->sortable(),
				TextColumn::make("projects_count")
					->counts("projects")
					->label("Projects")
					->sortable(),
				TextColumn::make("stripe_id")
					->label("Stripe ID")
					->placeholder("None")
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make("created_at")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->filters(array(
				TernaryFilter::make("active")
					->label("Status")
					->queries(
						true: fn ($query) => $query->whereNull("deactivated_at"),
						false: fn ($query) => $query->whereNotNull("deactivated_at"),
					),
			))
			->recordActions(array(
				ActionGroup::make(array(
					\Filament\Actions\EditAction::make(),
					Action::make("overridePlan")
						->label("Override Plan")
						->icon(Heroicon::OutlinedGift)
						->color("warning")
						->fillForm(fn (Organization $record): array => array(
							"target_plan_id" => $record->plan_id,
						))
						->schema(array(
							Select::make("target_plan_id")
								->label("New Plan")
								->options(Plan::query()->ordered()->pluck("name", "id")->toArray())
								->required(),
							Textarea::make("reason")
								->label("Reason / Notes")
								->rows(3)
								->maxLength(1000)
								->required(),
						))
						->action(function (Organization $record, array $data): void {
							$targetPlan = Plan::findOrFail($data["target_plan_id"]);
							$actor = auth()->user();
							abort_unless($actor instanceof \App\Models\User, 403);

							app(PlanOverrideService::class)->applyOverride(
								$record,
								$targetPlan,
								$actor,
								$data["reason"] ?? null,
							);

							Notification::make()
								->title("Plan overridden")
								->body("{$record->name} is now on {$targetPlan->name}.")
								->success()
								->send();
						}),
					Action::make("deactivate")
						->label("Deactivate")
						->icon(Heroicon::OutlinedNoSymbol)
						->color("danger")
						->requiresConfirmation()
						->action(fn (Organization $record) => $record->deactivate())
						->hidden(fn (Organization $record): bool => !$record->isActive()),
					Action::make("reactivate")
						->label("Reactivate")
						->icon(Heroicon::OutlinedCheckCircle)
						->color("success")
						->requiresConfirmation()
						->action(fn (Organization $record) => $record->reactivate())
						->hidden(fn (Organization $record): bool => $record->isActive()),
					\Filament\Actions\DeleteAction::make(),
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

	public static function getRelations(): array
	{
		return array(
			RelationManagers\UsersRelationManager::class,
			RelationManagers\ProjectsRelationManager::class,
		);
	}

	public static function getPages(): array
	{
		return array(
			"index" => Pages\ListOrganizations::route("/"),
			"create" => Pages\CreateOrganization::route("/create"),
			"edit" => Pages\EditOrganization::route("/{record}/edit"),
		);
	}

	private static function resolveLastOverrideAt(Organization $record): string
	{
		$lastOverride = AuditLog::query()
			->where("action", "organization.plan_override")
			->where("auditable_type", Organization::class)
			->where("auditable_id", $record->id)
			->latest("created_at")
			->value("created_at");

		if ($lastOverride === null) {
			return "Never";
		}

		return (string) \Illuminate\Support\Carbon::parse($lastOverride)->format("M j, Y g:i A");
	}

	private static function renderOverrideHistory(?Organization $record): HtmlString
	{
		if ($record === null) {
			return new HtmlString("No organization selected.");
		}

		$logs = AuditLog::query()
			->with("user")
			->where("action", "organization.plan_override")
			->where("auditable_type", Organization::class)
			->where("auditable_id", $record->id)
			->latest("created_at")
			->limit(10)
			->get();

		if ($logs->isEmpty()) {
			return new HtmlString('<span style="color:#6b7280;">No plan overrides recorded for this organization.</span>');
		}

		$rows = $logs->map(function (AuditLog $log): string {
			$fromPlan = $log->old_values["plan_name"] ?? "Unknown";
			$toPlan = $log->new_values["plan_name"] ?? "Unknown";
			$reason = $log->new_values["reason"] ?? "";
			$actor = $log->user?->email ?? "System";
			$timestamp = $log->created_at?->format("M j, Y g:i A") ?? "Unknown time";

			$reasonHtml = $reason !== ""
				? '<div style="color:#4b5563; margin-top:2px;">Reason: ' . e($reason) . '</div>'
				: "";

			return '<li style="padding:8px 0; border-bottom:1px solid #e5e7eb;">'
				. '<div><strong>' . e($fromPlan) . '</strong> &rarr; <strong>' . e($toPlan) . '</strong></div>'
				. '<div style="color:#6b7280;">By ' . e($actor) . ' on ' . e($timestamp) . '</div>'
				. $reasonHtml
				. '</li>';
		})->implode("");

		return new HtmlString('<ul style="margin:0; padding-left:0; list-style:none;">' . $rows . '</ul>');
	}
}
