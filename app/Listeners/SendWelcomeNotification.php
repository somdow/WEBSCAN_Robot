<?php

namespace App\Listeners;

use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Verified;

/**
 * Send the welcome email AFTER the user verifies their address. Listening on
 * Verified (not Registered) avoids the prior behavior where both the welcome
 * email and the verification email arrived in the same envelope flurry — the
 * user is now welcomed only when they actually open the door.
 */
class SendWelcomeNotification
{
	public function handle(Verified $event): void
	{
		$event->user->notify(new WelcomeNotification());
	}
}
