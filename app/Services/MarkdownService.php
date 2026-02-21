<?php

namespace App\Services;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Converts AI-generated markdown content to safe HTML.
 * Uses GitHub Flavored Markdown (tables, strikethrough, autolinks).
 * All raw HTML is stripped and unsafe links are blocked.
 */
class MarkdownService
{
	private static ?GithubFlavoredMarkdownConverter $converter = null;

	public static function toHtml(string $markdown): string
	{
		if (self::$converter === null) {
			self::$converter = new GithubFlavoredMarkdownConverter(array(
				"html_input" => "strip",
				"allow_unsafe_links" => false,
			));
		}

		return trim(self::$converter->convert($markdown)->getContent());
	}
}
