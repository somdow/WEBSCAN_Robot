<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamController extends Controller
{
	public function __construct(
		private InvitationService $invitationService,
	) {}

	/**
	 * Display the team management page with members and pending invitations.
	 */
	public function index(Request $request): View
	{
		$organization = $request->user()->currentOrganization();
		$isOwner = $request->user()->isOrganizationOwner();

		$members = $organization->users()
			->orderByPivot("created_at", "asc")
			->get();

		$pendingInvitations = $isOwner
			? TeamInvitation::where("organization_id", $organization->id)
				->pending()
				->with("inviter")
				->orderBy("created_at", "desc")
				->get()
			: collect();

		$maxUsers = $organization->plan?->max_users ?? 1;
		$currentCount = $members->count();

		$ownerRole = OrganizationRole::Owner->value;

		return view("team.index", compact(
			"organization",
			"members",
			"pendingInvitations",
			"isOwner",
			"maxUsers",
			"currentCount",
			"ownerRole",
		));
	}

	/**
	 * Send a team invitation (Owner only).
	 */
	public function invite(Request $request): RedirectResponse
	{
		$this->authorizeOwner($request);

		$validated = $request->validate(array(
			"email" => array("required", "email", "max:255"),
		));

		$organization = $request->user()->currentOrganization();

		try {
			$this->invitationService->createInvitation(
				$organization,
				$validated["email"],
				$request->user(),
			);

			return back()->with("success", "Invitation sent to {$validated["email"]}.");
		} catch (\InvalidArgumentException $exception) {
			return back()->withErrors(array("email" => $exception->getMessage()));
		}
	}

	/**
	 * Remove a member from the team (Owner only).
	 */
	public function removeMember(Request $request, User $member): RedirectResponse
	{
		$this->authorizeOwner($request);

		$organization = $request->user()->currentOrganization();

		/* Prevent owner from removing themselves */
		if ($member->id === $request->user()->id) {
			return back()->withErrors(array("member" => "You cannot remove yourself from the team."));
		}

		$isMember = $organization->users()->where("users.id", $member->id)->exists();

		if (!$isMember) {
			return back()->withErrors(array("member" => "This user is not a member of your team."));
		}

		$organization->users()->detach($member->id);

		/* Invalidate removed member's sessions so they can't continue navigating */
		DB::table("sessions")->where("user_id", $member->id)->delete();

		/* Revoke any Sanctum API tokens for the removed member */
		$member->tokens()->delete();

		return back()->with("success", "Team member removed.");
	}

	/**
	 * Cancel a pending invitation (Owner only).
	 */
	public function cancelInvitation(Request $request, TeamInvitation $invitation): RedirectResponse
	{
		$this->authorizeOwner($request);
		$this->authorizeInvitationBelongsToOrganization($request, $invitation);

		$this->invitationService->cancelInvitation($invitation);

		return back()->with("success", "Invitation cancelled.");
	}

	/**
	 * Resend a pending invitation with a fresh link (Owner only).
	 */
	public function resendInvitation(Request $request, TeamInvitation $invitation): RedirectResponse
	{
		$this->authorizeOwner($request);
		$this->authorizeInvitationBelongsToOrganization($request, $invitation);

		if (!$invitation->isPending()) {
			return back()->withErrors(array("invitation" => "This invitation is no longer active."));
		}

		$this->invitationService->resendInvitation($invitation);

		return back()->with("success", "Invitation resent to {$invitation->email}.");
	}

	/**
	 * Ensure the current user is the organization owner.
	 */
	private function authorizeOwner(Request $request): void
	{
		abort_unless(
			$request->user()->isOrganizationOwner(),
			403,
			"Only the team owner can perform this action.",
		);
	}

	/**
	 * Ensure the invitation belongs to the user's organization.
	 */
	private function authorizeInvitationBelongsToOrganization(Request $request, TeamInvitation $invitation): void
	{
		$organization = $request->user()->currentOrganization();

		abort_unless(
			$invitation->organization_id === $organization->id,
			403,
			"This invitation does not belong to your organization.",
		);
	}
}
