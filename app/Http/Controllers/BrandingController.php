<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBrandingRequest;
use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BrandingController extends Controller
{
	public function edit(Request $request): View
	{
		$organization = $request->user()->currentOrganization();
		$defaultAccentColor = config("scan-ui.pdf_default_accent_color");
		$logoUrl = $organization->logoUrl();

		return view("branding.edit", array(
			"organization" => $organization,
			"canWhiteLabel" => $organization->canWhiteLabel(),
			"defaultAccentColor" => $defaultAccentColor,
			"siteName" => Setting::getValue("site_name", config("app.name", "HELLO WEB_SCANS")),
			"logoUrl" => $logoUrl,
		));
	}

	public function update(UpdateBrandingRequest $request): RedirectResponse
	{
		$organization = $request->user()->currentOrganization();

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
			$file = $request->file("logo");
			if (!$file instanceof UploadedFile || !$file->isValid()) {
				throw ValidationException::withMessages(array(
					"logo" => "Logo upload failed. Please choose the file again.",
				));
			}

			$extension = strtolower($file->guessExtension() ?: ($file->getClientOriginalExtension() ?: "png"));
			if (!in_array($extension, array("jpg", "jpeg", "png"), true)) {
				$extension = "png";
			}

			$filename = "{$organization->getKey()}.{$extension}";
			$storedPath = "logos/{$filename}";
			$previousPath = $organization->logo_path;

			$written = Storage::disk("public")->put($storedPath, $file->get());
			if (!$written) {
				throw new \RuntimeException("Unable to write logo file to storage.");
			}

			$organization->update(array("logo_path" => $storedPath));

			if (!empty($previousPath) && $previousPath !== $storedPath) {
				Storage::disk("public")->delete($previousPath);
			}
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
