<?php

namespace Tests\Feature\Auth;

use App\Jobs\ProcessScanJob;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Covers the post-email-verification scan dispatch added for the landing-page
 * signup flow: a project is created during registration with auto_scan_pending
 * set to true, and the first scan only fires once the user verifies their email.
 */
class VerifyEmailScanDispatchTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;

	protected function setUp(): void
	{
		parent::setUp();

		$plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create(array("email_verified_at" => null));
		$this->organization = Organization::factory()->withPlan($plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
	}

	public function test_verification_dispatches_scan_for_pending_landing_project(): void
	{
		Queue::fake();

		$project = $this->buildPendingProject(true);

		$response = $this->actingAs($this->user)->get($this->buildVerificationUrl());

		Queue::assertPushed(ProcessScanJob::class);

		$project->refresh();
		$this->assertFalse($project->auto_scan_pending);

		$response->assertRedirect();
		$this->assertStringContainsString(
			"/projects/{$project->uuid}",
			$response->headers->get("Location"),
		);
	}

	public function test_verification_does_not_dispatch_scan_for_unflagged_project(): void
	{
		Queue::fake();

		$this->buildPendingProject(false);

		$response = $this->actingAs($this->user)->get($this->buildVerificationUrl());

		Queue::assertNotPushed(ProcessScanJob::class);
		$response->assertRedirect(route("dashboard", array("verified" => 1)));
	}

	public function test_verification_with_no_projects_redirects_to_dashboard(): void
	{
		Queue::fake();

		$response = $this->actingAs($this->user)->get($this->buildVerificationUrl());

		Queue::assertNotPushed(ProcessScanJob::class);
		$response->assertRedirect(route("dashboard", array("verified" => 1)));
	}

	public function test_already_verified_user_does_not_re_dispatch_scan(): void
	{
		Queue::fake();

		$this->user->forceFill(array("email_verified_at" => now()))->save();
		$this->buildPendingProject(true);

		$this->actingAs($this->user)->get($this->buildVerificationUrl());

		Queue::assertNotPushed(ProcessScanJob::class);
	}

	public function test_verification_sends_welcome_notification(): void
	{
		Notification::fake();

		$this->actingAs($this->user)->get($this->buildVerificationUrl());

		Notification::assertSentTo($this->user, WelcomeNotification::class);
	}

	public function test_already_verified_user_does_not_resend_welcome(): void
	{
		Notification::fake();

		$this->user->forceFill(array("email_verified_at" => now()))->save();

		$this->actingAs($this->user)->get($this->buildVerificationUrl());

		Notification::assertNotSentTo($this->user, WelcomeNotification::class);
	}

	private function buildPendingProject(bool $autoScanPending): Project
	{
		return Project::create(array(
			"organization_id" => $this->organization->id,
			"name" => "example.com",
			"url" => "https://example.com",
			"target_keywords" => null,
			"auto_scan_pending" => $autoScanPending,
		));
	}

	private function buildVerificationUrl(): string
	{
		return URL::temporarySignedRoute(
			"verification.verify",
			now()->addMinutes(60),
			array(
				"id" => $this->user->id,
				"hash" => sha1($this->user->email),
			),
		);
	}
}
