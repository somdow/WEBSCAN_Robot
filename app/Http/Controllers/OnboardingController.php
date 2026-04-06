<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatus;
use App\Jobs\ProcessScanJob;
use App\Models\Scan;
use App\Rules\SafeExternalUrl;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
	public function store(Request $request, ProjectService $projectService): JsonResponse
	{
		/* Auto-prepend https:// for bare domains (matches ProjectFormRequest behavior) */
		$rawUrl = trim($request->input("url", ""));
		if ($rawUrl !== "" && !preg_match("#^https?://#i", $rawUrl)) {
			$request->merge(array("url" => "https://" . $rawUrl));
		}

		$validated = $request->validate(array(
			"name" => "required|string|max:255",
			"url" => array("required", "string", "url:http,https", "max:2048", new SafeExternalUrl()),
			"target_keywords" => "nullable|string|max:1000",
			"trigger_scan" => "boolean",
		));

		$organization = $request->user()->currentOrganization();
		$project = $projectService->createProject($organization, $validated);

		$redirectUrl = route("projects.show", $project);

		if ($validated["trigger_scan"] ?? false) {
			try {
				$scan = Scan::create(array(
					"project_id" => $project->id,
					"triggered_by" => $request->user()->id,
					"status" => ScanStatus::Pending,
				));

				ProcessScanJob::dispatch($scan);
				$redirectUrl = route("projects.show", array("project" => $project, "scan" => $scan));
			} catch (\Throwable $exception) {
				Log::warning("Onboarding scan dispatch failed", array(
					"project_id" => $project->id,
					"error" => $exception->getMessage(),
				));
			}
		}

		return response()->json(array(
			"success" => true,
			"redirect" => $redirectUrl,
		));
	}
}
