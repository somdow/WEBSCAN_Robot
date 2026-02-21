<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

class PerformanceHintsAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "performanceHints";
	}

	public function label(): string
	{
		return "Performance Hints";
	}

	public function category(): string
	{
		return "Usability & Performance";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.performanceHints", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$ttfb = $scanContext->timeToFirstByte;
		$totalTime = $scanContext->totalTransferTime;
		$findings = array();
		$recommendations = array();
		$status = ModuleStatus::Ok;

		$ttfbGood = config("scanning.thresholds.performance.ttfbGoodSeconds", 0.2);
		$ttfbWarn = config("scanning.thresholds.performance.ttfbWarnSeconds", 0.6);
		$cssWarnCount = config("scanning.thresholds.performance.cssWarnCount", 8);
		$jsWarnCount = config("scanning.thresholds.performance.jsWarnCount", 10);

		if ($ttfb !== null) {
			$ttfbMs = round($ttfb * 1000);
			if ($ttfb <= $ttfbGood) {
				$findings[] = array("type" => "ok", "message" => "Time to First Byte (TTFB): {$ttfbMs}ms — Excellent.");
			} elseif ($ttfb <= $ttfbWarn) {
				$findings[] = array("type" => "warning", "message" => "Time to First Byte (TTFB): {$ttfbMs}ms — Could be improved.");
				$recommendations[] = "Consider server-side optimizations (caching, CDN, faster hosting) to reduce TTFB below 200ms.";
				$status = ModuleStatus::Warning;
			} else {
				$findings[] = array("type" => "bad", "message" => "Time to First Byte (TTFB): {$ttfbMs}ms — Slow server response.");
				$recommendations[] = "TTFB exceeds 600ms. Investigate server performance, enable caching, or use a CDN.";
				$status = ModuleStatus::Warning;
			}
		}

		if ($totalTime !== null) {
			$totalMs = round($totalTime * 1000);
			$findings[] = array("type" => "info", "message" => "Total HTML Load Time: {$totalMs}ms");
		}

		$cssNodes = $xpath->query("//link[@rel='stylesheet']");
		$cssCount = $cssNodes ? $cssNodes->length : 0;
		$jsNodes = $xpath->query("//script[@src]");
		$jsCount = $jsNodes ? $jsNodes->length : 0;

		$findings[] = array("type" => "info", "message" => "External Resources: {$cssCount} CSS file(s), {$jsCount} JS file(s)");

		if ($cssCount > $cssWarnCount) {
			$findings[] = array("type" => "warning", "message" => "High number of CSS files ({$cssCount}). Consider consolidating to reduce HTTP requests.");
			$recommendations[] = "Combine CSS files where possible to reduce the number of HTTP requests.";
			$status = ModuleStatus::Warning;
		}

		if ($jsCount > $jsWarnCount) {
			$findings[] = array("type" => "warning", "message" => "High number of JavaScript files ({$jsCount}). Consider consolidating or deferring non-critical scripts.");
			$recommendations[] = "Combine JS files, defer non-critical scripts, or load them asynchronously.";
			$status = ModuleStatus::Warning;
		}

		$renderBlockingCount = $this->countRenderBlockingScripts($xpath);

		if ($renderBlockingCount > 0) {
			$findings[] = array(
				"type" => "warning",
				"message" => "{$renderBlockingCount} render-blocking script(s) found in <head> without async or defer. These block page rendering until fully downloaded and executed.",
			);
			$recommendations[] = "Add 'defer' or 'async' to script tags in <head>, or move them to the end of <body>. Render-blocking scripts delay First Contentful Paint (FCP).";
			$status = ModuleStatus::Warning;
		}

		if ($status === ModuleStatus::Ok) {
			$findings[] = array("type" => "ok", "message" => "Performance indicators look healthy.");
		}

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Count script tags in <head> that lack async or defer attributes.
	 * These scripts block rendering — the browser must download and execute them
	 * before it can continue parsing the HTML and painting the page.
	 */
	private function countRenderBlockingScripts(\DOMXPath $xpath): int
	{
		$headScripts = $xpath->query("//head//script[@src]");

		if ($headScripts === false || $headScripts->length === 0) {
			return 0;
		}

		$blockingCount = 0;

		for ($index = 0; $index < $headScripts->length; $index++) {
			$scriptNode = $headScripts->item($index);

			if (!($scriptNode instanceof DOMElement)) {
				continue;
			}

			$hasAsync = $scriptNode->hasAttribute("async");
			$hasDefer = $scriptNode->hasAttribute("defer");
			$scriptType = strtolower(trim($scriptNode->getAttribute("type")));

			/** Module scripts are deferred by default — skip them */
			if ($scriptType === "module") {
				continue;
			}

			if (!$hasAsync && !$hasDefer) {
				$blockingCount++;
			}
		}

		return $blockingCount;
	}
}
