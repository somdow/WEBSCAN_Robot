<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

		return view("welcome", array(
			"plans" => $this->loadPublicPlans(),
			"registrationEnabled" => $this->isRegistrationEnabled(),
		));
	}

	/**
	 * Fetch publicly-listed plans, ordered cheapest first. Returns an empty
	 * collection if the plans table is missing — keeps the landing page
	 * renderable on fresh installs before migrations have run.
	 */
	private function loadPublicPlans(): Collection
	{
		try {
			return Plan::public()->ordered()->get();
		} catch (\Throwable $exception) {
			Log::warning("Landing plan lookup failed — rendering with empty plans", array(
				"error" => $exception->getMessage(),
			));

			return collect();
		}
	}

	/**
	 * Read the registration_enabled setting, defaulting to false (waitlist mode)
	 * if the settings table is missing. Same fresh-install safety net.
	 */
	private function isRegistrationEnabled(): bool
	{
		try {
			return Setting::getValue("registration_enabled", "0") === "1";
		} catch (\Throwable $exception) {
			Log::warning("Landing settings lookup failed — assuming registration closed", array(
				"error" => $exception->getMessage(),
			));

			return false;
		}
	}
}
