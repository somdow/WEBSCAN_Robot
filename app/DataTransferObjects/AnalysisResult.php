<?php

namespace App\DataTransferObjects;

use App\Enums\ModuleStatus;

final readonly class AnalysisResult
{
	/**
	 * @param ModuleStatus $status         ok|warning|bad|info
	 * @param array        $findings       Structured findings from the analysis
	 * @param array        $recommendations Actionable recommendation strings
	 */
	public function __construct(
		public ModuleStatus $status,
		public array $findings,
		public array $recommendations,
	) {}
}
