<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePlanRequest;
use App\Http\Requests\CreateCheckoutRequest;
use App\Models\Organization;
use App\Services\BillingService;
use App\Services\OrganizationProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BillingController extends Controller
{
	public function __construct(
		private readonly BillingService $billingService,
		private readonly OrganizationProvisioningService $organizationProvisioningService,
	) {}

	/**
	 * Display the billing portal page.
	 */
	public function index(Request $request): View|RedirectResponse
	{
		$user = $request->user();
		$organization = $this->resolveOrganization($request);
		if ($organization instanceof RedirectResponse) {
			return $organization;
		}

		$plan = $organization->plan;
		$isOwner = $user->id === $organization->owner()?->id;

		$usage = $this->billingService->resolveCurrentUsage($organization);

		$availablePlans = \App\Models\Plan::public()
			->ordered()
			->where("id", "!=", $plan?->id)
			->get();

		$invoices = array();

		if ($this->billingService->isStripeConfigured() && $organization->hasStripeId()) {
			try {
				$invoices = $organization->invoices();
			} catch (\Exception $exception) {
				Log::warning("Failed to retrieve invoices", array(
					"organization_id" => $organization->id,
					"error" => $exception->getMessage(),
				));
			}
		}

		return view("billing.index", array(
			"organization" => $organization,
			"plan" => $plan,
			"usage" => $usage,
			"availablePlans" => $availablePlans,
			"invoices" => $invoices,
			"isOwner" => $isOwner,
			"isStripeConfigured" => $this->billingService->isStripeConfigured(),
		));
	}

	/**
	 * Create a Stripe Embedded Checkout session and return the client secret.
	 * The frontend uses this to mount the Stripe checkout iframe in a modal.
	 */
	public function checkout(CreateCheckoutRequest $request): JsonResponse
	{
		$this->authorizeOwner($request);

		if (!$this->billingService->isStripeConfigured()) {
			return response()->json(array(
				"error" => "Stripe is not configured yet. Please contact support.",
			), 503);
		}

		$plan = \App\Models\Plan::findOrFail($request->validated("plan_id"));
		$organization = $request->user()->currentOrganization();

		try {
			$clientSecret = $this->billingService->createCheckoutSession(
				$organization,
				$plan,
				$request->validated("billing_cycle"),
				$request->validated("coupon_code"),
			);

			return response()->json(array("clientSecret" => $clientSecret));
		} catch (\Exception $exception) {
			Log::error("Checkout session creation failed", array(
				"organization_id" => $organization->id,
				"plan_id" => $plan->id,
				"error" => $exception->getMessage(),
			));

			return response()->json(array(
				"error" => "Unable to start checkout. Please try again or contact support.",
			), 500);
		}
	}

	/**
	 * Display the post-checkout success page.
	 * Uses the session_id from Stripe's return URL to sync the plan immediately,
	 * so the user sees their new plan without waiting for webhooks.
	 */
	public function success(Request $request): View|RedirectResponse
	{
		$organization = $this->resolveOrganization($request);
		if ($organization instanceof RedirectResponse) {
			return $organization;
		}

		$sessionId = $request->query("session_id");

		if ($sessionId !== null && $this->billingService->isStripeConfigured()) {
			$this->billingService->syncPlanFromCheckoutSession($organization, $sessionId);
			$organization->refresh();
		}

		$plan = $organization->plan;

		return view("billing.success", array(
			"plan" => $plan,
			"organization" => $organization,
		));
	}

	/**
	 * Swap to a different plan (upgrade or downgrade).
	 */
	public function changePlan(ChangePlanRequest $request): RedirectResponse
	{
		$this->authorizeOwner($request);

		if ($redirect = $this->ensureStripeConfigured()) {
			return $redirect;
		}

		$targetPlan = \App\Models\Plan::findOrFail($request->validated("plan_id"));
		$organization = $request->user()->currentOrganization();

		if (!$organization->subscribed("default")) {
			return redirect()->route("billing.index")
				->with("error", "You need an active subscription to change plans. Please start a new subscription.");
		}

		try {
			$this->billingService->swapSubscription(
				$organization,
				$targetPlan,
				$request->validated("billing_cycle"),
			);

			return redirect()->route("billing.index")
				->with("success", "Your plan has been changed to {$targetPlan->name}.");
		} catch (\Exception $exception) {
			Log::error("Plan change failed", array(
				"organization_id" => $organization->id,
				"target_plan_id" => $targetPlan->id,
				"error" => $exception->getMessage(),
			));

			return redirect()->route("billing.index")
				->with("error", "Unable to change plan. Please try again or contact support.");
		}
	}

	/**
	 * Cancel the current subscription at end of period.
	 */
	public function cancel(Request $request): RedirectResponse
	{
		$this->authorizeOwner($request);

		$organization = $request->user()->currentOrganization();

		try {
			$this->billingService->cancelSubscription($organization);

			return redirect()->route("billing.index")
				->with("success", "Your subscription has been cancelled. You will retain access until the end of your current billing period.");
		} catch (\Exception $exception) {
			Log::error("Subscription cancellation failed", array(
				"organization_id" => $organization->id,
				"error" => $exception->getMessage(),
			));

			return redirect()->route("billing.index")
				->with("error", "Unable to cancel subscription. Please try again or contact support.");
		}
	}

	/**
	 * Resume a cancelled subscription during its grace period.
	 */
	public function resume(Request $request): RedirectResponse
	{
		$this->authorizeOwner($request);

		$organization = $request->user()->currentOrganization();

		try {
			$this->billingService->resumeSubscription($organization);

			return redirect()->route("billing.index")
				->with("success", "Your subscription has been resumed.");
		} catch (\Exception $exception) {
			Log::error("Subscription resume failed", array(
				"organization_id" => $organization->id,
				"error" => $exception->getMessage(),
			));

			return redirect()->route("billing.index")
				->with("error", "Unable to resume subscription. Please try again or contact support.");
		}
	}

	/**
	 * Redirect to Stripe's hosted Customer Portal for payment method updates.
	 */
	public function redirectToCustomerPortal(Request $request): RedirectResponse
	{
		$this->authorizeOwner($request);

		if ($redirect = $this->ensureStripeConfigured()) {
			return $redirect;
		}

		$organization = $request->user()->currentOrganization();

		try {
			return $organization->redirectToBillingPortal(route("billing.index"));
		} catch (\Exception $exception) {
			Log::error("Customer portal redirect failed", array(
				"organization_id" => $organization->id,
				"error" => $exception->getMessage(),
			));

			return redirect()->route("billing.index")
				->with("error", "Unable to open the payment portal. Please try again or contact support.");
		}
	}

	/**
	 * Download an invoice PDF.
	 * Validates the invoice belongs to this organization to prevent IDOR.
	 */
	public function downloadInvoice(Request $request, string $invoiceId)
	{
		$this->authorizeOwner($request);

		$organization = $request->user()->currentOrganization();

		$invoice = $organization->findInvoice($invoiceId);

		if ($invoice === null) {
			abort(404, "Invoice not found.");
		}

		return $organization->downloadInvoice($invoiceId, array(
			"vendor" => config("app.name", "HELLO WEB_SCANS"),
		));
	}

	/**
	 * Abort if the current user is not the organization owner.
	 */
	private function authorizeOwner(Request $request): void
	{
		abort_unless($request->user()->isOrganizationOwner(), 403, "Only the organization owner can manage billing.");
	}

	/**
	 * Return a redirect if Stripe is not configured, or null to continue.
	 */
	private function ensureStripeConfigured(): ?RedirectResponse
	{
		if (!$this->billingService->isStripeConfigured()) {
			return redirect()->route("billing.index")
				->with("error", "Stripe is not configured yet. Please add your Stripe API keys to the .env file.");
		}

		return null;
	}

	/**
	 * Resolve the user's current organization or redirect safely.
	 */
	private function resolveOrganization(Request $request): Organization|RedirectResponse
	{
		$user = $request->user();
		$organization = $this->organizationProvisioningService->ensureForUser($user);

		return $organization;
	}
}
