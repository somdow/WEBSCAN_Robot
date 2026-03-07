<?php

namespace App\Providers;

use App\Listeners\CreateOrganizationForNewUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * Explicit listener registration.
	 *
	 * SendEmailVerificationNotification is NOT listed here — the framework's
	 * base EventServiceProvider (auto-registered by Application::configure())
	 * adds it via configureEmailVerification(). Listing it here would cause a
	 * duplicate because both this subclass and the base instance boot independently.
	 */
	protected $listen = array(
		Registered::class => array(
			CreateOrganizationForNewUser::class,
		),
	);

	/**
	 * Disable auto-discovery so listeners are only registered via $listen above.
	 * Laravel 12 discovers listeners in app/Listeners by convention, which causes
	 * duplicates when the same listener is also in the explicit $listen array.
	 */
	public function shouldDiscoverEvents(): bool
	{
		return false;
	}

	/**
	 * Prevent this subclass from adding SendEmailVerificationNotification again.
	 * The framework's base EventServiceProvider instance already adds it via
	 * its own configureEmailVerification() during boot.
	 */
	protected function configureEmailVerification(): void
	{
		// Intentionally empty — base class instance handles this.
	}
}
