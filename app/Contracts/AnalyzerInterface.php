<?php

namespace App\Contracts;

use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;

interface AnalyzerInterface
{
	/**
	 * Unique module key stored in scan_module_results.module_key
	 * Must match the v1 camelCase convention (e.g., "titleTag")
	 */
	public function moduleKey(): string;

	/**
	 * Human-readable label for display in the UI
	 */
	public function label(): string;

	/**
	 * Category grouping for report sections
	 */
	public function category(): string;

	/**
	 * Numeric weight for score calculation (0-10)
	 * Higher weight = more impact on overall_score
	 */
	public function weight(): int;

	/**
	 * Execute the analysis against the shared scan context
	 */
	public function analyze(ScanContext $scanContext): AnalysisResult;
}
