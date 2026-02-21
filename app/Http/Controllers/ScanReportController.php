<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatus;
use App\Models\Scan;
use App\Services\PdfReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ScanReportController extends Controller
{
	/**
	 * Download a PDF report for a completed scan.
	 * GET /scans/{scan}/pdf
	 */
	public function download(Request $request, Scan $scan, PdfReportService $reportService): Response
	{
		Gate::authorize("access", $scan->project);

		if ($scan->status !== ScanStatus::Completed) {
			return redirect()
				->route("projects.show", array("project" => $scan->project, "scan" => $scan->getRouteKey()))
				->with("error", "PDF reports are only available for completed scans.");
		}

		return $reportService->generateReport($scan);
	}
}
