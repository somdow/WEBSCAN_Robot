<?php

namespace App\Models;

use App\Enums\ModuleStatus;
use App\Models\Concerns\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanModuleResult extends Model
{
	use HasFactory;
	use HasRouteUuid;

	protected $fillable = array(
		"scan_id",
		"scan_page_id",
		"module_key",
		"status",
		"findings",
		"recommendations",
		"ai_summary",
		"ai_suggestion",
	);

	protected function casts(): array
	{
		return array(
			"status" => ModuleStatus::class,
			"findings" => "array",
			"recommendations" => "array",
		);
	}

	/* ── Relationships ── */

	public function scan(): BelongsTo
	{
		return $this->belongsTo(Scan::class);
	}

	public function scanPage(): BelongsTo
	{
		return $this->belongsTo(ScanPage::class);
	}

	/* ── Helpers ── */

	public function hasAiInsights(): bool
	{
		return $this->ai_summary !== null || $this->ai_suggestion !== null;
	}
}
