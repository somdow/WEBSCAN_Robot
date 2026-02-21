<?php

namespace App\Services\Ai\Gateways\Concerns;

use App\DataTransferObjects\AiResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

trait HandlesApiErrors
{
	private function handleErrorResponse(Response $response, string $providerName): AiResponse
	{
		$statusCode = $response->status();
		$body = $response->json();
		$errorMessage = $body["error"]["message"] ?? "HTTP {$statusCode}";
		$providerSlug = strtolower($providerName);

		Log::warning("{$providerName} API error", array(
			"status" => $statusCode,
			"error" => $errorMessage,
			"model" => $this->model,
		));

		return AiResponse::failure("{$providerName} API error: {$errorMessage}", $providerSlug, $this->model);
	}
}
