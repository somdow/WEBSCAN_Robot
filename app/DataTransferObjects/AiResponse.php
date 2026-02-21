<?php

namespace App\DataTransferObjects;

final readonly class AiResponse
{
	public function __construct(
		public bool $successful,
		public ?string $content,
		public ?int $tokensUsed,
		public string $provider,
		public string $model,
		public ?string $errorMessage = null,
	) {}

	public static function success(string $content, int $tokensUsed, string $provider, string $model): self
	{
		return new self(
			successful: true,
			content: $content,
			tokensUsed: $tokensUsed,
			provider: $provider,
			model: $model,
		);
	}

	public static function failure(string $errorMessage, string $provider, string $model): self
	{
		return new self(
			successful: false,
			content: null,
			tokensUsed: null,
			provider: $provider,
			model: $model,
			errorMessage: $errorMessage,
		);
	}
}
