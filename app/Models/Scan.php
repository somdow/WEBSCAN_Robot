<?php

namespace App\Models;

use App\Enums\ScanStatus;
use App\Models\Concerns\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
class Scan extends Model
{
	use HasFactory;
	use HasRouteUuid;

	protected $fillable = array(
		"project_id",
		"competitor_id",
		"triggered_by",
		"status",
		"progress_percent",
		"progress_label",
		"scan_type",
		"overall_score",
		"seo_score",
		"health_score",
		"scan_duration_ms",
		"pages_crawled",
		"max_pages_requested",
		"crawl_depth_limit",
		"is_wordpress",
		"detection_method",
		"fetcher_used",
		"insecure_transport_used",
		"credit_state",
		"homepage_screenshot_path",
		"ai_executive_summary",
	);

	protected function casts(): array
	{
		return array(
			"status" => ScanStatus::class,
			"is_wordpress" => "boolean",
			"insecure_transport_used" => "boolean",
			"progress_percent" => "integer",
			"overall_score" => "integer",
			"seo_score" => "integer",
			"health_score" => "integer",
			"scan_duration_ms" => "integer",
			"ai_executive_summary" => "array",
		);
	}

	/* ── Relationships ── */

	public function project(): BelongsTo
	{
		return $this->belongsTo(Project::class);
	}

	public function triggeredBy(): BelongsTo
	{
		return $this->belongsTo(User::class, "triggered_by");
	}

	public function competitor(): BelongsTo
	{
		return $this->belongsTo(Competitor::class);
	}

	public function moduleResults(): HasMany
	{
		return $this->hasMany(ScanModuleResult::class);
	}

	public function pages(): HasMany
	{
		return $this->hasMany(ScanPage::class);
	}

	public function homePage(): HasOne
	{
		return $this->hasOne(ScanPage::class)->where("is_homepage", true);
	}

	/* ── Helpers ── */

	public function isCrawlScan(): bool
	{
		return $this->scan_type === "crawl";
	}

	public function isCompetitorScan(): bool
	{
		return $this->competitor_id !== null;
	}

	public function isComplete(): bool
	{
		return $this->status->isTerminal();
	}

	/**
	 * Get module results grouped by status for summary display
	 */
	public function resultsByStatus(): array
	{
		return $this->moduleResults
			->groupBy(fn ($result) => $result->status->value)
			->map->count()
			->toArray();
	}

	/**
	 * Tailwind text color class based on score thresholds (80+ green, 50+ amber, else red)
	 */
	public function scoreColorClass(): string
	{
		return self::resolveColorClass($this->overall_score ?? 0);
	}

	/**
	 * Tailwind stroke color class for SVG score rings
	 */
	public function scoreStrokeClass(): string
	{
		return self::resolveStrokeClass($this->overall_score ?? 0);
	}

	public function seoScoreColorClass(): string
	{
		return self::resolveColorClass($this->seo_score ?? 0);
	}

	public function seoScoreStrokeClass(): string
	{
		return self::resolveStrokeClass($this->seo_score ?? 0);
	}

	public function healthScoreColorClass(): string
	{
		return self::resolveColorClass($this->health_score ?? 0);
	}

	public function healthScoreStrokeClass(): string
	{
		return self::resolveStrokeClass($this->health_score ?? 0);
	}

	private static function resolveColorClass(int $score): string
	{
		return match (true) {
			$score >= 80 => "text-emerald-600",
			$score >= 50 => "text-amber-600",
			default => "text-red-600",
		};
	}

	private static function resolveStrokeClass(int $score): string
	{
		return match (true) {
			$score >= 80 => "stroke-emerald-500",
			$score >= 50 => "stroke-amber-500",
			default => "stroke-red-500",
		};
	}

	/**
	 * Human-readable duration
	 */
	public function formattedDuration(): string
	{
		if ($this->scan_duration_ms === null) {
			return "N/A";
		}

		$seconds = round($this->scan_duration_ms / 1000, 1);
		return "{$seconds}s";
	}

	/**
	 * Public URL for the homepage screenshot, or null if not captured.
	 */
	public function getScreenshotUrl(): ?string
	{
		if (empty($this->homepage_screenshot_path)) {
			return null;
		}

		return Storage::disk("public")->url($this->homepage_screenshot_path);
	}
}
