<?php

namespace App\Services;

use App\Enums\CreditState;
use App\Models\Coupon;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\SubscriptionUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class BillingService
{
	/**
	 * Whether Stripe API keys are configured in the environment.
	 */
	public function isStripeConfigured(): bool
	{
		return !empty(config("cashier.secret"));
	}

	/**
	 * Create a Stripe Embedded Checkout session and return the client secret.
	 * Uses Stripe's embedded UI mode so checkout renders in an iframe modal
	 * instead of redirecting to stripe.com.
	 *
	 * @throws \RuntimeException When Stripe is not configured
	 */
	public function createCheckoutSession(
		Organization $organization,
		Plan $plan,
		string $billingCycle,
		?string $couponCode = null,
	): string {
		$this->ensureStripeConfigured();

		$priceId = $this->resolvePriceId($plan, $billingCycle);

		$checkoutBuilder = $organization->newSubscription("default", $priceId)
			->allowPromotionCodes();

		if ($couponCode !== null) {
			$coupon = $this->validateCoupon($couponCode, $plan);

			if ($coupon !== null && $coupon->stripe_coupon_id !== null) {
				$checkoutBuilder->withCoupon($coupon->stripe_coupon_id);
			}
		}

		$checkout = $checkoutBuilder->checkout(array(
			"ui_mode" => "embedded",
			"return_url" => route("billing.success") . "?session_id={CHECKOUT_SESSION_ID}",
		));

		return $checkout->asStripeCheckoutSession()->client_secret;
	}

	/**
	 * Swap the organization's subscription to a different plan.
	 */
	public function swapSubscription(
		Organization $organization,
		Plan $targetPlan,
		string $billingCycle,
	): void {
		$this->ensureStripeConfigured();

		$priceId = $this->resolvePriceId($targetPlan, $billingCycle);

		$organization->subscription("default")->swap($priceId);

		$this->syncPlanFromStripe($organization);
	}

	/**
	 * Cancel the organization's subscription at the end of the current period.
	 */
	public function cancelSubscription(Organization $organization): void
	{
		$this->ensureStripeConfigured();

		$subscription = $organization->subscription("default");

		if ($subscription !== null && !$subscription->canceled()) {
			$subscription->cancel();
		}
	}

	/**
	 * Resume a cancelled subscription that is still within its grace period.
	 */
	public function resumeSubscription(Organization $organization): void
	{
		$this->ensureStripeConfigured();

		$subscription = $organization->subscription("default");

		if ($subscription !== null && $subscription->onGracePeriod()) {
			$subscription->resume();
		}
	}

	/**
	 * Sync the organization's plan_id by looking up the Stripe price ID
	 * in the plans table.
	 */
	public function syncPlanFromStripe(Organization $organization): void
	{
		$subscription = $organization->subscription("default");

		if ($subscription === null) {
			$this->downgradeToFree($organization);
			return;
		}

		$stripePrice = $subscription->stripe_price;

		$matchedPlan = Plan::where("stripe_monthly_price_id", $stripePrice)
			->orWhere("stripe_annual_price_id", $stripePrice)
			->first();

		if ($matchedPlan !== null) {
			$organization->update(array("plan_id" => $matchedPlan->id));
		} else {
			Log::warning("BillingService: Could not resolve plan for Stripe price", array(
				"organization_id" => $organization->id,
				"stripe_price" => $stripePrice,
			));
		}
	}

	/**
	 * Downgrade the organization to the Free plan.
	 */
	public function downgradeToFree(Organization $organization): void
	{
		$freePlan = Plan::where("slug", "free")->first();

		$organization->update(array(
			"plan_id" => $freePlan?->id,
		));
	}

	/**
	 * Resolve or create the current usage tracking record for this billing period.
	 */
	public function resolveCurrentUsage(Organization $organization): SubscriptionUsage
	{
		return SubscriptionUsage::resolveCurrentPeriod($organization);
	}

	/**
	 * Validate a coupon code for a specific plan.
	 * Returns the Coupon model if valid, null otherwise.
	 */
	public function validateCoupon(string $code, Plan $plan): ?Coupon
	{
		$coupon = Coupon::valid()->where("code", $code)->first();

		if ($coupon === null || !$coupon->appliesToPlan($plan->id)) {
			return null;
		}

		return $coupon;
	}

	/**
	 * Whether the organization can create another project within their plan limits.
	 */
	public function canCreateProject(Organization $organization): bool
	{
		$plan = $organization->plan;

		if ($plan === null) {
			return $organization->projects()->count() < 1;
		}

		return $organization->projects()->count() < $plan->max_projects;
	}

	/**
	 * Whether the organization can trigger another scan this billing period.
	 */
	public function canTriggerScan(Organization $organization): bool
	{
		$plan = $organization->plan;
		$maxScans = $plan?->max_scans_per_month ?? 10;

		$usage = $this->resolveCurrentUsage($organization);

		return $usage->scans_used < $maxScans;
	}

	/**
	 * Atomically claim a scan credit for the organization.
	 * Returns true if the credit was claimed, false if the quota is exhausted.
	 * Uses DB-level conditional update to prevent race conditions.
	 */
	public function claimScanCredit(Organization $organization): bool
	{
		$plan = $organization->plan;
		$maxScans = $plan?->max_scans_per_month ?? 10;

		$usage = $this->resolveCurrentUsage($organization);

		return $usage->claimScanCredit($maxScans);
	}

	/**
	 * Release a previously claimed scan credit.
	 */
	public function releaseScanCredit(Organization $organization): bool
	{
		$usage = $this->resolveCurrentUsage($organization);

		return $usage->releaseScanCredit();
	}

	/**
	 * Release a scan credit for a model (Scan or ScanPage) if its credit_state is "claimed".
	 * Centralizes the claim-check + release + state-update pattern used by jobs and orchestrator.
	 */
	public function releaseCreditForModel(Model $model): void
	{
		if ($model->credit_state !== CreditState::Claimed->value) {
			return;
		}

		try {
			$organization = $model->project?->organization;

			if ($organization !== null) {
				$this->releaseScanCredit($organization);
			}

			$model->updateQuietly(array("credit_state" => CreditState::Released->value));
		} catch (\Throwable $exception) {
			Log::warning("Failed to release scan credit for model", array(
				"model_type" => class_basename($model),
				"model_id" => $model->getKey(),
				"error" => $exception->getMessage(),
			));
		}
	}

	/**
	 * Whether the project can add another competitor within the organization's plan limits.
	 */
	public function canAddCompetitor(Organization $organization, \App\Models\Project $project): bool
	{
		$plan = $organization->plan;
		$maxCompetitors = $plan?->max_competitors ?? 0;

		return $project->competitors()->count() < $maxCompetitors;
	}

	/**
	 * Whether the organization can add another team member within their plan limits.
	 */
	public function canAddMember(Organization $organization): bool
	{
		$plan = $organization->plan;
		$maxUsers = $plan?->max_users ?? 1;
		$currentMembers = $organization->users()->count();
		$pendingInvitations = $organization->teamInvitations()->pending()->count();

		return ($currentMembers + $pendingInvitations) < $maxUsers;
	}

	/**
	 * Resolve the Stripe price ID for a given plan and billing cycle.
	 *
	 * @throws \InvalidArgumentException When no price ID is configured
	 */
	private function resolvePriceId(Plan $plan, string $billingCycle): string
	{
		$priceId = $billingCycle === "annual"
			? $plan->stripe_annual_price_id
			: $plan->stripe_monthly_price_id;

		if (empty($priceId)) {
			throw new \InvalidArgumentException(
				"No Stripe price ID configured for {$plan->name} ({$billingCycle})"
			);
		}

		return $priceId;
	}

	/**
	 * Guard clause — throw if Stripe is not configured.
	 *
	 * @throws \RuntimeException
	 */
	private function ensureStripeConfigured(): void
	{
		if (!$this->isStripeConfigured()) {
			throw new \RuntimeException(
				"Stripe is not configured. Add STRIPE_KEY and STRIPE_SECRET to your .env file."
			);
		}
	}
}
