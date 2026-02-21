<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
	public function __construct(
		private readonly BillingService $billingService,
	) {}

	/**
	 * Display the user's profile form.
	 */
	public function edit(Request $request): View
	{
		return view("profile.edit", array(
			"user" => $request->user(),
			"canAccessAi" => $request->user()->canAccessAi(),
		));
	}

	/**
	 * Update the user's profile information.
	 */
	public function update(ProfileUpdateRequest $request): RedirectResponse
	{
		$request->user()->fill($request->validated());

		if ($request->user()->isDirty("email")) {
			$request->user()->email_verified_at = null;
		}

		$request->user()->save();

		return Redirect::route("profile.edit")->with("status", "profile-updated");
	}

	/**
	 * Deactivate the user's account and their organization.
	 * Cancels any active Stripe subscription and blocks future login
	 * via the EnsureAccountActive middleware.
	 */
	public function destroy(Request $request): RedirectResponse
	{
		$request->validateWithBag("userDeletion", array(
			"password" => array("required", "current_password"),
		));

		$user = $request->user();
		$organization = $user->currentOrganization();

		$this->cancelStripeSubscription($organization);

		$user->deactivate();

		if ($organization !== null) {
			$organization->deactivate();
		}

		Auth::logout();
		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return Redirect::to("/")->with(
			"success",
			"Your account has been deactivated. You can reactivate it by logging in again."
		);
	}

	/**
	 * Cancel the organization's Stripe subscription if one is active.
	 */
	private function cancelStripeSubscription(?object $organization): void
	{
		if ($organization === null) {
			return;
		}

		if (!$this->billingService->isStripeConfigured()) {
			return;
		}

		try {
			$this->billingService->cancelSubscription($organization);
		} catch (\Throwable $exception) {
			Log::warning("Failed to cancel Stripe subscription during account deactivation", array(
				"organization_id" => $organization->id,
				"error" => $exception->getMessage(),
			));
		}
	}
}
