<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeamInvitation extends Model
{
	protected $fillable = array(
		"organization_id",
		"invited_by",
		"email",
		"token",
		"expires_at",
		"accepted_at",
	);

	protected function casts(): array
	{
		return array(
			"expires_at" => "datetime",
			"accepted_at" => "datetime",
		);
	}

	/* ── Relationships ── */

	public function organization(): BelongsTo
	{
		return $this->belongsTo(Organization::class);
	}

	public function inviter(): BelongsTo
	{
		return $this->belongsTo(User::class, "invited_by");
	}

	/* ── Scopes ── */

	public function scopePending(Builder $query): Builder
	{
		return $query->whereNull("accepted_at")->where("expires_at", ">", now());
	}

	public function scopeExpired(Builder $query): Builder
	{
		return $query->whereNull("accepted_at")->where("expires_at", "<=", now());
	}

	/* ── Helpers ── */

	public function isExpired(): bool
	{
		return $this->expires_at->isPast();
	}

	public function isAccepted(): bool
	{
		return $this->accepted_at !== null;
	}

	public function isPending(): bool
	{
		return !$this->isAccepted() && !$this->isExpired();
	}

	public function markAccepted(): void
	{
		$this->update(array("accepted_at" => now()));
	}

	/**
	 * Generate a unique invitation token.
	 */
	public static function generateToken(): string
	{
		return Str::random(64);
	}
}
