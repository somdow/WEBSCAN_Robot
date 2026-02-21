<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAiSettingsRequest;
use Illuminate\Http\RedirectResponse;
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
	 * Update the user's AI provider preference and per-provider API keys.
	 * Requires a paid plan with AI access (ai_tier >= 2).
	 */
	public function update(UpdateAiSettingsRequest $request): RedirectResponse
	{
		abort_unless($request->user()->canAccessAi(), 403, "AI settings require a Pro or Agency plan.");

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
