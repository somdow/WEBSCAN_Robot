<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
	use HasFactory;

	protected $fillable = array(
		"name",
		"slug",
		"description",
		"stripe_monthly_price_id",
		"stripe_annual_price_id",
		"price_monthly",
		"price_annual",
		"max_users",
		"max_projects",
		"max_scans_per_month",
		"max_pages_per_scan",
		"max_additional_pages",
		"max_crawl_depth",
		"max_competitors",
		"scan_history_days",
		"ai_tier",
		"feature_flags",
		"is_public",
		"sort_order",
	);

	protected function casts(): array
	{
		return array(
			"price_monthly" => "decimal:2",
			"price_annual" => "decimal:2",
			"feature_flags" => "array",
			"is_public" => "boolean",
		);
	}

	/* ── Relationships ── */

	public function organizations(): HasMany
	{
		return $this->hasMany(Organization::class);
	}

	/* ── Scopes ── */

	public function scopePublic(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->where("is_public", true);
	}

	public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->orderBy("sort_order");
	}

	/* ── Helpers ── */

	public function hasFeature(string $featureKey): bool
	{
		$flags = $this->feature_flags ?? array();
		return !empty($flags[$featureKey]);
	}

	public function annualSavingsPercent(): float
	{
		if ($this->price_monthly <= 0) {
			return 0;
		}

		$annualFromMonthly = $this->price_monthly * 12;
		return round((1 - ($this->price_annual / $annualFromMonthly)) * 100, 1);
	}
}
