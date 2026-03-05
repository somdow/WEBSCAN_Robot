<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAiSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class AiSettingsController extends Controller
{
	/**
	 * Maps each provider to its key column on the users table.
	 */
	private const PROVIDER_KEY_FIELDS = array(
		"gemini" => "ai_gemini_key",
		"openai" => "ai_openai_key",
		"anthropic" => "ai_anthropic_key",
	);

	/**
	 * Endpoint URLs and config for each provider's lightweight validation call.
	 */
	private const PROVIDER_TEST_CONFIG = array(
		"gemini" => array(
			"url" => "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent",
			"auth" => "query_key",
		),
		"openai" => array(
			"url" => "https://api.openai.com/v1/chat/completions",
			"auth" => "bearer",
			"model" => "gpt-4o-mini",
		),
		"anthropic" => array(
			"url" => "https://api.anthropic.com/v1/messages",
			"auth" => "x-api-key",
			"model" => "claude-haiku-4-5-20251001",
		),
	);

	/**
	 * Verify an API key works by making a minimal request to the provider.
	 * Returns JSON indicating whether the key is valid.
	 */
	public function testKey(Request $request): JsonResponse
	{
		$request->validate(array(
			"provider" => "required|string|in:gemini,openai,anthropic",
			"api_key" => "required|string|min:5",
		));

		$provider = $request->input("provider");
		$apiKey = $request->input("api_key");
		$config = self::PROVIDER_TEST_CONFIG[$provider];

		try {
			$response = $this->sendTestRequest($provider, $apiKey, $config);

			if ($response->successful()) {
				return response()->json(array(
					"valid" => true,
					"message" => "Key works!",
				));
			}

			$errorMessage = $this->extractErrorMessage($provider, $response);

			return response()->json(array(
				"valid" => false,
				"message" => $errorMessage,
			));
		} catch (\Illuminate\Http\Client\ConnectionException $exception) {
			return response()->json(array(
				"valid" => false,
				"message" => "Connection failed: could not reach " . ucfirst($provider) . " API.",
			));
		} catch (\Exception $exception) {
			return response()->json(array(
				"valid" => false,
				"message" => "Unexpected error: " . $exception->getMessage(),
			));
		}
	}

	/**
	 * Dispatch a minimal API call to verify the key authenticates successfully.
	 */
	private function sendTestRequest(string $provider, string $apiKey, array $config): \Illuminate\Http\Client\Response
	{
		$httpClient = Http::timeout(10)->acceptJson();

		if ($provider === "gemini") {
			return $httpClient->post($config["url"] . "?key=" . $apiKey, array(
				"contents" => array(
					array("parts" => array(array("text" => "Hi"))),
				),
			));
		}

		if ($provider === "openai") {
			return $httpClient
				->withToken($apiKey)
				->post($config["url"], array(
					"model" => $config["model"],
					"messages" => array(
						array("role" => "user", "content" => "Hi"),
					),
					"max_tokens" => 1,
				));
		}

		return $httpClient
			->withHeaders(array(
				"x-api-key" => $apiKey,
				"anthropic-version" => "2023-06-01",
			))
			->post($config["url"], array(
				"model" => $config["model"],
				"messages" => array(
					array("role" => "user", "content" => "Hi"),
				),
				"max_tokens" => 1,
			));
	}

	/**
	 * Pull a human-readable error message from the provider's error response.
	 */
	private function extractErrorMessage(string $provider, \Illuminate\Http\Client\Response $response): string
	{
		$status = $response->status();
		$body = $response->json();

		if ($status === 401 || $status === 403) {
			return "Invalid key — authentication failed.";
		}

		if ($provider === "gemini" && isset($body["error"]["message"])) {
			return $body["error"]["message"];
		}

		if ($provider === "openai" && isset($body["error"]["message"])) {
			return $body["error"]["message"];
		}

		if ($provider === "anthropic" && isset($body["error"]["message"])) {
			return $body["error"]["message"];
		}

		return "Request failed (HTTP {$status}).";
	}

	/**
	 * Update the user's AI provider preference and per-provider API keys.
	 * Available to all users via BYOK (Bring Your Own Key).
	 */
	public function update(UpdateAiSettingsRequest $request): RedirectResponse
	{

		$validated = $request->validated();
		$user = $request->user();

		$user->ai_provider = $validated["ai_provider"] ?? null;

		foreach (self::PROVIDER_KEY_FIELDS as $providerValue => $column) {
			$keyField = "ai_{$providerValue}_key";
			$clearField = "clear_{$providerValue}_key";

			if (isset($validated[$clearField]) && $validated[$clearField]) {
				$user->{$column} = null;
			} elseif (isset($validated[$keyField]) && $validated[$keyField] !== "") {
				$user->{$column} = $validated[$keyField];
			}
		}

		$user->save();

		return Redirect::route("profile.edit")->with("status", "ai-settings-updated");
	}
}
