<?php

namespace App\Filament\Resources\WaitlistSignups\Pages;

use App\Filament\Resources\WaitlistSignups\WaitlistSignupResource;
use Filament\Resources\Pages\ListRecords;

class ListWaitlistSignups extends ListRecords
{
	protected static string $resource = WaitlistSignupResource::class;

	/**
	 * No header actions — signups are public-form-driven, not admin-created.
	 */
	protected function getHeaderActions(): array
	{
		return array();
	}
}
