<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
	use RefreshDatabase;

	private function createUserWithOrganization(): User
	{
		$user = User::factory()->create();
		$organization = Organization::factory()->create();
		$organization->users()->attach($user, array("role" => "owner"));
		$user->update(array("current_organization_id" => $organization->id));

		return $user;
	}

	public function test_profile_page_is_displayed(): void
	{
		$user = $this->createUserWithOrganization();

		$response = $this
			->actingAs($user)
			->get("/profile");

		$response->assertOk();
	}

	public function test_profile_information_can_be_updated(): void
	{
		$user = $this->createUserWithOrganization();

		$response = $this
			->actingAs($user)
			->patch("/profile", array(
				"name" => "Test User",
				"email" => "test@example.com",
			));

		$response
			->assertSessionHasNoErrors()
			->assertRedirect("/profile");

		$user->refresh();

		$this->assertSame("Test User", $user->name);
		$this->assertSame("test@example.com", $user->email);
		$this->assertNull($user->email_verified_at);
	}

	public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
	{
		$user = $this->createUserWithOrganization();

		$response = $this
			->actingAs($user)
			->patch("/profile", array(
				"name" => "Test User",
				"email" => $user->email,
			));

		$response
			->assertSessionHasNoErrors()
			->assertRedirect("/profile");

		$this->assertNotNull($user->refresh()->email_verified_at);
	}

	public function test_user_can_deactivate_their_account(): void
	{
		$user = $this->createUserWithOrganization();

		$response = $this
			->actingAs($user)
			->delete("/profile", array(
				"password" => "password",
			));

		$response
			->assertSessionHasNoErrors()
			->assertRedirect("/");

		$this->assertGuest();
		$user->refresh();
		$this->assertNotNull($user->deactivated_at);
		$this->assertDatabaseHas("users", array("id" => $user->id));
	}

	public function test_correct_password_must_be_provided_to_delete_account(): void
	{
		$user = $this->createUserWithOrganization();

		$response = $this
			->actingAs($user)
			->from("/profile")
			->delete("/profile", array(
				"password" => "wrong-password",
			));

		$response
			->assertSessionHasErrorsIn("userDeletion", "password")
			->assertRedirect("/profile");

		$this->assertNotNull($user->fresh());
	}
}
