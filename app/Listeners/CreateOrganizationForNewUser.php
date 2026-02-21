<?php

namespace App\Listeners;

use App\Notifications\WelcomeNotification;
use App\Services\OrganizationProvisioningService;
use Illuminate\Auth\Events\Registered;

class CreateOrganizationForNewUser
{
	public function __construct(
		private readonly OrganizationProvisioningService $organizationProvisioningService,
	) {}

	public function handle(Registered $event): void
	{
		$user = $event->user;

		/**
		 * If the user is registering via a team invitation, skip auto-org creation.
		 * The invitation acceptance flow will add them to the inviter's org instead.
		 * RegisteredUserController calls ensureForUser() as a safety net afterward.
		 */
		if (session()->has("team_invitation_token")) {
			return;
		}

		$this->organizationProvisioningService->ensureForUser($user);

		$user->notify(new WelcomeNotification());
	}
}
