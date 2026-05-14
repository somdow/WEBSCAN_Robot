<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\WaitlistSignup;
use App\Notifications\WaitlistConfirmationNotification;
use App\Notifications\WaitlistSignupAdminNotification;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WaitlistController extends Controller
{
	/**
	 * Capture a waitlist signup when public registration is disabled.
	 *
	 * Stores email + optional desired URL so we can re-engage users once
	 * registration reopens. Always returns JSON so the landing-page form
	 * can swap to a success state without a full page reload.
	 *
	 * Side-effects (best-effort, never block the response):
	 *   - Email every super-admin with the new signup details.
	 *   - Email the visitor confirming we received their address.
	 */
	public function store(Request $request): JsonResponse
	{
		/* This endpoint only exists when public registration is OFF. When the
		   admin re-enables registration the landing form swaps to /register —
		   leaving /waitlist live would turn it into an email-spam vector
		   (admin notifications + branded confirmation emails to attacker-
		   supplied addresses). 404 makes the route effectively disappear. */
		if (Setting::getValue("registration_enabled", "0") === "1") {
			throw new NotFoundHttpException();
		}

		$request->merge(array(
			"desired_url" => UrlNormalizer::prependScheme((string) $request->input("desired_url", "")),
		));

		$validated = $request->validate(array(
			"email" => array("required", "email:rfc,dns", "max:255"),
			"desired_url" => array("nullable", "string", "url:http,https", "max:2048"),
		));

		try {
			$signup = WaitlistSignup::create(array(
				"email" => $validated["email"],
				"desired_url" => $validated["desired_url"] ?? null,
				"ip_address" => $request->ip(),
			));
		} catch (\Throwable $exception) {
			Log::error("Waitlist signup failed", array(
				"email" => $validated["email"],
				"error" => $exception->getMessage(),
			));

			return response()->json(array(
				"success" => false,
				"error" => "We could not save your signup. Please try again later.",
				"code" => 500,
			), 500);
		}

		$this->fanOutWaitlistNotifications($signup);

		return response()->json(array(
			"success" => true,
			"message" => "You are on the list. We will email you the moment we reopen signups.",
		));
	}

	/**
	 * Fan out admin + subscriber notifications. Failures are logged but
	 * never bubbled to the caller — the signup itself succeeded and the
	 * visitor should still see a clean confirmation.
	 */
	private function fanOutWaitlistNotifications(WaitlistSignup $signup): void
	{
		try {
			$superAdmins = User::query()->where("is_super_admin", true)->get();
			if ($superAdmins->isNotEmpty()) {
				Notification::send($superAdmins, new WaitlistSignupAdminNotification($signup));
			}
		} catch (\Throwable $exception) {
			Log::warning("Waitlist admin notification failed", array(
				"signup_id" => $signup->id,
				"error" => $exception->getMessage(),
			));
		}

		try {
			Notification::route("mail", $signup->email)
				->notify(new WaitlistConfirmationNotification());
		} catch (\Throwable $exception) {
			Log::warning("Waitlist confirmation notification failed", array(
				"signup_id" => $signup->id,
				"error" => $exception->getMessage(),
			));
		}
	}
}
