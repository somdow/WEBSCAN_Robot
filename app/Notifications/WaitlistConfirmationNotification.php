<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms to the visitor that their waitlist signup was received.
 * Keeps the loop closed so they are not left wondering whether the
 * inline success message on the landing page actually saved anything.
 */
class WaitlistConfirmationNotification extends Notification implements ShouldQueue
{
	use Queueable;

	public function via(object $notifiable): array
	{
		return array("mail");
	}

	public function toMail(object $notifiable): MailMessage
	{
		return (new MailMessage())
			->subject("You are on the " . config("app.name") . " waitlist")
			->greeting("Thanks for joining the waitlist!")
			->line("We have added you to the " . config("app.name") . " waitlist.")
			->line("Public signups are paused while we polish a few things, but you will be one of the first emailed the moment we reopen.")
			->line("Reply to this email anytime if you have questions.")
			->salutation("— The " . config("app.name") . " team");
	}
}
