<?php

namespace App\Services\Ai\Gateways;

use App\Contracts\AiGatewayInterface;
use App\DataTransferObjects\AiResponse;
use App\Services\Ai\Gateways\Concerns\HandlesApiErrors;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiGateway implements AiGatewayInterface
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
		$url = "{$this->endpoint}/models/{$this->model}:generateContent?key={$this->apiKey}";

		$generationConfig = array(
			"temperature" => $options["temperature"] ?? 0.3,
			"topK" => 40,
			"topP" => 0.95,
		);

		if (isset($options["max_tokens"])) {
			$generationConfig["maxOutputTokens"] = $options["max_tokens"];
		}

		$payload = array(
			"contents" => array(
				array(
					"parts" => array(
						array("text" => $systemPrompt . "\n\n" . $userPrompt),
					),
				),
			),
			"generationConfig" => $generationConfig,
		);

		if (!empty($options["response_mime_type"])) {
			$payload["generationConfig"]["responseMimeType"] = $options["response_mime_type"];
		}

		try {
			$response = Http::withHeaders(array(
				"Content-Type" => "application/json",
			))
				->timeout($this->timeoutSeconds)
				->post($url, $payload);

			if ($response->failed()) {
				return $this->handleErrorResponse($response, "Gemini");
			}

			$body = $response->json();
			$parts = $body["candidates"][0]["content"]["parts"] ?? array();
			$content = $this->extractTextFromParts($parts);
			$finishReason = $body["candidates"][0]["finishReason"] ?? null;
			$tokensUsed = $body["usageMetadata"]["totalTokenCount"] ?? 0;

			if ($finishReason !== null) {
				Log::info("Gemini finish reason", array(
					"finish_reason" => $finishReason,
					"model" => $this->model,
				));
			}

			/* If truncated and no text returned, fail fast; otherwise allow parser+retry flow to recover. */
			if ($finishReason === "MAX_TOKENS" && $content === null) {
				Log::warning("Gemini MAX_TOKENS with empty text payload", array(
					"model" => $this->model,
					"parts_count" => is_array($parts) ? count($parts) : 0,
				));

				return AiResponse::failure(
					"Gemini response was not completed in the required format. Please try again.",
					"gemini",
					$this->model,
				);
			}

			if ($content === null) {
				return AiResponse::failure(
					"Gemini returned empty content",
					"gemini",
					$this->model,
				);
			}

			return AiResponse::success($content, $tokensUsed, "gemini", $this->model);
		} catch (\Throwable $exception) {
			Log::error("GeminiGateway request failed", array(
				"error" => $exception->getMessage(),
				"model" => $this->model,
			));

			return AiResponse::failure($exception->getMessage(), "gemini", $this->model);
		}
	}

	private function extractTextFromParts(array $parts): ?string
	{
		$textChunks = array();

		foreach ($parts as $part) {
			if (!is_array($part)) {
				continue;
			}

			$text = $part["text"] ?? null;

			if (is_string($text) && trim($text) !== "") {
				$textChunks[] = $text;
			}
		}

		if (empty($textChunks)) {
			return null;
		}

		return implode("\n", $textChunks);
	}

}
