<?php

namespace App\Enums;

enum AiProvider: string
{
	case Gemini = "gemini";
	case OpenAi = "openai";
	case Anthropic = "anthropic";

	public function label(): string
	{
		return match ($this) {
			self::Gemini => "Google Gemini",
			self::OpenAi => "OpenAI",
			self::Anthropic => "Anthropic",
		};
	}

	public function defaultModel(): string
	{
		return match ($this) {
			self::Gemini => "gemini-2.0-flash",
			self::OpenAi => "gpt-4o",
			self::Anthropic => "claude-sonnet-4-5-20250929",
		};
	}

	public function configKey(): string
	{
		return "ai.providers.{$this->value}";
	}
}
