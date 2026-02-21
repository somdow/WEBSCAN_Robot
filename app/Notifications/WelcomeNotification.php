<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
	use Queueable;

	public function via(object $notifiable): array
	{
		return array("mail");
	}

	public function toMail(object $notifiable): MailMessage
	{
		$siteName = Setting::getValue("site_name", "HELLO WEB_SCANS");
		$siteTagline = Setting::getValue("site_tagline", "");
		$analyzerCount = Setting::getValue("analyzer_count", "37");

		return (new MailMessage())
			->subject("Welcome to " . $siteName)
			->greeting("Hello " . $notifiable->name . "!")
			->line("Welcome to " . $siteName . " — " . $siteTagline . ".")
			->line("Get started by creating a project and running your first scan. Our " . $analyzerCount . " analyzers will audit your site across technical SEO, content quality, performance, and more.")
			->action("Go to Dashboard", url("/dashboard"))
			->line("If you have any questions, reply to this email and we'll be happy to help.");
	}
}
