<?php

namespace App\Services\Ai\Prompts\Modules;

use App\Contracts\MultimodalPromptInterface;
use App\Models\ScanModuleResult;
use App\Services\Ai\Prompts\Concerns\BuildsPageContext;

/**
 * Specialized prompt for the imageAnalysis module.
 * Sends page context + image URLs to the AI (with vision when available)
 * so it can generate SEO-optimized alt text for each image.
 */
class ImageAnalysisPrompt implements MultimodalPromptInterface
{
	use BuildsPageContext;

	private const MAX_IMAGES_FOR_VISION = 10;
	private const MAX_EXISTING_ALT_SAMPLES = 8;

	private array $imagesNeedingAlt = array();
	private array $imagesWithAlt = array();
	private string $pageContext = "";

	public function __construct(
		protected readonly ScanModuleResult $moduleResult,
		protected readonly string $siteUrl,
	) {
		$this->prepareImageData();
		$this->preparePageContext();
	}

	public function buildSystemPrompt(): string
	{
		return <<<'PROMPT'
You are a senior SEO consultant specializing in image optimization and accessibility.

Your job: generate specific, SEO-optimized alt text for each image provided.

SEO rules (2025/2026 Google guidelines):
- LENGTH: Keep each alt text between 80 and 125 characters. Most screen readers stop reading after ~125 characters. Too short (<30 chars) is vague; too long is truncated.
- DESCRIPTION FIRST: Describe what the image actually shows. Google uses alt text + computer vision + page content to understand images. Start with the visual content, then weave in context.
- KEYWORD INTEGRATION: Naturally incorporate one relevant keyword from the page topic per alt text. Google explicitly warns that keyword-stuffing in alt attributes "results in a negative user experience and may cause your site to be seen as spam."
- DECORATIVE IMAGES: If the image is clearly decorative (spacer, divider, icon, background pattern), suggest empty alt="" per W3C accessibility guidelines. Do NOT describe decorative images.
- ACCESSIBILITY FRAMEWORK: Think "what information would a visually impaired user miss if they couldn't see this image?" — answer that question first, then add SEO value naturally.
- GOOGLE IMAGE SEARCH: Alt text is a primary signal for Google Image Search ranking. Well-described images can drive significant traffic from image search.
- If you can see the image (vision mode), describe what you actually see. If you cannot, infer from the filename, URL path, and page context.

Output format — for EACH image, output exactly:
IMAGE: [filename]
ALT: [your suggested alt text]

Then after all images, add a brief 1-2 sentence overall tip about the site's image SEO strategy.

General rules:
- Be specific to this website and its industry. Never give generic alt text like "image" or "photo".
- Write for a marketing manager — knowledgeable but not a developer.
- You may use markdown formatting (bold, bullet lists, numbered lists) to improve readability.
PROMPT;
	}

	public function buildUserPrompt(): string
	{
		$imageList = $this->formatImageList();

		return <<<PROMPT
Website: {$this->siteUrl}

Page Context:
{$this->pageContext}

Images needing alt text ({$this->countImagesNeedingAlt()} total):
{$imageList}

Generate SEO-optimized alt text for each image listed above.
PROMPT;
	}

	/**
	 * Return image URLs for multimodal providers to visually analyze.
	 * Capped to control token costs — vision models charge per image.
	 */
	public function buildImageUrls(): array
	{
		$urls = array();

		foreach ($this->imagesNeedingAlt as $image) {
			if (count($urls) >= self::MAX_IMAGES_FOR_VISION) {
				break;
			}

			$src = $image["src"] ?? "";

			if ($src !== "" && str_starts_with($src, "http")) {
				$urls[] = $src;
			}
		}

		return $urls;
	}

	/**
	 * Extract images from module findings, separating those needing alt text
	 * from those that already have it (used for topic/voice context).
	 */
	private function prepareImageData(): void
	{
		$findings = $this->moduleResult->findings ?? array();

		foreach ($findings as $finding) {
			if (($finding["type"] ?? "") !== "data" || ($finding["key"] ?? "") !== "imageDetails") {
				continue;
			}

			foreach ($finding["value"] ?? array() as $image) {
				$altStatus = $image["alt_status"] ?? "ok";

				if ($altStatus === "missing" || $altStatus === "empty") {
					$this->imagesNeedingAlt[] = $image;
				} elseif ($altStatus === "ok" && !empty($image["alt"])) {
					$this->imagesWithAlt[] = $image;
				}
			}

			break;
		}
	}

	/**
	 * Build page context using the shared trait, then append image-specific context
	 * (existing alt text samples for voice/topic reference).
	 */
	private function preparePageContext(): void
	{
		$baseContext = $this->buildPageContext($this->moduleResult);
		$existingAltContext = $this->formatExistingAltText();

		$parts = array();

		if ($baseContext !== "") {
			$parts[] = $baseContext;
		}

		if ($existingAltContext !== "") {
			$parts[] = "Existing Image Alt Text on This Page (for voice/topic reference):\n{$existingAltContext}";
		}

		$this->pageContext = !empty($parts)
			? implode("\n\n", $parts)
			: "No additional page context available.";
	}

	/**
	 * Format existing alt text samples from images that already have alt attributes.
	 * Gives the AI a sense of the page's topic and existing naming conventions.
	 */
	private function formatExistingAltText(): string
	{
		if (empty($this->imagesWithAlt)) {
			return "";
		}

		$samples = array_slice($this->imagesWithAlt, 0, self::MAX_EXISTING_ALT_SAMPLES);
		$lines = array();

		foreach ($samples as $image) {
			$fileName = $this->extractFileName($image["src"] ?? "unknown");
			$alt = $image["alt"] ?? "";

			$lines[] = "- {$fileName}: \"{$alt}\"";
		}

		return implode("\n", $lines);
	}

	private function formatImageList(): string
	{
		if (empty($this->imagesNeedingAlt)) {
			return "All images already have alt text. Suggest improvements for existing alt attributes.";
		}

		$lines = array();

		foreach ($this->imagesNeedingAlt as $index => $image) {
			$src = $image["src"] ?? "unknown";
			$fileName = $this->extractFileName($src);
			$altStatus = $image["alt_status"] ?? "unknown";
			$number = $index + 1;

			$lines[] = "{$number}. [{$altStatus}] {$fileName} — {$src}";
		}

		return implode("\n", $lines);
	}

	/**
	 * Extract the filename from an image URL, falling back to the raw source.
	 */
	private function extractFileName(string $src): string
	{
		$urlPath = parse_url($src, PHP_URL_PATH);

		return $urlPath ? basename($urlPath) : $src;
	}

	private function countImagesNeedingAlt(): int
	{
		return count($this->imagesNeedingAlt);
	}
}
