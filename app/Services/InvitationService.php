<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Mail\TeamInviteMail;
use App\Models\Organization;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvitationService
{
	private const INVITATION_EXPIRY_DAYS = 7;

	public function __construct(
		private readonly BillingService $billingService,
	) {}

	/**
	 * Create and send an invitation to join an organization.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function createInvitation(Organization $organization, string $email, User $invitedBy): TeamInvitation
	{
		$email = strtolower(trim($email));

		$this->validateNotAlreadyMember($organization, $email);
		$this->validateNoPendingInvitation($organization, $email);
		$this->validatePlanCapacity($organization);

		$invitation = TeamInvitation::create(array(
			"organization_id" => $organization->id,
			"invited_by" => $invitedBy->id,
			"email" => $email,
			"token" => TeamInvitation::generateToken(),
			"expires_at" => now()->addDays(self::INVITATION_EXPIRY_DAYS),
		));

		Mail::to($email)->queue(new TeamInviteMail($invitation));

		return $invitation;
	}

	/**
	 * Accept an invitation by token. Adds the user to the organization.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function acceptInvitation(string $token, User $acceptingUser): TeamInvitation
	{
		return DB::transaction(function () use ($token, $acceptingUser): TeamInvitation {
			$invitation = TeamInvitation::where("token", $token)
				->lockForUpdate()
				->first();

			if ($invitation === null) {
				throw new \InvalidArgumentException("This invitation link is invalid.");
			}

			if ($invitation->isAccepted()) {
				throw new \InvalidArgumentException("This invitation has already been accepted.");
			}

			if ($invitation->isExpired()) {
				throw new \InvalidArgumentException("This invitation has expired. Ask the team owner to send a new one.");
			}

			$organization = $invitation->organization;

			/* Prevent duplicate membership */
			$alreadyMember = $organization->users()->where("users.id", $acceptingUser->id)->exists();
			if ($alreadyMember) {
				$invitation->markAccepted();
				return $invitation;
			}

			/* Re-check plan capacity at acceptance time */
			$this->validatePlanCapacity($organization);

			$organization->users()->attach($acceptingUser->id, array(
				"role" => OrganizationRole::Member->value,
			));

			$invitation->markAccepted();

			return $invitation;
		});
	}

	/**
	 * Cancel a pending invitation.
	 */
	public function cancelInvitation(TeamInvitation $invitation): void
	{
		$invitation->delete();
	}

	/**
	 * Process a pending invitation token stored in the session (after login or registration).
	 * Returns the organization name on success, null if no token or invitation is invalid.
	 */
	public function processSessionInvitation(User $authenticatedUser): ?string
	{
		$token = session()->pull("team_invitation_token");

		if ($token === null) {
			return null;
		}

		$invitation = TeamInvitation::where("token", $token)->first();

		if ($invitation === null || !$invitation->isPending()) {
			return null;
		}

		try {
			$this->acceptInvitation($token, $authenticatedUser);

			return $invitation->organization->name;
		} catch (\InvalidArgumentException) {
			return null;
		}
	}

	/**
	 * Resend an invitation with a fresh token and expiry.
	 */
	public function resendInvitation(TeamInvitation $invitation): TeamInvitation
	{
		$invitation->update(array(
			"token" => TeamInvitation::generateToken(),
			"expires_at" => now()->addDays(self::INVITATION_EXPIRY_DAYS),
		));

		Mail::to($invitation->email)->queue(new TeamInviteMail($invitation));

		return $invitation;
	}

	/**
	 * Ensure the email isn't already a member of the organization.
	 */
	private function validateNotAlreadyMember(Organization $organization, string $email): void
	{
		$existingMember = $organization->users()->where("email", $email)->exists();

		if ($existingMember) {
			throw new \InvalidArgumentException("This person is already a member of your team.");
		}
	}

	/**
	 * Ensure there's no pending invitation for this email in this org.
	 */
	private function validateNoPendingInvitation(Organization $organization, string $email): void
	{
		$pendingExists = TeamInvitation::where("organization_id", $organization->id)
			->where("email", $email)
			->pending()
			->exists();

		if ($pendingExists) {
			throw new \InvalidArgumentException("An invitation has already been sent to this email address.");
		}
	}

	/**
	 * Ensure the organization hasn't reached its plan's member limit.
	 */
	private function validatePlanCapacity(Organization $organization): void
	{
		if (!$this->billingService->canAddMember($organization)) {
			throw new \InvalidArgumentException("Your plan's team member limit has been reached. Upgrade to add more members.");
		}
	}
}
