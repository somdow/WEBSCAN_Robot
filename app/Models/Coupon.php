<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
	use HasFactory;

	protected $fillable = array(
		"code",
		"stripe_coupon_id",
		"discount_type",
		"discount_value",
		"applicable_plan_ids",
		"max_redemptions",
		"times_redeemed",
		"expires_at",
		"is_active",
	);

	protected function casts(): array
	{
		return array(
			"discount_type" => DiscountType::class,
			"applicable_plan_ids" => "array",
			"expires_at" => "datetime",
			"is_active" => "boolean",
		);
	}

	/* ── Scopes ── */

	public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->where("is_active", true);
	}

	public function scopeValid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->active()
			->where(function (\Illuminate\Database\Eloquent\Builder $q) {
				$q->whereNull("expires_at")
					->orWhere("expires_at", ">", now());
			})
			->where(function (\Illuminate\Database\Eloquent\Builder $q) {
				$q->whereNull("max_redemptions")
					->orWhereColumn("times_redeemed", "<", "max_redemptions");
			});
	}

	/* ── Helpers ── */

	public function isExpired(): bool
	{
		return $this->expires_at !== null && $this->expires_at->isPast();
	}

	public function isFullyRedeemed(): bool
	{
		return $this->max_redemptions !== null
			&& $this->times_redeemed >= $this->max_redemptions;
	}

	public function isUsable(): bool
	{
		return $this->is_active && !$this->isExpired() && !$this->isFullyRedeemed();
	}

	/**
	 * Whether this coupon applies to a specific plan
	 */
	public function appliesToPlan(int $planId): bool
	{
		$applicablePlans = $this->applicable_plan_ids;
		if (empty($applicablePlans)) {
			return true;
		}
		return in_array($planId, $applicablePlans);
	}
}
