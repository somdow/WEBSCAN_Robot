<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\OrganizationProvisioningService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
	/**
	 * Display the registration view.
	 */
	public function create(): View
	{
		return view("auth.register");
	}

	/**
	 * Handle an incoming registration request.
	 *
	 * Organization provisioning is handled by the CreateOrganizationForNewUser
	 * listener (fires on the Registered event). That listener skips org creation
	 * when a team_invitation_token is in session.
	 *
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function store(
		Request $request,
		InvitationService $invitationService,
		OrganizationProvisioningService $organizationProvisioningService,
	): RedirectResponse
	{
		$request->validate(array(
			"name" => array("required", "string", "max:255"),
			"email" => array("required", "string", "lowercase", "email", "max:255", "unique:" . User::class),
			"password" => array("required", "confirmed", Rules\Password::defaults()),
		));

		$user = User::create(array(
			"name" => $request->name,
			"email" => $request->email,
			"password" => Hash::make($request->password),
		));

		event(new Registered($user));

		Auth::login($user);

		/* Process any pending team invitation from session */
		$joinedOrganization = $invitationService->processSessionInvitation($user);

		/**
		 * Safety net: ensure every user always has at least one organization.
		 * If the listener skipped org creation (invitation token in session) but the
		 * invitation failed (expired, invalid, plan full), the user would be orphaned.
		 * ensureForUser() is idempotent — it's a no-op if the user already has an org.
		 */
		$organizationProvisioningService->ensureForUser($user);

		if ($joinedOrganization !== null) {
			return redirect()->route("dashboard")->with("success", "You've joined {$joinedOrganization}!");
		}

		return redirect(route("dashboard", absolute: false));
	}
}
