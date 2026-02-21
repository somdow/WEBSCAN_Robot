<?php

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInviteMail extends Mailable
{
	use Queueable, SerializesModels;

	public function __construct(
		public TeamInvitation $invitation,
	) {}

	public function envelope(): Envelope
	{
		$organizationName = $this->invitation->organization->name;

		return new Envelope(
			subject: "You've been invited to join {$organizationName}",
		);
	}

	public function content(): Content
	{
		return new Content(
			view: "mail.team-invite",
			with: array(
				"inviterName" => $this->invitation->inviter->name,
				"organizationName" => $this->invitation->organization->name,
				"acceptUrl" => route("team.invitations.accept", $this->invitation->token),
				"expiresAt" => $this->invitation->expires_at->format("F j, Y"),
			),
		);
	}
}
