<?php

namespace App\Notifications;

use App\Models\WaitlistSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every super-admin user when an anonymous visitor joins the
 * landing-page waitlist while public registration is disabled. Keeps
 * the admin in the loop without forcing them to query the DB.
 */
class WaitlistSignupAdminNotification extends Notification implements ShouldQueue
{
	use Queueable;

	public function __construct(
		private readonly WaitlistSignup $signup,
	) {}

	public function via(object $notifiable): array
	{
		return array("mail");
	}

	public function toMail(object $notifiable): MailMessage
	{
		$desiredUrl = $this->signup->desired_url ?: "—";

		$message = (new MailMessage())
			->subject("New waitlist signup: " . $this->signup->email)
			->greeting("New waitlist signup")
			->line("Someone joined the waitlist on " . config("app.name") . ".")
			->line("**Email:** " . $this->signup->email)
			->line("**Wanted to scan:** " . $desiredUrl)
			->line("**IP:** " . ($this->signup->ip_address ?: "—"))
			->line("**Signed up:** " . $this->signup->created_at->toDayDateTimeString());

		return $message;
	}
}
