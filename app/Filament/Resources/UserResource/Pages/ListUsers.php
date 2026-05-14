<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
	protected static string $resource = UserResource::class;

	protected function getHeaderActions(): array
	{
		return array(
			CreateAction::make(),
		);
	}

	/**
	 * Status tabs above the table — one-click filtering between the same three
	 * states the status column renders. Counts shown as badges so the admin
	 * sees at a glance how many users are stuck in each bucket.
	 */
	public function getTabs(): array
	{
		return array(
			"all" => Tab::make("All"),

			"unverified" => Tab::make("Unverified")
				->modifyQueryUsing(fn (Builder $query) => $query
					->whereNull("email_verified_at")
					->whereNull("deactivated_at"))
				->badge(fn (): int => User::query()
					->whereNull("email_verified_at")
					->whereNull("deactivated_at")
					->count())
				->badgeColor("warning"),

			"active" => Tab::make("Active")
				->modifyQueryUsing(fn (Builder $query) => $query
					->whereNotNull("email_verified_at")
					->whereNull("deactivated_at"))
				->badge(fn (): int => User::query()
					->whereNotNull("email_verified_at")
					->whereNull("deactivated_at")
					->count())
				->badgeColor("success"),

			"deactivated" => Tab::make("Deactivated")
				->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull("deactivated_at"))
				->badge(fn (): int => User::query()->whereNotNull("deactivated_at")->count())
				->badgeColor("danger"),
		);
	}
}
