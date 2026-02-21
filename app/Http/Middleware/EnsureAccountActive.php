<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
	/**
	 * Block deactivated users and deactivated organizations from accessing the app.
	 * Logs them out immediately and redirects to login with an explanation.
	 */
	public function handle(Request $request, Closure $next): Response
	{
		$user = $request->user();

		if ($user === null) {
			return $next($request);
		}

		if (!$user->isActive()) {
			Auth::logout();
			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return redirect()->route("login")
				->with("error", "Your account has been deactivated. Please contact support for assistance.");
		}

		$organization = $user->currentOrganization();

		if ($organization === null && !$user->isSuperAdmin()) {
			Auth::logout();
			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return redirect()->route("login")
				->with("error", "You are no longer a member of any organization. Please contact your team owner.");
		}

		if ($organization !== null && !$organization->isActive()) {
			Auth::logout();
			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return redirect()->route("login")
				->with("error", "Your organization has been deactivated. Please contact support for assistance.");
		}

		return $next($request);
	}
}
