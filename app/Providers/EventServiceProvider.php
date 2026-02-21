<?php

namespace App\Providers;

use App\Listeners\CreateOrganizationForNewUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * Explicit listener registration — single source of truth.
	 */
	protected $listen = array(
		Registered::class => array(
			SendEmailVerificationNotification::class,
			CreateOrganizationForNewUser::class,
		),
	);

	/**
	 * Override: prevent the base class from adding a duplicate
	 * SendEmailVerificationNotification listener (it's already in $listen).
	 */
	protected function configureEmailVerification(): void
	{
		// Already registered above — do not add again.
	}

	/**
	 * Override: prevent auto-discovery from finding listeners in app/Listeners
	 * and double-registering them alongside the explicit $listen array.
	 */
	protected function discoveredEvents(): array
	{
		return array();
	}
}
