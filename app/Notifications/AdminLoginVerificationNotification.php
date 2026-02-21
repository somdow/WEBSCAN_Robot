<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLoginVerificationNotification extends Notification implements ShouldQueue
{
	use Queueable;

	public function __construct(
		private string $verificationLink,
	) {}

	public function via(object $notifiable): array
	{
		return array("mail");
	}

	public function toMail(object $notifiable): MailMessage
	{
		$siteName = Setting::getValue("site_name", "HELLO WEB_SCANS");

		return (new MailMessage())
			->subject("Verify Your Admin Login — " . $siteName)
			->greeting("Hello " . $notifiable->name . "!")
			->line("We received a login request for your admin account. Click the button below to verify and sign in.")
			->action("Verify & Sign In", $this->verificationLink)
			->line("This link expires in 15 minutes and can only be used once.")
			->line("If you didn't attempt to log in, please change your password immediately.");
	}
}
