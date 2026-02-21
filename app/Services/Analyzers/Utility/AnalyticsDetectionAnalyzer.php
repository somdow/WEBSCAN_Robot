<?php

namespace App\Services\Analyzers\Utility;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class AnalyticsDetectionAnalyzer implements AnalyzerInterface
{
	/**
	 * Platform definitions: each entry maps a human-readable name to its
	 * detection patterns, whether it counts as a "major" analytics platform,
	 * and a regex to extract the tracking/measurement ID when present.
	 */
	private const PLATFORMS = array(
		"Google Analytics 4" => array(
			"patterns" => array("gtag/js?id=G-", "gtag('config'", "gtag(\"config\"", "googletagmanager.com/gtag/js"),
			"major" => true,
			"idPattern" => "/G-[A-Z0-9]+/i",
		),
		"Google Tag Manager" => array(
			"patterns" => array("googletagmanager.com/gtm.js", "googletagmanager.com/ns.html?id=GTM-", "GTM-"),
			"major" => true,
			"idPattern" => "/GTM-[A-Z0-9]+/i",
		),
		"Universal Analytics (legacy)" => array(
			"patterns" => array("google-analytics.com/analytics.js", "ga('create'", "ga(\"create\""),
			"major" => false,
			"idPattern" => "/UA-\d{4,}-\d{1,}/",
			"deprecated" => true,
		),
		"Facebook/Meta Pixel" => array(
			"patterns" => array("connect.facebook.net/en_US/fbevents.js", "fbq('init'", "fbq(\"init\""),
			"major" => false,
			"idPattern" => "/fbq\(['\"]init['\"],\s*['\"](\d+)['\"]/",
			"idGroup" => 1,
		),
		"Microsoft Clarity" => array(
			"patterns" => array("clarity.ms/tag/"),
			"major" => false,
			"idPattern" => "/clarity\.ms\/tag\/([a-z0-9]+)/i",
			"idGroup" => 1,
		),
		"Hotjar" => array(
			"patterns" => array("static.hotjar.com"),
			"major" => false,
			"idPattern" => "/hjid['\"]?\s*[:=]\s*(\d+)/i",
			"idGroup" => 1,
		),
		"Plausible" => array(
			"patterns" => array("plausible.io/js/"),
			"major" => true,
			"idPattern" => null,
		),
		"Matomo" => array(
			"patterns" => array("matomo.js", "piwik.js"),
			"major" => true,
			"idPattern" => null,
		),
	);

	public function moduleKey(): string
	{
		return "analyticsDetection";
	}

	public function label(): string
	{
		return "Analytics";
	}

	public function category(): string
	{
		return "Analytics";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.analyticsDetection", 0);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$html = $scanContext->htmlContent;
		$findings = array();
		$recommendations = array();
		$detected = array();
		$hasMajorAnalytics = false;
		$hasDeprecated = false;

		/* Check every platform — show detected ones as ok/warning, others as greyed-out info */
		foreach (self::PLATFORMS as $platformName => $platformConfig) {
			$matchedPattern = $this->detectPlatform($html, $platformConfig["patterns"]);

			if ($matchedPattern === null) {
				$findings[] = array(
					"type" => "info",
					"message" => "{$platformName} — not detected.",
				);
				continue;
			}

			$trackingId = $this->extractTrackingId($html, $platformConfig);
			$idSuffix = $trackingId !== null ? " ({$trackingId})" : "";
			$isDeprecated = $platformConfig["deprecated"] ?? false;

			if ($isDeprecated) {
				$hasDeprecated = true;
				$findings[] = array(
					"type" => "warning",
					"message" => "{$platformName} detected{$idSuffix} — this platform is deprecated and will stop collecting data.",
				);
			} else {
				$findings[] = array(
					"type" => "ok",
					"message" => "{$platformName} detected{$idSuffix}.",
				);
			}

			if ($platformConfig["major"]) {
				$hasMajorAnalytics = true;
			}

			$detected[] = $platformName;
		}

		/* GTM note about container-managed analytics */
		if (in_array("Google Tag Manager", $detected, true) && !in_array("Google Analytics 4", $detected, true)) {
			$findings[] = array(
				"type" => "info",
				"message" => "Google Tag Manager is present — analytics may be loaded through the GTM container and not visible in the page source.",
			);
		}

		/* Build recommendations based on what was found */
		if (empty($detected)) {
			$recommendations[] = "Install an analytics platform to measure traffic, user behavior, and SEO performance. Google Analytics 4 (free) is the most common choice.";
			$recommendations[] = "If using a tag manager, ensure the container snippet is present in the page source.";
		}

		if ($hasDeprecated) {
			$recommendations[] = "Universal Analytics stopped processing data on July 1, 2024. Migrate to Google Analytics 4 (GA4) to continue collecting website data.";
		}

		if (!empty($detected) && !$hasMajorAnalytics && !$hasDeprecated) {
			$recommendations[] = "Consider adding a full analytics platform (Google Analytics 4, Plausible, or Matomo) alongside your current tracking tools to measure SEO performance.";
		}

		/* Determine overall status */
		$status = match (true) {
			empty($detected) => ModuleStatus::Warning,
			$hasMajorAnalytics => ModuleStatus::Ok,
			$hasDeprecated => ModuleStatus::Warning,
			default => ModuleStatus::Info,
		};

		return new AnalysisResult(
			status: $status,
			findings: $findings,
			recommendations: $recommendations,
		);
	}

	/**
	 * Check if any of the platform's signature patterns exist in the HTML.
	 */
	private function detectPlatform(string $html, array $patterns): ?string
	{
		foreach ($patterns as $pattern) {
			if (stripos($html, $pattern) !== false) {
				return $pattern;
			}
		}

		return null;
	}

	/**
	 * Extract the tracking/measurement ID from the HTML using the platform's regex.
	 */
	private function extractTrackingId(string $html, array $platformConfig): ?string
	{
		$idPattern = $platformConfig["idPattern"] ?? null;

		if ($idPattern === null) {
			return null;
		}

		if (preg_match($idPattern, $html, $matches)) {
			$captureGroup = $platformConfig["idGroup"] ?? 0;
			return $matches[$captureGroup] ?? null;
		}

		return null;
	}
}
