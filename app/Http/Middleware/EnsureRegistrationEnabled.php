<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationEnabled
{
	public function handle(Request $request, Closure $next): Response
	{
		$registrationEnabled = Setting::getValue("registration_enabled", "0");

		if ($registrationEnabled !== "1") {
			return redirect()->route("login")->with("status", "Registration is currently closed.");
		}

		return $next($request);
	}
}
