<?php

namespace App\Contracts;

interface AiPromptInterface
{
	/**
	 * Build the system prompt that defines the AI's role and output format.
	 */
	public function buildSystemPrompt(): string;

	/**
	 * Build the user prompt containing the actual scan data for analysis.
	 */
	public function buildUserPrompt(): string;
}
