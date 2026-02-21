<?php

namespace App\Services\Ai;

use App\Enums\AiProvider;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiKeyResolver
{
	/**
	 * Maps each provider to its column on the users table.
	 */
	private const PROVIDER_KEY_COLUMNS = array(
		"gemini" => "ai_gemini_key",
		"openai" => "ai_openai_key",
		"anthropic" => "ai_anthropic_key",
	);

	/**
	 * Resolve an API key for the given provider.
	 * Priority: user's stored key for this provider → system .env key.
	 */
	public function resolve(?User $user, AiProvider $provider): string
	{
		$userKey = $this->resolveUserKey($user, $provider);

		if ($userKey !== null) {
			return $userKey;
		}

		$systemKey = $this->resolveSystemKey($provider);

		if ($systemKey !== null) {
			return $systemKey;
		}

		Log::warning("No AI API key available", array(
			"provider" => $provider->value,
			"user_id" => $user?->id,
		));

		throw new RuntimeException("No API key configured for {$provider->label()}. Set one in your account settings or ask your administrator to configure a system key.");
	}

	/**
	 * Check whether any key is available for a given provider without throwing.
	 */
	public function hasKey(?User $user, AiProvider $provider): bool
	{
		return $this->resolveUserKey($user, $provider) !== null
			|| $this->resolveSystemKey($provider) !== null;
	}

	/**
	 * Find the first provider that has a user-configured API key.
	 * Used for auto-detection when the user hasn't set an explicit preference.
	 */
	public function findProviderWithUserKey(?User $user): ?AiProvider
	{
		if ($user === null) {
			return null;
		}

		foreach (AiProvider::cases() as $provider) {
			if ($this->resolveUserKey($user, $provider) !== null) {
				return $provider;
			}
		}

		return null;
	}

	private function resolveUserKey(?User $user, AiProvider $provider): ?string
	{
		if ($user === null) {
			return null;
		}

		$column = self::PROVIDER_KEY_COLUMNS[$provider->value] ?? null;

		if ($column === null) {
			return null;
		}

		$key = $user->{$column};

		if ($key === null || trim($key) === "") {
			return null;
		}

		return trim($key);
	}

	private function resolveSystemKey(AiProvider $provider): ?string
	{
		$key = config("ai.providers.{$provider->value}.api_key");

		if ($key === null || trim($key) === "") {
			return null;
		}

		return trim($key);
	}
}
