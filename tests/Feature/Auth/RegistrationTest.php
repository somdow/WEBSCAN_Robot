<?php

namespace Tests\Feature\Auth;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
	use RefreshDatabase;

	protected function setUp(): void
	{
		parent::setUp();

		/* EnsureRegistrationEnabled middleware redirects to /login when this
		   setting is "0" (the seeded default). Tests must explicitly enable
		   registration before posting to /register. */
		Setting::setValue("registration_enabled", "1");
	}

	public function test_registration_screen_can_be_rendered(): void
	{
		$response = $this->get("/register");

		$response->assertStatus(200);
	}

	public function test_new_users_can_register(): void
	{
		$response = $this->post("/register", array(
			"name" => "Test User",
			"email" => "test@example.com",
			"password" => "password",
			"password_confirmation" => "password",
		));

		$this->assertAuthenticated();
		$response->assertRedirect(route("dashboard", absolute: false));
	}

	public function test_registration_creates_organization(): void
	{
		$freePlan = \App\Models\Plan::factory()->free()->create();

		$this->post("/register", array(
			"name" => "Org Test User",
			"email" => "orgtest@example.com",
			"password" => "password",
			"password_confirmation" => "password",
		));

		$this->assertDatabaseHas("organizations", array(
			"name" => "Org Test User's Organization",
			"plan_id" => $freePlan->id,
		));
	}

	public function test_registration_does_not_send_welcome_notification(): void
	{
		Notification::fake();

		$this->post("/register", array(
			"name" => "Welcome User",
			"email" => "welcome@example.com",
			"password" => "password",
			"password_confirmation" => "password",
		));

		/* Welcome email should only arrive AFTER the user verifies their address
		   so the verification email lands alone in the inbox first. */
		Notification::assertNotSentTo(
			\App\Models\User::where("email", "welcome@example.com")->first(),
			WelcomeNotification::class,
		);
	}

	public function test_registration_sends_email_verification_notification(): void
	{
		Notification::fake();

		$this->post("/register", array(
			"name" => "Verify User",
			"email" => "verify@example.com",
			"password" => "password",
			"password_confirmation" => "password",
		));

		$user = User::where("email", "verify@example.com")->first();
		Notification::assertSentTo($user, VerifyEmail::class);
	}
}
