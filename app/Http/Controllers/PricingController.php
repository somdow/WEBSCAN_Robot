<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
	/**
	 * Display the public pricing page.
	 */
	public function __invoke(Request $request): View
	{
		$plans = Plan::public()->ordered()->get();

		$currentPlanId = null;

		if ($request->user() !== null) {
			$organization = $request->user()->currentOrganization();
			$currentPlanId = $organization?->plan_id;
		}

		return view("pricing", array(
			"plans" => $plans,
			"currentPlanId" => $currentPlanId,
		));
	}
}
