<?php

namespace App\Services\Ai\Gateways;

use App\Contracts\AiGatewayInterface;
use App\DataTransferObjects\AiResponse;
use App\Services\Ai\Gateways\Concerns\HandlesApiErrors;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicGateway implements AiGatewayInterface
{
	use HandlesApiErrors;
	private const API_VERSION = "2023-06-01";

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
		$url = "{$this->endpoint}/messages";
		$imageUrls = $options["images"] ?? array();

		$userContent = $this->buildUserContent($userPrompt, $imageUrls);

		$payload = array(
			"model" => $this->model,
			"max_tokens" => $maxTokens,
			"temperature" => $options["temperature"] ?? 0.3,
			"system" => $systemPrompt,
			"messages" => array(
				array("role" => "user", "content" => $userContent),
			),
		);

		try {
			$response = Http::withHeaders(array(
				"x-api-key" => $this->apiKey,
				"anthropic-version" => self::API_VERSION,
				"Content-Type" => "application/json",
			))
				->timeout($this->timeoutSeconds)
				->post($url, $payload);

			if ($response->failed()) {
				return $this->handleErrorResponse($response, "Anthropic");
			}

			$body = $response->json();
			$content = $body["content"][0]["text"] ?? null;
			$stopReason = $body["stop_reason"] ?? null;
			$inputTokens = $body["usage"]["input_tokens"] ?? 0;
			$outputTokens = $body["usage"]["output_tokens"] ?? 0;
			$tokensUsed = $inputTokens + $outputTokens;

			if ($stopReason === "max_tokens") {
				return AiResponse::failure(
					"Anthropic response was cut off before completion. Please try again.",
					"anthropic",
					$this->model,
				);
			}

			if ($content === null) {
				return AiResponse::failure(
					"Anthropic returned empty content",
					"anthropic",
					$this->model,
				);
			}

			return AiResponse::success($content, $tokensUsed, "anthropic", $this->model);
		} catch (\Throwable $exception) {
			Log::error("AnthropicGateway request failed", array(
				"error" => $exception->getMessage(),
				"model" => $this->model,
			));

			return AiResponse::failure($exception->getMessage(), "anthropic", $this->model);
		}
	}

	/**
	 * Build user content — plain string for text-only, array of blocks for multimodal.
	 */
	private function buildUserContent(string $userPrompt, array $imageUrls): string|array
	{
		if (empty($imageUrls)) {
			return $userPrompt;
		}

		$contentParts = array();

		foreach ($imageUrls as $imageUrl) {
			$contentParts[] = array(
				"type" => "image",
				"source" => array(
					"type" => "url",
					"url" => $imageUrl,
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
