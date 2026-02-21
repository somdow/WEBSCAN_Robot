<?php

namespace App\Enums;

/**
 * Classifies analyzers by their execution scope during multi-page crawl scans.
 * Site-wide analyzers run once per scan; per-page analyzers run for each crawled page.
 */
enum AnalyzerScope: string
{
	case SiteWide = "site_wide";
	case PerPage = "per_page";
}
