<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\AdminLoginVerificationNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AdminLoginVerificationService
{
	private const CACHE_PREFIX = "admin_login_nonce:";
	private const NONCE_LENGTH = 40;
	private const TTL_MINUTES = 15;

	/**
	 * Generate a signed verification link for admin login.
	 * Stores a single-use nonce in cache mapped to the user ID and intended destination.
	 */
	public function generateVerificationLink(User $user, string $intended = "dashboard"): string
	{
		$nonce = Str::random(self::NONCE_LENGTH);

		Cache::put(
			self::CACHE_PREFIX . $nonce,
			array("user_id" => $user->id, "intended" => $intended),
			now()->addMinutes(self::TTL_MINUTES),
		);

		return URL::temporarySignedRoute(
			"admin.login.verify",
			now()->addMinutes(self::TTL_MINUTES),
			array("nonce" => $nonce),
		);
	}

	/**
	 * Consume the nonce atomically. Returns cached payload (user_id + intended) or null if expired/used.
	 * Cache::pull() ensures single-use — the nonce is deleted on first retrieval.
	 */
	public function consumeNonce(string $nonce): ?array
	{
		return Cache::pull(self::CACHE_PREFIX . $nonce);
	}

	/**
	 * Dispatch the verification email to the admin user.
	 */
	public function sendVerificationEmail(User $user, string $verificationLink): void
	{
		$user->notify(new AdminLoginVerificationNotification($verificationLink));
	}
}
