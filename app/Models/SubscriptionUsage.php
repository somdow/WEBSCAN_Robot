<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SubscriptionUsage extends Model
{
	use HasFactory;

	protected $table = "subscription_usage";

	protected $fillable = array(
		"organization_id",
		"period_start",
		"period_end",
		"scans_used",
		"ai_calls_used",
		"api_calls_used",
	);

	protected function casts(): array
	{
		return array(
			"period_start" => "date",
			"period_end" => "date",
		);
	}

	/* ── Relationships ── */

	public function organization(): BelongsTo
	{
		return $this->belongsTo(Organization::class);
	}

	/* ── Scopes ── */

	/**
	 * Filter to the current billing period for the given organization.
	 * Free plans use calendar month; paid plans align to Stripe billing cycle.
	 */
	public function scopeForCurrentPeriod($query, Organization $organization)
	{
		$periodStart = now()->startOfMonth();
		$periodEnd = now()->endOfMonth();

		return $query->where("organization_id", $organization->id)
			->where("period_start", "<=", $periodEnd)
			->where("period_end", ">=", $periodStart);
	}

	/* ── Static Helpers ── */

	/**
	 * Get or create the usage record for the current billing period.
	 */
	public static function resolveCurrentPeriod(Organization $organization): self
	{
		$periodStart = now()->startOfMonth();
		$periodEnd = now()->endOfMonth();

		return self::firstOrCreate(
			array(
				"organization_id" => $organization->id,
				"period_start" => $periodStart,
				"period_end" => $periodEnd,
			),
			array(
				"scans_used" => 0,
				"ai_calls_used" => 0,
				"api_calls_used" => 0,
			)
		);
	}

	/* ── Helpers ── */

	public function incrementScans(int $count = 1): void
	{
		$this->increment("scans_used", $count);
	}

	/**
	 * Atomically claim a scan credit if under the limit.
	 * Returns true if the credit was claimed, false if the limit was already reached.
	 * Uses a conditional UPDATE to prevent race conditions.
	 */
	public function claimScanCredit(int $maxScans): bool
	{
		$affectedRows = DB::table($this->getTable())
			->where("id", $this->id)
			->where("scans_used", "<", $maxScans)
			->update(array(
				"scans_used" => DB::raw("scans_used + 1"),
				"updated_at" => now(),
			));

		if ($affectedRows > 0) {
			$this->refresh();
			return true;
		}

		return false;
	}

	/**
	 * Atomically release a previously claimed scan credit.
	 * Returns true when a credit was released, false when no decrement occurred.
	 */
	public function releaseScanCredit(): bool
	{
		$affectedRows = DB::table($this->getTable())
			->where("id", $this->id)
			->where("scans_used", ">", 0)
			->update(array(
				"scans_used" => DB::raw("scans_used - 1"),
				"updated_at" => now(),
			));

		if ($affectedRows > 0) {
			$this->refresh();
			return true;
		}

		return false;
	}

	public function incrementAiCalls(int $count = 1): void
	{
		$this->increment("ai_calls_used", $count);
	}

	public function incrementApiCalls(int $count = 1): void
	{
		$this->increment("api_calls_used", $count);
	}
}
