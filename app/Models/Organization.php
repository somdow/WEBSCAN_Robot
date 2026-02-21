<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Billable;

class Organization extends Model
{
	use Billable, HasFactory;

	protected static function booted(): void
	{
		static::creating(function (Organization $organization): void {
			if ($organization->plan_id !== null) {
				return;
			}

			$defaultPlanId = Plan::query()->where("slug", "free")->value("id")
				?? Plan::query()->orderBy("id")->value("id");

			if ($defaultPlanId !== null) {
				$organization->plan_id = (int) $defaultPlanId;
			}
		});
	}

	protected $fillable = array(
		"name",
		"slug",
		"logo_path",
		"pdf_company_name",
		"brand_color",
		"plan_id",
		"deactivated_at",
	);

	protected function casts(): array
	{
		return array(
			"trial_ends_at" => "datetime",
			"deactivated_at" => "datetime",
		);
	}

	/* ── Relationships ── */

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}

	public function users(): BelongsToMany
	{
		return $this->belongsToMany(User::class)
			->withPivot("role")
			->withTimestamps();
	}

	public function projects(): HasMany
	{
		return $this->hasMany(Project::class);
	}

	public function subscriptionUsage(): HasMany
	{
		return $this->hasMany(SubscriptionUsage::class);
	}

	public function teamInvitations(): HasMany
	{
		return $this->hasMany(TeamInvitation::class);
	}

	/* ── Helpers ── */

	/**
	 * Get the organization owner
	 */
	public function owner(): ?User
	{
		return $this->users()
			->wherePivot("role", OrganizationRole::Owner->value)
			->first();
	}

	/**
	 * Whether the trial period is still active
	 */
	public function onTrial(): bool
	{
		return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
	}

	/**
	 * Get members filtered by role
	 */
	public function membersByRole(OrganizationRole $role): BelongsToMany
	{
		return $this->users()->wherePivot("role", $role->value);
	}

	/**
	 * Whether this organization's plan includes AI optimization access.
	 * Requires ai_tier >= 2 (Pro or Agency).
	 */
	public function canAccessAi(): bool
	{
		return $this->plan !== null && $this->plan->ai_tier >= 2;
	}

	/**
	 * Whether this organization is currently active (not deactivated by an admin).
	 */
	public function isActive(): bool
	{
		return $this->deactivated_at === null;
	}

	public function deactivate(): void
	{
		$this->update(array("deactivated_at" => now()));
	}

	public function reactivate(): void
	{
		$this->update(array("deactivated_at" => null));
	}

	public function isOnFreePlan(): bool
	{
		return $this->plan === null || $this->plan->slug === "free";
	}

	/* ── PDF Branding ── */

	/**
	 * Whether this organization's plan allows white-label PDF branding.
	 */
	public function canWhiteLabel(): bool
	{
		return $this->plan?->hasFeature("white_label") ?? false;
	}

	/**
	 * Brand name for PDF reports. Uses custom name if white-label enabled, else org name.
	 */
	public function pdfBrandName(): string
	{
		if ($this->canWhiteLabel() && !empty($this->pdf_company_name)) {
			return $this->pdf_company_name;
		}

		return $this->name;
	}

	/**
	 * Accent color for PDF reports. Uses custom color if white-label enabled, else default orange.
	 */
	public function pdfAccentColor(): string
	{
		if ($this->canWhiteLabel() && !empty($this->brand_color)) {
			if (preg_match('/^#[0-9A-Fa-f]{6}$/', $this->brand_color)) {
				return $this->brand_color;
			}
		}

		return config("scan-ui.pdf_default_accent_color");
	}

	/**
	 * Public URL for the organization's logo, or null if none uploaded.
	 */
	public function logoUrl(): ?string
	{
		if (empty($this->logo_path)) {
			return null;
		}

		return Storage::disk("public")->url($this->logo_path);
	}

	/**
	 * Full filesystem path for the logo (needed by dompdf which can't fetch URLs).
	 */
	public function logoFilePath(): ?string
	{
		if (empty($this->logo_path)) {
			return null;
		}

		return Storage::disk("public")->path($this->logo_path);
	}

	/**
	 * Logo filesystem path for PDF reports — only available on white-label plans.
	 */
	public function pdfLogoFilePath(): ?string
	{
		if (!$this->canWhiteLabel()) {
			return null;
		}

		$path = $this->logoFilePath();

		if ($path === null || !file_exists($path)) {
			return null;
		}

		return $path;
	}

	/**
	 * Whether this organization has an active paid Stripe subscription.
	 */
	public function hasActiveSubscription(): bool
	{
		return $this->subscribed("default") && !$this->isOnFreePlan();
	}

	/**
	 * Determine the current billing cycle (monthly or annual) from Stripe price ID.
	 */
	public function billingCycle(): ?string
	{
		$subscription = $this->subscription("default");

		if ($subscription === null || $this->plan === null) {
			return null;
		}

		$priceId = $subscription->stripe_price;

		if ($priceId === $this->plan->stripe_monthly_price_id) {
			return "monthly";
		}

		if ($priceId === $this->plan->stripe_annual_price_id) {
			return "annual";
		}

		return null;
	}

	/**
	 * Whether the subscription is cancelled but still within its grace period.
	 */
	public function subscriptionOnGracePeriod(): bool
	{
		$subscription = $this->subscription("default");

		return $subscription !== null && $subscription->onGracePeriod();
	}
}
