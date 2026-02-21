<?php

namespace App\Services;

use App\Enums\AiProvider;
use App\Models\Scan;
use App\Models\Setting;
use App\Services\Scanning\ModuleRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfReportService
{
	public function __construct(
		private readonly ModuleRegistry $moduleRegistry,
	) {}

	/**
	 * Generate a PDF report for a completed scan and return a download response.
	 */
	public function generateReport(Scan $scan): Response
	{
		$reportData = $this->buildReportData($scan);
		$filename = $this->buildFilename($scan);

		$pdf = Pdf::loadView("reports.scan-pdf", $reportData)
			->setPaper("a4", "portrait");

		return $pdf->download($filename);
	}

	/**
	 * Assemble all data the PDF template needs.
	 */
	public function buildReportData(Scan $scan): array
	{
		$scan->load(array("project.organization.plan", "moduleResults", "triggeredBy", "pages.moduleResults"));

		$groupedResults = $scan->moduleResults
			->groupBy(fn ($result) => $this->moduleRegistry->resolveCategory($result->module_key));

		$statusCounts = $scan->resultsByStatus();
		$moduleLabels = $this->moduleRegistry->labelMap();

		$scanPages = $scan->isCrawlScan()
			? $scan->pages
			: collect();

		$additionalPages = $scan->project
			->additionalPages()
			->where("analysis_status", "completed")
			->with("moduleResults")
			->get()
			->sortByDesc("page_score");

		$hasAiSuggestions = $scan->moduleResults->contains(fn ($result) => !empty($result->ai_suggestion));
		$aiProviderLabel = null;
		if ($hasAiSuggestions && $scan->triggeredBy) {
			$provider = AiProvider::tryFrom($scan->triggeredBy->ai_provider ?? "");
			$aiProviderLabel = $provider?->label();
		}

		/* All module results that have AI suggestions (shown in dedicated AI section) */
		$aiResults = $scan->moduleResults
			->filter(fn ($result) => !empty($result->ai_suggestion))
			->values();

		$reportAuthor = $scan->triggeredBy;

		$organization = $scan->project->organization;
		$siteName = Setting::getValue("site_name", config("app.name", "HELLO WEB_SCANS"));

		/* White-label branding: custom name, color, logo for eligible plans */
		$pdfBrandName = $organization->canWhiteLabel() ? $organization->pdfBrandName() : $siteName;
		$accentColor = $organization->pdfAccentColor();
		$logoPath = $organization->pdfLogoFilePath();

		return array(
			"scan" => $scan,
			"project" => $scan->project,
			"organization" => $organization,
			"groupedResults" => $groupedResults,
			"statusCounts" => $statusCounts,
			"moduleLabels" => $moduleLabels,
			"scanPages" => $scanPages,
			"additionalPages" => $additionalPages,
			"aiResults" => $aiResults,
			"hasAiSuggestions" => $hasAiSuggestions,
			"aiProviderLabel" => $aiProviderLabel,
			"reportAuthor" => $reportAuthor,
			"generatedAt" => now()->format("F j, Y \\a\\t g:i A"),
			"siteName" => $siteName,
			"pdfBrandName" => $pdfBrandName,
			"accentColor" => $accentColor,
			"logoPath" => $logoPath,
		);
	}

	/**
	 * Build a descriptive filename for the PDF download.
	 */
	private function buildFilename(Scan $scan): string
	{
		$domain = $scan->project->domain();
		$date = $scan->created_at->format("Y-m-d");

		return "website-audit-{$domain}-{$date}.pdf";
	}
}
