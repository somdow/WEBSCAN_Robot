<?php

namespace App\Contracts;

/**
 * Prompt that includes image URLs for multimodal AI providers (vision).
 * Providers that support vision will send the images alongside the text prompt.
 * Providers without vision support still receive the text prompt and image URLs as context.
 */
interface MultimodalPromptInterface extends AiPromptInterface
{
	/**
	 * Return image URLs to send to the AI for visual analysis.
	 * Each entry is an absolute URL string.
	 *
	 * @return array<string>
	 */
	public function buildImageUrls(): array;
}
