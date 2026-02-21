<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;

class ChangePlanRequest extends FormRequest
{
	/**
	 * Only the organization owner can change plans.
	 */
	public function authorize(): bool
	{
		return $this->user()?->isOrganizationOwner() === true;
	}

	public function rules(): array
	{
		return array(
			"plan_id" => array("required", "integer", "exists:plans,id"),
			"billing_cycle" => array("required", "string", "in:monthly,annual"),
		);
	}

	/**
	 * Additional validation: cannot switch to current plan or to Free plan.
	 */
	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			$targetPlan = Plan::find($this->input("plan_id"));
			$currentPlanId = $this->user()?->currentOrganization()?->plan_id;

			if ($targetPlan !== null && $targetPlan->id === $currentPlanId) {
				$validator->errors()->add("plan_id", "You are already on this plan.");
			}

			if ($targetPlan !== null && $targetPlan->slug === "free") {
				$validator->errors()->add("plan_id", "To downgrade to Free, please cancel your subscription instead.");
			}
		});
	}
}
