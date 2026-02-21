<?php

namespace App\Models;

use App\Models\Concerns\HasRouteUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competitor extends Model
{
	use HasFactory;
	use HasRouteUuid;

	protected $fillable = array(
		"project_id",
		"url",
		"name",
		"latest_scan_id",
	);

	/* ── Relationships ── */

	public function project(): BelongsTo
	{
		return $this->belongsTo(Project::class);
	}

	public function latestScan(): BelongsTo
	{
		return $this->belongsTo(Scan::class, "latest_scan_id");
	}

	public function scans(): HasMany
	{
		return $this->hasMany(Scan::class);
	}

	/* ── Helpers ── */

	public function domain(): string
	{
		return parse_url($this->url, PHP_URL_HOST) ?? $this->url;
	}

	public function displayName(): string
	{
		return $this->name ?? $this->domain();
	}
}
