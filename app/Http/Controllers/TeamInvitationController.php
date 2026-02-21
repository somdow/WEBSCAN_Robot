<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamInvitationController extends Controller
{
	public function __construct(
		private InvitationService $invitationService,
	) {}

	/**
	 * Handle a magic link click. If the user is logged in, accept immediately.
	 * If not, show a landing page with options to log in or register.
	 */
	public function accept(Request $request, string $token): View|RedirectResponse
	{
		$invitation = TeamInvitation::where("token", $token)->first();

		if ($invitation === null) {
			return redirect()->route("login")->withErrors(array(
				"invitation" => "This invitation link is invalid.",
			));
		}

		if ($invitation->isAccepted()) {
			if ($request->user() !== null) {
				return redirect()->route("dashboard")->with("info", "This invitation has already been accepted.");
			}

			return redirect()->route("login")->with("info", "This invitation has already been accepted. Please log in.");
		}

		if ($invitation->isExpired()) {
			return redirect()->route("login")->withErrors(array(
				"invitation" => "This invitation has expired. Ask the team owner to send a new one.",
			));
		}

		/* Logged-in user: accept immediately */
		if ($request->user() !== null) {
			return $this->processAcceptance($invitation, $request->user());
		}

		/* Not logged in: store token in session and show landing page */
		session()->put("team_invitation_token", $token);

		return view("team.accept-invitation", array(
			"invitation" => $invitation,
			"organizationName" => $invitation->organization->name,
			"inviterName" => $invitation->inviter->name,
		));
	}

	/**
	 * Accept the invitation and redirect to the dashboard.
	 */
	private function processAcceptance(TeamInvitation $invitation, User $user): RedirectResponse
	{
		try {
			$this->invitationService->acceptInvitation($invitation->token, $user);
			$organizationName = $invitation->organization->name;

			return redirect()->route("dashboard")->with(
				"success",
				"You've joined {$organizationName}!",
			);
		} catch (\InvalidArgumentException $exception) {
			return redirect()->route("dashboard")->withErrors(array(
				"invitation" => $exception->getMessage(),
			));
		}
	}
}
