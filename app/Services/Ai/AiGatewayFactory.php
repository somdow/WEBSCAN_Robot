<?php

namespace App\Services\Ai;

use App\Contracts\AiGatewayInterface;
use App\Enums\AiProvider;
use App\Models\User;
use App\Services\Ai\Gateways\AnthropicGateway;
use App\Services\Ai\Gateways\GeminiGateway;
use App\Services\Ai\Gateways\OpenAiGateway;

class AiGatewayFactory
{
	public function __construct(
		private readonly AiKeyResolver $keyResolver,
	) {}

	/**
	 * Build the appropriate gateway for a user.
	 * Uses the user's preferred provider if they have a key, otherwise falls back to system default.
	 */
	public function make(?User $user): AiGatewayInterface
	{
		$provider = $this->resolveProvider($user);
		$apiKey = $this->keyResolver->resolve($user, $provider);
		$providerConfig = config("ai.providers.{$provider->value}");

		return match ($provider) {
			AiProvider::Gemini => new GeminiGateway(
				apiKey: $apiKey,
				model: $providerConfig["model"],
				endpoint: $providerConfig["endpoint"],
				maxTokens: $providerConfig["max_tokens"],
				timeoutSeconds: $providerConfig["timeout_seconds"],
			),
			AiProvider::OpenAi => new OpenAiGateway(
				apiKey: $apiKey,
				model: $providerConfig["model"],
				endpoint: $providerConfig["endpoint"],
				maxTokens: $providerConfig["max_tokens"],
				timeoutSeconds: $providerConfig["timeout_seconds"],
			),
			AiProvider::Anthropic => new AnthropicGateway(
				apiKey: $apiKey,
				model: $providerConfig["model"],
				endpoint: $providerConfig["endpoint"],
				maxTokens: $providerConfig["max_tokens"],
				timeoutSeconds: $providerConfig["timeout_seconds"],
			),
		};
	}

	/**
	 * Check whether AI is available for a user (any provider has a key).
	 */
	public function isAvailable(?User $user): bool
	{
		$provider = $this->resolveProvider($user);

		return $this->keyResolver->hasKey($user, $provider);
	}

	/**
	 * Determine which AI provider to use.
	 * 1. User's explicit preference (if they have a key for it)
	 * 2. Auto-detect from whichever provider key the user has configured
	 * 3. System default (for admin-configured .env keys)
	 */
	private function resolveProvider(?User $user): AiProvider
	{
		if ($user !== null && $user->ai_provider !== null) {
			$userProvider = AiProvider::tryFrom($user->ai_provider);

			if ($userProvider !== null && $this->keyResolver->hasKey($user, $userProvider)) {
				return $userProvider;
			}
		}

		$detectedProvider = $this->keyResolver->findProviderWithUserKey($user);

		if ($detectedProvider !== null) {
			return $detectedProvider;
		}

		$defaultValue = config("ai.default_provider", "gemini");

		return AiProvider::from($defaultValue);
	}
}
