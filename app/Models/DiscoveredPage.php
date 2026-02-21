<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveredPage extends Model
{
	protected $fillable = array(
		"project_id",
		"scan_id",
		"url",
		"crawl_depth",
		"is_analyzed",
	);

	protected function casts(): array
	{
		return array(
			"is_analyzed" => "boolean",
		);
	}

	public function project(): BelongsTo
	{
		return $this->belongsTo(Project::class);
	}

	public function scan(): BelongsTo
	{
		return $this->belongsTo(Scan::class);
	}
}
