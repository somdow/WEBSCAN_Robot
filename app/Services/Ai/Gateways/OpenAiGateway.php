<?php

namespace App\Services\Ai\Gateways;

use App\Contracts\AiGatewayInterface;
use App\DataTransferObjects\AiResponse;
use App\Services\Ai\Gateways\Concerns\HandlesApiErrors;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiGateway implements AiGatewayInterface
{
	use HandlesApiErrors;

	public function __construct(
		private readonly string $apiKey,
		private readonly string $model,
		private readonly string $endpoint,
		private readonly int $maxTokens,
		private readonly int $timeoutSeconds,
	) {}

	public function generate(string $systemPrompt, string $userPrompt, array $options = array()): AiResponse
	{
		$maxTokens = $options["max_tokens"] ?? $this->maxTokens;
		$url = "{$this->endpoint}/chat/completions";
		$imageUrls = $options["images"] ?? array();

		$userContent = $this->buildUserContent($userPrompt, $imageUrls);

		$payload = array(
			"model" => $this->model,
			"max_tokens" => $maxTokens,
			"temperature" => $options["temperature"] ?? 0.3,
			"messages" => array(
				array("role" => "system", "content" => $systemPrompt),
				array("role" => "user", "content" => $userContent),
			),
		);

		try {
			$response = Http::withHeaders(array(
				"Authorization" => "Bearer {$this->apiKey}",
				"Content-Type" => "application/json",
			))
				->timeout($this->timeoutSeconds)
				->post($url, $payload);

			if ($response->failed()) {
				return $this->handleErrorResponse($response, "OpenAI");
			}

			$body = $response->json();
			$content = $body["choices"][0]["message"]["content"] ?? null;
			$finishReason = $body["choices"][0]["finish_reason"] ?? null;
			$tokensUsed = $body["usage"]["total_tokens"] ?? 0;

			if ($finishReason === "length") {
				return AiResponse::failure(
					"OpenAI response was cut off before completion. Please try again.",
					"openai",
					$this->model,
				);
			}

			if ($content === null) {
				return AiResponse::failure(
					"OpenAI returned empty content",
					"openai",
					$this->model,
				);
			}

			return AiResponse::success($content, $tokensUsed, "openai", $this->model);
		} catch (\Throwable $exception) {
			Log::error("OpenAiGateway request failed", array(
				"error" => $exception->getMessage(),
				"model" => $this->model,
			));

			return AiResponse::failure($exception->getMessage(), "openai", $this->model);
		}
	}

	/**
	 * Build user content — plain string for text-only, array of content parts for multimodal.
	 */
	private function buildUserContent(string $userPrompt, array $imageUrls): string|array
	{
		if (empty($imageUrls)) {
			return $userPrompt;
		}

		$contentParts = array();

		foreach ($imageUrls as $imageUrl) {
			$contentParts[] = array(
				"type" => "image_url",
				"image_url" => array(
					"url" => $imageUrl,
					"detail" => "low",
				),
			);
		}

		$contentParts[] = array(
			"type" => "text",
			"text" => $userPrompt,
		);

		return $contentParts;
	}
}
