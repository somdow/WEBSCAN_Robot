<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\PageSpeedInsightsClient;

/**
 * Shared Core Web Vitals evaluation logic for mobile and desktop strategies.
 * Subclasses define moduleKey, label, weight, and which API strategy to use.
 */
abstract class CoreWebVitalsBaseAnalyzer implements AnalyzerInterface
{
	/** Screenshot captured from the PageSpeed API response (base64 JPEG). */
	protected ?string $capturedScreenshotBase64 = null;

	public function __construct(
		protected readonly PageSpeedInsightsClient $pageSpeedClient,
	) {}

	abstract protected function strategy(): string;

	public function category(): string
	{
		return "Core Web Vitals";
	}

	/**
	 * Retrieve the homepage screenshot captured during the last analyze() call.
	 * Returns raw base64-encoded JPEG data or null if unavailable.
	 */
	public function getCapturedScreenshot(): ?string
	{
		return $this->capturedScreenshotBase64;
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$apiResult = $this->pageSpeedClient->fetchMetrics($scanContext->effectiveUrl, $this->strategy());

		$this->capturedScreenshotBase64 = $apiResult["screenshotBase64"] ?? null;

		if (!$apiResult["success"]) {
			return $this->buildUnavailableResult($apiResult["error"] ?? "Unknown error");
		}

		$findings = array();
		$recommendations = array();

		$dataSource = $apiResult["hasFieldData"] ? "field" : "lab";
		$findings[] = array("type" => "data", "key" => "cwvDataSource", "value" => $dataSource);
		$findings[] = array("type" => "data", "key" => "cwvStrategy", "value" => $this->strategy());

		if ($apiResult["hasFieldData"]) {
			$findings[] = array("type" => "info", "message" => "Real user data available (Chrome User Experience Report). These metrics reflect actual visitor experiences.");
		} else {
			$findings[] = array("type" => "info", "message" => "No real user data available — using lab data (Lighthouse simulation). Lab data may not reflect real-world performance.");
		}

		/** Evaluate the 3 Core Web Vitals */
		$cwvMetrics = $this->evaluateCoreWebVitals($apiResult, $findings, $recommendations);
		$findings[] = array("type" => "data", "key" => "cwvMetrics", "value" => $cwvMetrics);

		/** Performance score from Lighthouse */
		$performanceScore = $apiResult["performanceScore"];
		if ($performanceScore !== null) {
			$scorePercent = (int) round($performanceScore * 100);
			$scoreType = $scorePercent >= 90 ? "ok" : ($scorePercent >= 50 ? "warning" : "bad");
			$findings[] = array("type" => $scoreType, "message" => "Lighthouse Performance Score: {$scorePercent}/100.");
		}

		$status = $this->determineOverallStatus($cwvMetrics);

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Evaluate the 3 Core Web Vitals: LCP, CLS, and INP (or TBT as lab proxy).
	 *
	 * @return array<string, array{label: string, value: ?float, unit: string, rating: string, thresholds: array}>
	 */
	private function evaluateCoreWebVitals(array $apiResult, array &$findings, array &$recommendations): array
	{
		$thresholds = config("scanning.thresholds.coreWebVitals", array());
		$hasFieldData = $apiResult["hasFieldData"];
		$fieldMetrics = $apiResult["fieldMetrics"];
		$labMetrics = $apiResult["labMetrics"];
		$cwvMetrics = array();

		/** LCP — Largest Contentful Paint */
		$lcpValue = $hasFieldData
			? ($fieldMetrics["lcp"]["value"] ?? null)
			: ($labMetrics["lcp"]["value"] ?? null);
		$lcpGood = (float) ($thresholds["lcpGoodMs"] ?? 2500);
		$lcpWarn = (float) ($thresholds["lcpWarnMs"] ?? 4000);

		$lcpRating = $this->rateMetric($lcpValue, $lcpGood, $lcpWarn);
		$cwvMetrics["lcp"] = array(
			"label" => "Largest Contentful Paint",
			"shortLabel" => "LCP",
			"value" => $lcpValue,
			"displayValue" => $lcpValue !== null ? $this->formatMilliseconds($lcpValue) : null,
			"unit" => "ms",
			"rating" => $lcpRating,
			"thresholds" => array("good" => $lcpGood, "warn" => $lcpWarn),
		);

		if ($lcpValue !== null) {
			$displayMs = $this->formatMilliseconds($lcpValue);
			$findings[] = array("type" => $this->ratingToFindingType($lcpRating), "message" => "LCP (Largest Contentful Paint): {$displayMs} — {$this->ratingLabel($lcpRating)}.");
			if ($lcpRating !== "good") {
				$recommendations[] = "Improve LCP by optimizing the largest visible element (usually a hero image or heading). Consider preloading critical resources, using a CDN, and optimizing server response time.";
			}
		}

		/** CLS — Cumulative Layout Shift */
		$clsValue = $hasFieldData
			? ($fieldMetrics["cls"]["value"] ?? null)
			: ($labMetrics["cls"]["value"] ?? null);
		$clsGood = (float) ($thresholds["clsGood"] ?? 0.1);
		$clsWarn = (float) ($thresholds["clsWarn"] ?? 0.25);

		$clsRating = $this->rateMetric($clsValue, $clsGood, $clsWarn);
		$cwvMetrics["cls"] = array(
			"label" => "Cumulative Layout Shift",
			"shortLabel" => "CLS",
			"value" => $clsValue,
			"displayValue" => $clsValue !== null ? number_format($clsValue, 3) : null,
			"unit" => "",
			"rating" => $clsRating,
			"thresholds" => array("good" => $clsGood, "warn" => $clsWarn),
		);

		if ($clsValue !== null) {
			$displayCls = number_format($clsValue, 3);
			$findings[] = array("type" => $this->ratingToFindingType($clsRating), "message" => "CLS (Cumulative Layout Shift): {$displayCls} — {$this->ratingLabel($clsRating)}.");
			if ($clsRating !== "good") {
				$recommendations[] = "Reduce CLS by setting explicit width/height on images and embeds, avoiding dynamically injected content above the fold, and using CSS containment.";
			}
		}

		/** INP (field data) or TBT (lab proxy) — Interactivity */
		if ($hasFieldData && isset($fieldMetrics["inp"])) {
			$inpValue = $fieldMetrics["inp"]["value"] ?? null;
			$inpGood = (float) ($thresholds["inpGoodMs"] ?? 200);
			$inpWarn = (float) ($thresholds["inpWarnMs"] ?? 500);

			$inpRating = $this->rateMetric($inpValue, $inpGood, $inpWarn);
			$cwvMetrics["inp"] = array(
				"label" => "Interaction to Next Paint",
				"shortLabel" => "INP",
				"value" => $inpValue,
				"displayValue" => $inpValue !== null ? $this->formatMilliseconds($inpValue) : null,
				"unit" => "ms",
				"rating" => $inpRating,
				"thresholds" => array("good" => $inpGood, "warn" => $inpWarn),
			);

			if ($inpValue !== null) {
				$displayMs = $this->formatMilliseconds($inpValue);
				$findings[] = array("type" => $this->ratingToFindingType($inpRating), "message" => "INP (Interaction to Next Paint): {$displayMs} — {$this->ratingLabel($inpRating)}.");
				if ($inpRating !== "good") {
					$recommendations[] = "Improve INP by reducing JavaScript execution time, breaking up long tasks, and minimizing main-thread blocking work.";
				}
			}
		} else {
			$tbtValue = $labMetrics["tbt"]["value"] ?? null;
			$tbtGood = (float) ($thresholds["tbtGoodMs"] ?? 200);
			$tbtWarn = (float) ($thresholds["tbtWarnMs"] ?? 600);

			$tbtRating = $this->rateMetric($tbtValue, $tbtGood, $tbtWarn);
			$cwvMetrics["tbt"] = array(
				"label" => "Total Blocking Time",
				"shortLabel" => "TBT",
				"value" => $tbtValue,
				"displayValue" => $tbtValue !== null ? $this->formatMilliseconds($tbtValue) : null,
				"unit" => "ms",
				"rating" => $tbtRating,
				"thresholds" => array("good" => $tbtGood, "warn" => $tbtWarn),
				"isLabProxy" => true,
			);

			if ($tbtValue !== null) {
				$displayMs = $this->formatMilliseconds($tbtValue);
				$findings[] = array("type" => $this->ratingToFindingType($tbtRating), "message" => "TBT (Total Blocking Time): {$displayMs} — {$this->ratingLabel($tbtRating)}. Lab proxy for INP.");
				if ($tbtRating !== "good") {
					$recommendations[] = "Reduce TBT by minimizing main-thread work, breaking up long JavaScript tasks, and deferring non-critical scripts.";
				}
			}
		}

		/** Supplemental metrics: FCP and Speed Index */
		$fcpValue = $labMetrics["fcp"]["value"] ?? null;
		if ($fcpValue !== null) {
			$fcpGood = (float) ($thresholds["fcpGoodMs"] ?? 1800);
			$fcpWarn = (float) ($thresholds["fcpWarnMs"] ?? 3000);
			$fcpRating = $this->rateMetric($fcpValue, $fcpGood, $fcpWarn);

			$cwvMetrics["fcp"] = array(
				"label" => "First Contentful Paint",
				"shortLabel" => "FCP",
				"value" => $fcpValue,
				"displayValue" => $this->formatMilliseconds($fcpValue),
				"unit" => "ms",
				"rating" => $fcpRating,
				"thresholds" => array("good" => $fcpGood, "warn" => $fcpWarn),
				"supplemental" => true,
			);
		}

		$siValue = $labMetrics["speedIndex"]["value"] ?? null;
		if ($siValue !== null) {
			$siGood = (float) ($thresholds["speedIndexGoodMs"] ?? 3400);
			$siWarn = (float) ($thresholds["speedIndexWarnMs"] ?? 5800);
			$siRating = $this->rateMetric($siValue, $siGood, $siWarn);

			$cwvMetrics["speedIndex"] = array(
				"label" => "Speed Index",
				"shortLabel" => "SI",
				"value" => $siValue,
				"displayValue" => $this->formatMilliseconds($siValue),
				"unit" => "ms",
				"rating" => $siRating,
				"thresholds" => array("good" => $siGood, "warn" => $siWarn),
				"supplemental" => true,
			);
		}

		return $cwvMetrics;
	}

	private function rateMetric(?float $value, float $goodThreshold, float $warnThreshold): string
	{
		if ($value === null) {
			return "unknown";
		}

		if ($value <= $goodThreshold) {
			return "good";
		}

		if ($value <= $warnThreshold) {
			return "needs-improvement";
		}

		return "poor";
	}

	private function determineOverallStatus(array $cwvMetrics): ModuleStatus
	{
		$coreKeys = array("lcp", "cls", "inp", "tbt");
		$ratings = array();

		foreach ($coreKeys as $key) {
			if (isset($cwvMetrics[$key])) {
				$ratings[] = $cwvMetrics[$key]["rating"];
			}
		}

		if (empty($ratings)) {
			return ModuleStatus::Info;
		}

		$poorCount = count(array_filter($ratings, fn(string $rating) => $rating === "poor"));
		$needsImprovementCount = count(array_filter($ratings, fn(string $rating) => $rating === "needs-improvement"));

		if ($poorCount > 0) {
			return ModuleStatus::Bad;
		}

		if ($needsImprovementCount > 0) {
			return ModuleStatus::Warning;
		}

		return ModuleStatus::Ok;
	}

	private function buildUnavailableResult(string $errorMessage): AnalysisResult
	{
		return new AnalysisResult(
			status: ModuleStatus::Info,
			findings: array(
				array("type" => "info", "message" => "Core Web Vitals data could not be retrieved from Google PageSpeed Insights."),
				array("type" => "info", "message" => "Reason: {$errorMessage}"),
				array("type" => "info", "message" => "Configure a PageSpeed Insights API key in admin settings for reliable access. The API is free (25,000 requests/day)."),
			),
			recommendations: array(),
		);
	}

	private function formatMilliseconds(float $milliseconds): string
	{
		if ($milliseconds >= 1000) {
			return number_format($milliseconds / 1000, 1) . " s";
		}

		return (int) round($milliseconds) . " ms";
	}

	private function ratingToFindingType(string $rating): string
	{
		return match ($rating) {
			"good" => "ok",
			"needs-improvement" => "warning",
			"poor" => "bad",
			default => "info",
		};
	}

	private function ratingLabel(string $rating): string
	{
		return match ($rating) {
			"good" => "Good",
			"needs-improvement" => "Needs Improvement",
			"poor" => "Poor",
			default => "Unknown",
		};
	}
}
