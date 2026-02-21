<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
	protected static string $resource = UserResource::class;

	/**
	 * Extract is_super_admin from form data and set it directly
	 * to avoid mass assignment on a guarded attribute.
	 */
	protected function handleRecordUpdate(Model $record, array $data): Model
	{
		$isSuperAdmin = (bool) ($data["is_super_admin"] ?? false);
		unset($data["is_super_admin"]);

		$record->update($data);
		$record->is_super_admin = $isSuperAdmin;
		$record->save();

		return $record;
	}

	protected function getHeaderActions(): array
	{
		return array(
			Action::make("sendPasswordReset")
				->label("Send Password Reset")
				->icon(Heroicon::OutlinedEnvelope)
				->color("warning")
				->requiresConfirmation()
				->modalHeading("Send Password Reset Link")
				->modalDescription(fn () => "A password reset link will be sent to {$this->record->email}.")
				->action(function () {
					$status = Password::sendResetLink(
						array("email" => $this->record->email)
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
			DeleteAction::make(),
		);
	}
}
