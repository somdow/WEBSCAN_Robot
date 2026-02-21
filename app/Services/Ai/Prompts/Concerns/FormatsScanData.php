<?php

namespace App\Services\Ai\Prompts\Concerns;

trait FormatsScanData
{
	private function formatFindingsArray(array $findingsData): string
	{
		$lines = array();

		foreach ($findingsData as $finding) {
			$type = $finding["type"] ?? "info";
			$message = $finding["message"] ?? "";

			if ($message !== "") {
				$lines[] = "- [{$type}] {$message}";
			}
		}

		if (empty($lines)) {
			return "No specific findings recorded.";
		}

		return implode("\n", $lines);
	}

	private function formatRecommendationsArray(array $recommendations): string
	{
		if (empty($recommendations)) {
			return "None provided.";
		}

		$lines = array();

		foreach ($recommendations as $recommendation) {
			$lines[] = "- {$recommendation}";
		}

		return implode("\n", $lines);
	}
}
