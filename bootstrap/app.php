<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
	->withEvents(discover: false)
	->withRouting(
		web: __DIR__ . "/../routes/web.php",
		api: __DIR__ . "/../routes/api.php",
		commands: __DIR__ . "/../routes/console.php",
		health: "/up",
	)
	->withMiddleware(function (Middleware $middleware): void {
		$middleware->trustProxies(at: "*");

		$middleware->alias(array(
			"enforce.plan" => \App\Http\Middleware\EnforcePlanLimits::class,
			"ensure.active" => \App\Http\Middleware\EnsureAccountActive::class,
		));

		$middleware->validateCsrfTokens(except: array(
			"stripe/webhook",
		));
	})
	->withExceptions(function (Exceptions $exceptions): void {
		/**
		 * CSRF token mismatch (HTTP 419) — happens when the user's session
		 * expires while a page is still open, or when they submit a stale
		 * logout form. Instead of the default "Page Expired" view, we sign
		 * the user out and bounce them to the landing page so they can log
		 * back in.
		 */
		$exceptions->render(function (\Illuminate\Session\TokenMismatchException $exception, \Illuminate\Http\Request $request) {
			/* Log out every stateful guard (web, admin, etc.) so adding a new
			   guard later does not leak an authenticated session through the
			   419 path. Token-based guards (sanctum, etc.) do not implement
			   logout() and are skipped by the method_exists check. */
			foreach (array_keys(config("auth.guards", array())) as $guardName) {
				$guard = \Illuminate\Support\Facades\Auth::guard($guardName);
				if (method_exists($guard, "logout") && $guard->check()) {
					$guard->logout();
				}
			}

			if ($request->hasSession()) {
				$request->session()->invalidate();
				$request->session()->regenerateToken();
			}

			if ($request->wantsJson() || $request->expectsJson()) {
				return response()->json(array(
					"success" => false,
					"error" => "Your session expired. Please refresh the page and try again.",
					"code" => 419,
				), 419);
			}

			return redirect()
				->route("home")
				->with("status", "Your session expired. You have been signed out — please log back in if you would like to continue.");
		});
	})->create();
