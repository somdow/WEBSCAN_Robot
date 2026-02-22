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
	private const REPORT_TYPE_LABELS = array(
		"full" => "Website Audit Report",
		"seo" => "SEO Audit Report",
		"health" => "Site Health Report",
	);

	private const REPORT_TYPE_FILENAME_PREFIX = array(
		"full" => "website-audit",
		"seo" => "seo-audit",
		"health" => "health-audit",
	);

	public function __construct(
		private readonly ModuleRegistry $moduleRegistry,
	) {}

	/**
	 * Generate a PDF report for a completed scan and return a download response.
	 */
	public function generateReport(Scan $scan, string $reportType = "full"): Response
	{
		$reportData = $this->buildReportData($scan, $reportType);
		$filename = $this->buildFilename($scan, $reportType);

		$pdf = Pdf::loadView("reports.scan-pdf", $reportData)
			->setPaper("a4", "portrait");

		return $pdf->download($filename);
	}

	/**
	 * Assemble all data the PDF template needs.
	 */
	public function buildReportData(Scan $scan, string $reportType = "full"): array
	{
		$scan->load(array("project.organization.plan", "moduleResults", "triggeredBy", "pages.moduleResults"));

		$groupedResults = $scan->moduleResults
			->groupBy(fn ($result) => $this->moduleRegistry->resolveCategory($result->module_key));

		/* Always-included modules: show stoppers + WordPress (shown in all report types) */
		$alwaysIncludeKeys = array("sslCertificate", "httpsRedirect", "wpDetection", "wpPlugins", "wpTheme");
		$alwaysIncludedResults = $scan->moduleResults
			->filter(fn ($r) => in_array($r->module_key, $alwaysIncludeKeys, true));

		/* Filter results to only categories relevant to the report type */
		$allowedCategories = $this->resolveAllowedCategories($reportType);
		if ($allowedCategories !== null) {
			$groupedResults = $groupedResults->filter(
				fn ($results, $category) => in_array($category, $allowedCategories, true)
			);

			/* Merge always-included modules back in under their categories */
			foreach ($alwaysIncludedResults as $result) {
				$category = $this->moduleRegistry->resolveCategory($result->module_key);
				if (!$groupedResults->has($category)) {
					$groupedResults[$category] = collect();
				}
				$existingKeys = $groupedResults[$category]->pluck("module_key")->all();
				if (!in_array($result->module_key, $existingKeys, true)) {
					$groupedResults[$category]->push($result);
				}
			}
		}

		$statusCounts = $this->countStatuses($groupedResults);
		$moduleLabels = $this->moduleRegistry->labelMap();

		/* Build set of module keys allowed for this report type (used to filter additional page results) */
		$categoryMap = $this->moduleRegistry->categoryMap();
		$allowedModuleKeys = $allowedCategories !== null
			? collect($categoryMap)->filter(fn ($cat) => in_array($cat, $allowedCategories, true))->keys()->all()
			: null;

		$scanPages = $scan->isCrawlScan()
			? $scan->pages
			: collect();

		$additionalPages = $scan->project
			->additionalPages()
			->where("analysis_status", "completed")
			->with("moduleResults")
			->get()
			->sortByDesc("page_score");

		$filteredModuleResults = $groupedResults->flatten(1);

		$hasAiSuggestions = $filteredModuleResults->contains(fn ($result) => !empty($result->ai_suggestion));
		$aiProviderLabel = null;
		if ($hasAiSuggestions && $scan->triggeredBy) {
			$provider = AiProvider::tryFrom($scan->triggeredBy->ai_provider ?? "");
			$aiProviderLabel = $provider?->label();
		}

		$aiResults = $filteredModuleResults
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
			"moduleCategoryMap" => $this->moduleRegistry->categoryMap(),
			"categoryDescriptions" => config("scan-ui.category_descriptions"),
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
			"reportType" => $reportType,
			"reportTypeLabel" => self::REPORT_TYPE_LABELS[$reportType] ?? self::REPORT_TYPE_LABELS["full"],
			"allowedModuleKeys" => $allowedModuleKeys,
		);
	}

	/**
	 * Resolve which categories to include based on report type.
	 * Returns null for "full" (include all), or an array of category names.
	 */
	private function resolveAllowedCategories(string $reportType): ?array
	{
		if ($reportType === "full") {
			return null;
		}

		$categoryGroups = config("scan-ui.score_category_groups", array());

		return $categoryGroups[$reportType] ?? null;
	}

	/**
	 * Count module results by status from pre-filtered grouped results.
	 */
	private function countStatuses(\Illuminate\Support\Collection $groupedResults): array
	{
		$counts = array("ok" => 0, "warning" => 0, "bad" => 0, "info" => 0);

		foreach ($groupedResults as $results) {
			foreach ($results as $result) {
				$status = $result->status->value;
				if (isset($counts[$status])) {
					$counts[$status]++;
				}
			}
		}

		return $counts;
	}

	/**
	 * Build a descriptive filename for the PDF download.
	 */
	private function buildFilename(Scan $scan, string $reportType = "full"): string
	{
		$domain = $scan->project->domain();
		$date = $scan->created_at->format("Y-m-d");
		$prefix = self::REPORT_TYPE_FILENAME_PREFIX[$reportType] ?? "website-audit";

		return "{$prefix}-{$domain}-{$date}.pdf";
	}
}
