<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AdminLoginVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginVerificationController extends Controller
{
	/**
	 * Verify an admin login nonce from a signed email link.
	 * Consumes the nonce (single-use), authenticates the user, and redirects
	 * to the intended destination (dashboard or admin panel).
	 */
	public function __invoke(Request $request, string $nonce, AdminLoginVerificationService $verificationService): RedirectResponse
	{
		$payload = $verificationService->consumeNonce($nonce);

		if ($payload === null) {
			return redirect()->route("login")->withErrors(array(
				"email" => "This verification link has expired or has already been used. Please log in again.",
			));
		}

		$user = \App\Models\User::find($payload["user_id"]);

		if ($user === null || !$user->isSuperAdmin() || !$user->isActive()) {
			return redirect()->route("login")->withErrors(array(
				"email" => "This account is no longer eligible for admin access.",
			));
		}

		Auth::login($user);
		$request->session()->regenerate();

		$intended = $payload["intended"] ?? "dashboard";

		if ($intended === "admin") {
			return redirect("/admin");
		}

		return redirect()->route("dashboard");
	}
}
