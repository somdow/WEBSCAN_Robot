<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutRequest extends FormRequest
{
	/**
	 * Only the organization owner can initiate checkout.
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
			"coupon_code" => array("nullable", "string", "max:50"),
		);
	}

	/**
	 * Additional validation: cannot checkout to the Free plan.
	 */
	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			$plan = Plan::find($this->input("plan_id"));

			if ($plan !== null && $plan->slug === "free") {
				$validator->errors()->add("plan_id", "You cannot subscribe to the Free plan through checkout.");
			}
		});
	}
}
