<?php

namespace App\Services\Analyzers\Seo;

/**
 * Core Web Vitals evaluation using Google's desktop strategy.
 * Desktop metrics complement mobile results for full performance visibility.
 */
class CoreWebVitalsDesktopAnalyzer extends CoreWebVitalsBaseAnalyzer
{
	protected function strategy(): string
	{
		return "desktop";
	}

	public function moduleKey(): string
	{
		return "coreWebVitalsDesktop";
	}

	public function label(): string
	{
		return "Core Web Vitals (Desktop)";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.coreWebVitalsDesktop", 5);
	}
}
