<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\OrganizationProvisioningService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
	protected static string $resource = UserResource::class;

	/**
	 * Extract is_super_admin from form data and set it directly
	 * to avoid mass assignment on a guarded attribute.
	 */
	protected function handleRecordCreation(array $data): Model
	{
		$isSuperAdmin = (bool) ($data["is_super_admin"] ?? false);
		unset($data["is_super_admin"]);

		$record = static::getModel()::create($data);
		$record->is_super_admin = $isSuperAdmin;
		$record->save();
		app(OrganizationProvisioningService::class)->ensureForUser($record);

		return $record;
	}
}
