<?php

namespace App\Models;

use App\Models\Concerns\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanPage extends Model
{
	use HasFactory;
	use HasRouteUuid;

	protected $fillable = array(
		"project_id",
		"scan_id",
		"url",
		"page_score",
		"http_status_code",
		"content_type",
		"is_homepage",
		"crawl_depth",
		"scan_duration_ms",
		"error_message",
		"source",
		"analysis_status",
		"credit_state",
	);

	protected function casts(): array
	{
		return array(
			"is_homepage" => "boolean",
		);
	}

	protected static function booted(): void
	{
		static::creating(function (self $scanPage): void {
			if ($scanPage->project_id === null && $scanPage->scan_id !== null) {
				$scanPage->project_id = Scan::query()
					->whereKey($scanPage->scan_id)
					->value("project_id");
			}
		});
	}

	/* ── Relationships ── */

	public function project(): BelongsTo
	{
		return $this->belongsTo(Project::class);
	}

	public function scan(): BelongsTo
	{
		return $this->belongsTo(Scan::class);
	}

	public function moduleResults(): HasMany
	{
		return $this->hasMany(ScanModuleResult::class);
	}

	/* ── Helpers ── */

	public function scoreColorClass(): string
	{
		return match (true) {
			$this->page_score === null => "text-gray-400",
			$this->page_score >= 80 => "text-emerald-600",
			$this->page_score >= 50 => "text-amber-600",
			default => "text-red-600",
		};
	}

	public function scoreStrokeClass(): string
	{
		return match (true) {
			$this->page_score === null => "stroke-gray-300",
			$this->page_score >= 80 => "stroke-emerald-500",
			$this->page_score >= 50 => "stroke-amber-500",
			default => "stroke-red-500",
		};
	}

	public function formattedDuration(): string
	{
		if ($this->scan_duration_ms === null) {
			return "N/A";
		}

		$seconds = round($this->scan_duration_ms / 1000, 1);

		return "{$seconds}s";
	}

	public function truncatedUrl(int $maxLength = 60): string
	{
		$url = $this->url;

		if (mb_strlen($url) <= $maxLength) {
			return $url;
		}

		return mb_substr($url, 0, $maxLength - 3) . "...";
	}
}
