<?php

namespace App\Services\Analyzers\Seo;

/**
 * Core Web Vitals evaluation using Google's mobile strategy.
 * Mobile metrics are prioritized by Google's mobile-first indexing.
 */
class CoreWebVitalsMobileAnalyzer extends CoreWebVitalsBaseAnalyzer
{
	protected function strategy(): string
	{
		return "mobile";
	}

	public function moduleKey(): string
	{
		return "coreWebVitalsMobile";
	}

	public function label(): string
	{
		return "Core Web Vitals (Mobile)";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.coreWebVitalsMobile", 6);
	}
}
