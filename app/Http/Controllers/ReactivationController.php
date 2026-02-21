<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReactivationController extends Controller
{
	/**
	 * Show the reactivation confirmation page.
	 * Only accessible when the session contains a reactivate_user_id
	 * (set during login when a deactivated user authenticates).
	 */
	public function show(Request $request): View|RedirectResponse
	{
		if (!$request->session()->has("reactivate_user_id")) {
			return redirect()->route("login");
		}

		return view("auth.reactivate");
	}

	/**
	 * Reactivate the user and their organization.
	 * Clears deactivated_at, downgrades org to Free plan, and logs the user in.
	 */
	public function store(Request $request): RedirectResponse
	{
		$userId = $request->session()->pull("reactivate_user_id");

		if ($userId === null) {
			return redirect()->route("login");
		}

		$user = User::find($userId);

		if ($user === null) {
			return redirect()->route("login")
				->with("error", "Account not found.");
		}

		$user->reactivate();

		$organization = $user->currentOrganization();

		if ($organization !== null) {
			$organization->reactivate();

			$freePlan = Plan::where("slug", "free")->first();

			if ($freePlan !== null) {
				$organization->update(array("plan_id" => $freePlan->id));
			}
		}

		Auth::login($user);
		$request->session()->regenerate();

		return redirect()->route("dashboard")
			->with("success", "Welcome back! Your account has been reactivated on the Free plan.");
	}
}
