<?php

namespace App\Http\Controllers;

use App\Models\WaitlistSignup;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaitlistController extends Controller
{
	/**
	 * Capture a waitlist signup when public registration is disabled.
	 *
	 * Stores email + optional desired URL so we can re-engage users once
	 * registration reopens. Always returns JSON so the landing-page form
	 * can swap to a success state without a full page reload.
	 */
	public function store(Request $request): JsonResponse
	{
		$request->merge(array(
			"desired_url" => UrlNormalizer::prependScheme((string) $request->input("desired_url", "")),
		));

		$validated = $request->validate(array(
			"email" => array("required", "email:rfc,dns", "max:255"),
			"desired_url" => array("nullable", "string", "url:http,https", "max:2048"),
		));

		try {
			WaitlistSignup::create(array(
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

		return response()->json(array(
			"success" => true,
			"message" => "You are on the list. We will email you the moment we reopen signups.",
		));
	}
}
