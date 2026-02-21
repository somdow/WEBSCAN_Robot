<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBrandingRequest;
use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingController extends Controller
{
	public function edit(Request $request): View
	{
		$organization = $request->user()->currentOrganization();
		$defaultAccentColor = config("scan-ui.pdf_default_accent_color");

		return view("branding.edit", array(
			"organization" => $organization,
			"canWhiteLabel" => $organization->canWhiteLabel(),
			"defaultAccentColor" => $defaultAccentColor,
			"siteName" => Setting::getValue("site_name", config("app.name", "HELLO WEB_SCANS")),
		));
	}

	public function update(UpdateBrandingRequest $request): RedirectResponse
	{
		$organization = $request->user()->currentOrganization();

		if (!$organization->canWhiteLabel()) {
			abort(403);
		}

		$organization->update(array(
			"pdf_company_name" => $request->input("pdf_company_name"),
			"brand_color" => $request->input("brand_color"),
		));

		if ($request->hasFile("logo")) {
			$this->storeLogo($organization, $request);
		}

		return redirect()->route("branding.edit")
			->with("status", "Branding settings saved.");
	}

	public function destroyLogo(Request $request): RedirectResponse
	{
		$organization = $request->user()->currentOrganization();

		if (!$organization->canWhiteLabel()) {
			abort(403);
		}

		$this->deleteLogo($organization);

		return redirect()->route("branding.edit")
			->with("status", "Logo removed.");
	}

	private function storeLogo(Organization $organization, UpdateBrandingRequest $request): void
	{
		try {
			$this->deleteLogo($organization);

			$file = $request->file("logo");
			$extension = $file->guessExtension() ?: ($file->getClientOriginalExtension() ?: "png");
			$filename = "{$organization->id}.{$extension}";

			$storedPath = Storage::disk("public")->putFileAs("logos", $file, $filename);

			$organization->update(array("logo_path" => $storedPath));
		} catch (\Throwable $exception) {
			Log::error("Logo storage failed", array(
				"organization_id" => $organization->id,
				"error" => $exception->getMessage(),
			));
			throw $exception;
		}
	}

	private function deleteLogo(Organization $organization): void
	{
		if (empty($organization->logo_path)) {
			return;
		}

		try {
			Storage::disk("public")->delete($organization->logo_path);
		} catch (\Throwable $exception) {
			Log::warning("Logo deletion failed", array(
				"organization_id" => $organization->id,
				"path" => $organization->logo_path,
				"error" => $exception->getMessage(),
			));
		}

		$organization->update(array("logo_path" => null));
	}
}
