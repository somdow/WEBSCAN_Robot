<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LandingController extends Controller
{
	/**
	 * Render the public marketing landing page.
	 *
	 * Authenticated visitors are redirected to the dashboard so the
	 * marketing surface is only ever shown to anonymous prospects.
	 */
	public function __invoke(Request $request): View|RedirectResponse
	{
		if ($request->user() !== null) {
			return redirect()->route("dashboard");
		}

		/* The idle-timer JS in resources/js/app.js appends ?session_expired=1
		   when it kicks an inactive user back here. Flash a one-line banner so
		   they know what just happened. */
		if ($request->query("session_expired") === "1" && !$request->session()->has("status")) {
			$request->session()->flash("status", "Your session expired. You have been signed out — please log back in if you would like to continue.");
		}

		$plans = Plan::public()->ordered()->get();
		$registrationEnabled = Setting::getValue("registration_enabled", "0") === "1";

		return view("welcome", array(
			"plans" => $plans,
			"registrationEnabled" => $registrationEnabled,
		));
	}
}
