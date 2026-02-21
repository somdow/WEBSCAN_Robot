<?php

namespace App\Models;

use App\Enums\ScanSchedule;
use App\Models\Concerns\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
	use HasFactory;
	use HasRouteUuid;

	protected $fillable = array(
		"organization_id",
		"name",
		"url",
		"scan_schedule",
		"target_keywords",
		"discovery_status",
	);

	protected function casts(): array
	{
		return array(
			"scan_schedule" => ScanSchedule::class,
			"target_keywords" => "array",
		);
	}

	/* ── Relationships ── */

	public function organization(): BelongsTo
	{
		return $this->belongsTo(Organization::class);
	}

	/**
	 * All scans including competitor scans. Use ownScans() for project-only scans.
	 */
	public function scans(): HasMany
	{
		return $this->hasMany(Scan::class);
	}

	/**
	 * Latest project scan (excludes competitor scans).
	 */
	public function latestScan(): HasOne
	{
		return $this->hasOne(Scan::class)->ofMany(
			array("id" => "max"),
			function ($query) {
				$query->whereNull("competitor_id");
			},
		);
	}

	public function pages(): HasMany
	{
		return $this->hasMany(ScanPage::class);
	}

	/**
	 * Pages added manually or via discovery (excludes the homepage from initial scan).
	 */
	public function additionalPages(): HasMany
	{
		return $this->pages()->where("source", "!=", "scan");
	}

	public function discoveredPages(): HasMany
	{
		return $this->hasMany(DiscoveredPage::class);
	}

	public function competitors(): HasMany
	{
		return $this->hasMany(Competitor::class);
	}

	/**
	 * Scans belonging to this project (excludes competitor scans).
	 */
	public function ownScans(): HasMany
	{
		return $this->hasMany(Scan::class)->whereNull("competitor_id");
	}


	/* ── Helpers ── */

	/**
	 * Get the normalized domain from the project URL
	 */
	public function domain(): string
	{
		return parse_url($this->url, PHP_URL_HOST) ?? $this->url;
	}
}
