<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends WebhookController
{
	/**
	 * Handle subscription updated events — sync the local plan_id.
	 */
	protected function handleCustomerSubscriptionUpdated(array $payload): Response
	{
		$parentResponse = parent::handleCustomerSubscriptionUpdated($payload);

		$stripeId = $payload["data"]["object"]["customer"] ?? null;

		if ($stripeId !== null) {
			$organization = Organization::where("stripe_id", $stripeId)->first();

			if ($organization !== null) {
				app(BillingService::class)->syncPlanFromStripe($organization);
			}
		}

		return $parentResponse;
	}

	/**
	 * Handle subscription deleted events — downgrade to Free plan.
	 */
	protected function handleCustomerSubscriptionDeleted(array $payload): Response
	{
		$parentResponse = parent::handleCustomerSubscriptionDeleted($payload);

		$stripeId = $payload["data"]["object"]["customer"] ?? null;

		if ($stripeId !== null) {
			$organization = Organization::where("stripe_id", $stripeId)->first();

			if ($organization !== null) {
				app(BillingService::class)->downgradeToFree($organization);

				Log::info("Subscription deleted — organization downgraded to Free", array(
					"organization_id" => $organization->id,
				));
			}
		}

		return $parentResponse;
	}

	/**
	 * Handle checkout.session.completed — sync plan on initial subscription creation.
	 * This is the primary webhook for new subscriptions (complements the success page sync).
	 */
	protected function handleCheckoutSessionCompleted(array $payload): Response
	{
		$stripeId = $payload["data"]["object"]["customer"] ?? null;

		if ($stripeId !== null) {
			$organization = Organization::where("stripe_id", $stripeId)->first();

			if ($organization !== null) {
				app(BillingService::class)->syncPlanFromStripe($organization);

				Log::info("Plan synced via checkout.session.completed webhook", array(
					"organization_id" => $organization->id,
				));
			}
		}

		return $this->successMethod();
	}

	/**
	 * Handle payment failure events — log for now, notifications deferred to Sprint 8.
	 */
	protected function handleInvoicePaymentFailed(array $payload): Response
	{
		$stripeId = $payload["data"]["object"]["customer"] ?? null;

		Log::warning("Stripe invoice payment failed", array(
			"stripe_customer" => $stripeId,
			"invoice_id" => $payload["data"]["object"]["id"] ?? null,
			"amount_due" => $payload["data"]["object"]["amount_due"] ?? null,
		));

		return $this->successMethod();
	}
}
