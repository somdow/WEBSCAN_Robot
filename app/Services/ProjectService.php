<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Project;

class ProjectService
{
	/**
	 * Create a new project for an organization from validated form data.
	 */
	public function createProject(Organization $organization, array $validated): Project
	{
		return $organization->projects()->create(array(
			"name" => $validated["name"],
			"url" => $this->normalizeUrl($validated["url"]),
			"target_keywords" => $this->parseKeywords($validated["target_keywords"] ?? null),
		));
	}

	/**
	 * Update an existing project from validated form data.
	 */
	public function updateProject(Project $project, array $validated): void
	{
		$project->update(array(
			"name" => $validated["name"],
			"url" => $this->normalizeUrl($validated["url"]),
			"target_keywords" => $this->parseKeywords($validated["target_keywords"] ?? null),
		));
	}

	/**
	 * Parse a comma-separated keyword string into a trimmed, deduplicated array.
	 * Returns null if no valid keywords are provided.
	 */
	public function parseKeywords(?string $rawKeywords): ?array
	{
		if ($rawKeywords === null || trim($rawKeywords) === "") {
			return null;
		}

		$keywords = array_values(array_unique(array_filter(
			array_map("trim", explode(",", $rawKeywords)),
			fn(string $keyword) => $keyword !== "",
		)));

		return empty($keywords) ? null : $keywords;
	}

	/**
	 * Ensure the URL has an HTTPS scheme prefix.
	 */
	public function normalizeUrl(string $url): string
	{
		if (!preg_match("/^https?:\/\//i", $url)) {
			return "https://" . $url;
		}

		return $url;
	}
}
