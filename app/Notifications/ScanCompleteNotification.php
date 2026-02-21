<?php

namespace App\Notifications;

use App\Models\Scan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScanCompleteNotification extends Notification implements ShouldQueue
{
	use Queueable;

	public function __construct(
		private readonly Scan $scan,
	) {}

	public function via(object $notifiable): array
	{
		return array("mail");
	}

	public function toMail(object $notifiable): MailMessage
	{
		$projectName = $this->scan->project->name ?? "your project";
		$score = $this->scan->overall_score ?? 0;

		return (new MailMessage())
			->subject("SEO Scan Complete — Score: {$score}/100")
			->greeting("Hello " . $notifiable->name . "!")
			->line("Your SEO scan for **{$projectName}** is complete.")
			->line("**Overall Score: {$score}/100**")
			->action("View Results", url("/scans/{$this->scan->id}"))
			->line("You can also download a PDF report from the scan results page.");
	}
}
