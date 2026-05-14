<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Rules\SafeExternalUrl;
use App\Services\InvitationService;
use App\Services\OrganizationProvisioningService;
use App\Services\ProjectService;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
	/**
	 * Display the registration view.
	 *
	 * Accepts an optional `?url=` query parameter when the visitor came
	 * from the landing-page hero form. The URL is passed to the view so
	 * the visitor sees a confirmation banner ("we will scan X after signup")
	 * and travels through the form as a hidden field.
	 */
	public function create(Request $request): View
	{
		return view("auth.register", array(
			"pendingScanUrl" => $request->query("url"),
		));
	}

	/**
	 * Handle an incoming registration request.
	 *
	 * Organization provisioning is handled by the CreateOrganizationForNewUser
	 * listener (fires on the Registered event). That listener skips org creation
	 * when a team_invitation_token is in session.
	 *
	 * When a `pending_scan_url` field is present (came from the landing-page
	 * hero form), we create the project up-front but DO NOT dispatch the scan
	 * yet — the scan is kicked off after the user verifies their email, so the
	 * user lands on the project view with a live progress bar instead of a
	 * silently-completed scan. Team invitations take precedence: invited users
	 * are routed to their joined org's dashboard and the URL is discarded.
	 *
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function store(
		Request $request,
		InvitationService $invitationService,
		OrganizationProvisioningService $organizationProvisioningService,
		ProjectService $projectService,
	): RedirectResponse
	{
		$request->merge(array(
			"pending_scan_url" => UrlNormalizer::prependScheme((string) $request->input("pending_scan_url", "")),
		));

		$request->validate(array(
			"name" => array("required", "string", "max:255"),
			"email" => array("required", "string", "lowercase", "email", "max:255", "unique:" . User::class),
			"password" => array("required", "confirmed", Rules\Password::defaults()),
			"pending_scan_url" => array("nullable", "string", "url:http,https", "max:2048", new SafeExternalUrl()),
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
		$organization = $organizationProvisioningService->ensureForUser($user);

		if ($joinedOrganization !== null) {
			return redirect()->route("dashboard")->with("success", "You've joined {$joinedOrganization}!");
		}

		$pendingScanUrl = trim((string) $request->input("pending_scan_url", ""));
		if ($pendingScanUrl !== "") {
			$this->createPendingProject($pendingScanUrl, $organization, $projectService);
		}

		return redirect(route("dashboard", absolute: false));
	}

	/**
	 * Create the visitor's first project from the URL they typed on the landing
	 * page. No scan is dispatched yet — VerifyEmailController kicks off the
	 * first scan after the user verifies their email so the scan progress is
	 * visible in real time.
	 */
	private function createPendingProject(
		string $rawUrl,
		Organization $organization,
		ProjectService $projectService,
	): void
	{
		try {
			$normalizedUrl = $projectService->normalizeUrl($rawUrl);
			$projectName = parse_url($normalizedUrl, PHP_URL_HOST) ?: $normalizedUrl;

			$project = $projectService->createProject($organization, array(
				"name" => $projectName,
				"url" => $normalizedUrl,
				"target_keywords" => null,
			));

			/* Flag this project so VerifyEmailController dispatches the first
			   scan against THIS specific project — not any random scanless
			   project the org might have (e.g. for invited users). */
			$project->forceFill(array("auto_scan_pending" => true))->save();
		} catch (\Throwable $exception) {
			Log::warning("Landing-page pending project creation failed", array(
				"organization_id" => $organization->id,
				"url" => $rawUrl,
				"error" => $exception->getMessage(),
			));
		}
	}
}
