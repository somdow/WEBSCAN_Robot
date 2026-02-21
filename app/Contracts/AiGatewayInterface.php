<?php

namespace App\Contracts;

use App\DataTransferObjects\AiResponse;

interface AiGatewayInterface
{
	/**
	 * Send a prompt to the AI provider and return the response.
	 *
	 * @param string $systemPrompt  Instructions defining the AI's role and output format
	 * @param string $userPrompt    The actual analysis request with scan data
	 * @param array  $options       Provider-specific overrides (max_tokens, temperature, etc.)
	 */
	public function generate(string $systemPrompt, string $userPrompt, array $options = array()): AiResponse;
}
