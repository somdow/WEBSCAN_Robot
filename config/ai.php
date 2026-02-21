<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Default AI Provider
	|--------------------------------------------------------------------------
	|
	| The provider used when a user hasn't configured their own API key.
	| Supported: "gemini", "openai", "anthropic"
	|
	*/

	"default_provider" => env("AI_DEFAULT_PROVIDER", "gemini"),

	/*
	|--------------------------------------------------------------------------
	| Provider Configurations
	|--------------------------------------------------------------------------
	|
	| Each provider has its own API key, model, endpoint, and limits.
	| Users can override the provider and key in their account settings.
	|
	*/

	"providers" => array(
		"gemini" => array(
			"api_key" => env("AI_GEMINI_KEY"),
			"model" => env("AI_GEMINI_MODEL", "gemini-2.5-flash"),
			"endpoint" => "https://generativelanguage.googleapis.com/v1beta",
			"max_tokens" => 2048,
			"timeout_seconds" => 30,
		),
		"openai" => array(
			"api_key" => env("AI_OPENAI_KEY"),
			"model" => env("AI_OPENAI_MODEL", "gpt-4o"),
			"endpoint" => "https://api.openai.com/v1",
			"max_tokens" => 2048,
			"timeout_seconds" => 30,
		),
		"anthropic" => array(
			"api_key" => env("AI_ANTHROPIC_KEY"),
			"model" => env("AI_ANTHROPIC_MODEL", "claude-sonnet-4-5-20250929"),
			"endpoint" => "https://api.anthropic.com/v1",
			"max_tokens" => 2048,
			"timeout_seconds" => 30,
		),
	),

	/*
	|--------------------------------------------------------------------------
	| On-Demand Limits
	|--------------------------------------------------------------------------
	|
	| Monthly AI call caps per plan. Enforced via subscription_usage tracking.
	| Each per-module optimization or executive summary counts as one call.
	|
	*/

	"limits" => array(
		"max_monthly_ai_calls" => (int) env("AI_MAX_MONTHLY_CALLS", 500),
	),
);
