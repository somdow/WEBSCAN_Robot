<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
	/**
	 * Switch the user's active organization.
	 */
	public function switchOrganization(Request $request, Organization $organization): RedirectResponse
	{
		$isMember = $request->user()
			->organizations()
			->where("organizations.id", $organization->id)
			->exists();

		abort_unless($isMember, 403, "You are not a member of this organization.");

		session()->put("current_organization_id", $organization->id);

		return redirect()->route("dashboard")->with("success", "Switched to {$organization->name}.");
	}
}
