<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AdminLoginVerificationService;
use App\Services\InvitationService;
use App\Services\OrganizationProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
	/**
	 * Display the login view.
	 */
	public function create(): View
	{
		return view("auth.login");
	}

	/**
	 * Handle an incoming authentication request.
	 * If the user's account is deactivated, redirect to the reactivation page
	 * instead of the dashboard.
	 */
	public function store(
		LoginRequest $request,
		AdminLoginVerificationService $verificationService,
		OrganizationProvisioningService $organizationProvisioningService,
		InvitationService $invitationService,
	): RedirectResponse
	{
		$request->authenticate();

		$user = Auth::user();

		/* Admin accounts require email verification on every login */
		if ($user->isSuperAdmin()) {
			Auth::logout();
			$request->session()->regenerate();

			$verificationLink = $verificationService->generateVerificationLink($user, "dashboard");
			$verificationService->sendVerificationEmail($user, $verificationLink);

			return redirect()->route("login")->with(
				"status",
				"We sent a verification link to your email. Click it to sign in. The link expires in 15 minutes.",
			);
		}

		if (!$user->isActive()) {
			$userId = $user->id;
			Auth::logout();
			$request->session()->regenerate();
			$request->session()->put("reactivate_user_id", $userId);

			return redirect()->route("reactivate.show");
		}

		$organization = $user->currentOrganization();

		if ($organization !== null && !$organization->isActive()) {
			$userId = $user->id;
			Auth::logout();
			$request->session()->regenerate();
			$request->session()->put("reactivate_user_id", $userId);

			return redirect()->route("reactivate.show");
		}

		$organizationProvisioningService->ensureForUser($user);

		$request->session()->regenerate();

		/* Process any pending team invitation from session */
		$joinedOrganization = $invitationService->processSessionInvitation($user);

		if ($joinedOrganization !== null) {
			return redirect()->route("team.index")->with("success", "You've joined {$joinedOrganization}!");
		}

		return redirect()->intended(route("dashboard", absolute: false));
	}

	/**
	 * Destroy an authenticated session.
	 */
	public function destroy(Request $request): RedirectResponse
	{
		Auth::guard("web")->logout();

		$request->session()->invalidate();

		$request->session()->regenerateToken();

		return redirect("/");
	}
}
