<?php

namespace App\Http\Controllers;

use App\Rules\SafeExternalUrl;
use App\Services\ProjectService;
use App\Services\Scanning\ScanDispatchService;
use App\Services\Utils\UrlNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
	public function store(
		Request $request,
		ProjectService $projectService,
		ScanDispatchService $scanDispatchService,
	): JsonResponse
	{
		$request->merge(array("url" => UrlNormalizer::prependScheme((string) $request->input("url", ""))));

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
			$scan = $scanDispatchService->dispatchFirstScan($project, $request->user(), $organization);
			if ($scan !== null) {
				$redirectUrl = route("projects.show", array("project" => $project, "scan" => $scan));
			}
		}

		return response()->json(array(
			"success" => true,
			"redirect" => $redirectUrl,
		));
	}
}
