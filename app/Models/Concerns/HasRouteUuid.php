<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Adds a UUID column for URL-safe route model binding.
 *
 * Models keep their integer primary key for internal use (foreign keys,
 * relationships, queries). The UUID is only used for public-facing URLs
 * to prevent enumeration and hide sequential IDs.
 */
trait HasRouteUuid
{
	public static function bootHasRouteUuid(): void
	{
		static::creating(function ($model) {
			if (empty($model->uuid)) {
				$model->uuid = (string) Str::orderedUuid();
			}
		});
	}

	/**
	 * Resolve route model binding by the uuid column instead of id.
	 */
	public function getRouteKeyName(): string
	{
		return "uuid";
	}
}
