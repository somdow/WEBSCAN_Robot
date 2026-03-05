<?php

namespace App\Filament\Resources;

use App\Enums\ScanStatus;
use App\Filament\Resources\ScanResource\Pages;
use App\Filament\Resources\ScanResource\RelationManagers;
use App\Models\Scan;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ScanResource extends Resource
{
	protected static ?string $model = Scan::class;

	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

	protected static string | \UnitEnum | null $navigationGroup = "Content";

	public static function canCreate(): bool
	{
		return false;
	}

	public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
	{
		return false;
	}

	public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
	{
		return false;
	}

	public static function form(Schema $schema): Schema
	{
		return $schema->columns(1)->components(array(
			Section::make("")->schema(array(
				Placeholder::make("scan_header")
					->label("")
					->content(fn (Scan $record): HtmlString => self::renderScanHeader($record)),
			)),
			Section::make("Scores")->schema(array(
				Placeholder::make("score_cards")
					->label("")
					->content(fn (Scan $record): HtmlString => self::renderScoreCards($record)),
			)),
			Section::make("Scan Details")->schema(array(
				Placeholder::make("scan_details")
					->label("")
					->content(fn (Scan $record): HtmlString => self::renderDetailGrid($record)),
			))->collapsible(),
		));
	}

	private static function renderScanHeader(Scan $record): HtmlString
	{
		$url = e($record->project?->url ?? "N/A");
		$projectName = e($record->project?->name ?? "N/A");
		$orgName = e($record->project?->organization?->name ?? "N/A");
		$triggeredBy = e($record->triggeredBy?->name ?? "System");
		$scannedAt = e($record->created_at?->format("M j, Y g:i A") ?? "N/A");

		$statusLabel = e($record->status->label());
		$statusColor = match ($record->status) {
			ScanStatus::Completed => "#059669",
			ScanStatus::Running, ScanStatus::Pending => "#d97706",
			ScanStatus::Failed, ScanStatus::Blocked => "#dc2626",
			default => "#6b7280",
		};
		$statusBgColor = match ($record->status) {
			ScanStatus::Completed => "#ecfdf5",
			ScanStatus::Running, ScanStatus::Pending => "#fffbeb",
			ScanStatus::Failed, ScanStatus::Blocked => "#fef2f2",
			default => "#f9fafb",
		};

		return new HtmlString(
			"<div style=\"display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;\">"
				. "<div style=\"min-width:0;\">"
					. "<div style=\"font-size:20px; font-weight:700; color:#111827; word-break:break-all;\">{$url}</div>"
					. "<div style=\"font-size:14px; color:#6b7280; margin-top:4px;\">"
						. "{$projectName} &middot; {$orgName} &middot; by {$triggeredBy} &middot; {$scannedAt}"
					. "</div>"
				. "</div>"
				. "<div style=\"flex-shrink:0;\">"
					. "<span style=\"display:inline-block; padding:4px 14px; border-radius:9999px; font-size:13px; font-weight:600; color:{$statusColor}; background:{$statusBgColor};\">"
						. "{$statusLabel}"
					. "</span>"
				. "</div>"
			. "</div>"
		);
	}

	private static function renderScoreCards(Scan $record): HtmlString
	{
		$overallScore = $record->overall_score;
		$seoScore = $record->seo_score;
		$healthScore = $record->health_score;

		$cardHtml = "<div style=\"display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;\">";
		$cardHtml .= self::buildScoreCard("Overall", $overallScore);
		$cardHtml .= self::buildScoreCard("SEO", $seoScore);
		$cardHtml .= self::buildScoreCard("Health", $healthScore);
		$cardHtml .= "</div>";

		return new HtmlString($cardHtml);
	}

	private static function buildScoreCard(string $label, ?int $score): string
	{
		$displayScore = $score !== null ? (string) $score : "—";
		$scoreColor = "#6b7280";
		$scoreBg = "#f9fafb";

		if ($score !== null) {
			if ($score >= 80) {
				$scoreColor = "#059669";
				$scoreBg = "#ecfdf5";
			} elseif ($score >= 50) {
				$scoreColor = "#d97706";
				$scoreBg = "#fffbeb";
			} else {
				$scoreColor = "#dc2626";
				$scoreBg = "#fef2f2";
			}
		}

		return "<div style=\"text-align:center; padding:20px; border-radius:12px; background:{$scoreBg}; border:1px solid {$scoreColor}22;\">"
			. "<div style=\"font-size:36px; font-weight:800; color:{$scoreColor}; line-height:1;\">{$displayScore}</div>"
			. "<div style=\"font-size:13px; font-weight:600; color:#6b7280; margin-top:6px; text-transform:uppercase; letter-spacing:0.05em;\">{$label}</div>"
			. "</div>";
	}

	private static function renderDetailGrid(Scan $record): HtmlString
	{
		$duration = $record->scan_duration_ms !== null
			? round($record->scan_duration_ms / 1000, 1) . "s"
			: "N/A";

		$fetcher = match ($record->fetcher_used) {
			"guzzle" => "Guzzle",
			"zyte" => "Zyte API",
			default => "N/A",
		};

		$cmsDetection = match ($record->detection_method) {
			"whatcms_api" => "WhatCMS API",
			"html_signals" => "HTML Signals",
			"rss_feed" => "RSS Feed",
			default => "N/A",
		};

		$scanType = match ($record->scan_type) {
			"crawl" => "Crawl",
			default => "Single Page",
		};

		$isWordPress = $record->is_wordpress ? "Yes" : "No";
		$pagesCrawled = $record->pages_crawled !== null ? (string) $record->pages_crawled : "N/A";

		$cellStyle = "padding:10px 0; border-bottom:1px solid #f3f4f6;";
		$labelStyle = "font-size:13px; color:#6b7280; font-weight:500;";
		$valueStyle = "font-size:14px; color:#111827; font-weight:600; margin-top:2px;";

		$rows = array(
			array("Scan Type", e($scanType)),
			array("Pages Crawled", e($pagesCrawled)),
			array("Duration", e($duration)),
			array("Fetcher", e($fetcher)),
			array("CMS Detection", e($cmsDetection)),
			array("WordPress", e($isWordPress)),
		);

		$gridHtml = "<div style=\"display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 32px;\">";
		foreach ($rows as $row) {
			$gridHtml .= "<div style=\"{$cellStyle}\">"
				. "<div style=\"{$labelStyle}\">{$row[0]}</div>"
				. "<div style=\"{$valueStyle}\">{$row[1]}</div>"
				. "</div>";
		}
		$gridHtml .= "</div>";

		return new HtmlString($gridHtml);
	}

	public static function table(Table $table): Table
	{
		return $table
			->recordUrl(fn (Scan $record) => static::getUrl("view", array("record" => $record)))
			->columns(array(
				TextColumn::make("project.name")
					->label("Project")
					->searchable()
					->sortable(),
				TextColumn::make("project.organization.name")
					->label("Organization")
					->sortable(),
				TextColumn::make("status")
					->badge()
					->formatStateUsing(fn (ScanStatus $state): string => $state->label())
					->color(fn (ScanStatus $state): string => $state->color()),
				TextColumn::make("scan_type")
					->label("Type")
					->badge()
					->formatStateUsing(fn (?string $state): string => match ($state) {
						"crawl" => "Crawl",
						default => "Single",
					})
					->color(fn (?string $state): string => match ($state) {
						"crawl" => "info",
						default => "gray",
					}),
				TextColumn::make("overall_score")
					->label("Scan Score")
					->numeric()
					->sortable()
					->placeholder("N/A"),
				TextColumn::make("pages_crawled")
					->label("Pages")
					->numeric()
					->sortable()
					->placeholder("—"),
				TextColumn::make("scan_duration_ms")
					->label("Duration")
					->formatStateUsing(fn ($state) => $state !== null ? round($state / 1000, 1) . "s" : "N/A")
					->sortable(),
				TextColumn::make("fetcher_used")
					->label("Fetcher")
					->badge()
					->formatStateUsing(fn (?string $state): string => match ($state) {
						"guzzle" => "Guzzle",
						"zyte" => "Zyte API",
						default => "—",
					})
					->color(fn (?string $state): string => match ($state) {
						"guzzle" => "gray",
						"zyte" => "info",
						default => "gray",
					}),
				TextColumn::make("detection_method")
					->label("CMS Detection")
					->badge()
					->formatStateUsing(fn (?string $state): string => match ($state) {
						"whatcms_api" => "WhatCMS API",
						"html_signals" => "HTML Signals",
						"rss_feed" => "RSS Feed",
						default => "—",
					})
					->color(fn (?string $state): string => match ($state) {
						"whatcms_api" => "success",
						"html_signals" => "warning",
						"rss_feed" => "info",
						default => "gray",
					}),
				TextColumn::make("triggeredBy.name")
					->label("Triggered By")
					->placeholder("System"),
				TextColumn::make("created_at")
					->dateTime()
					->sortable(),
			))
			->defaultSort("created_at", "desc")
			->filters(array(
				SelectFilter::make("status")
					->options(array(
						ScanStatus::Pending->value => ScanStatus::Pending->label(),
						ScanStatus::Running->value => ScanStatus::Running->label(),
						ScanStatus::Completed->value => ScanStatus::Completed->label(),
						ScanStatus::Failed->value => ScanStatus::Failed->label(),
						ScanStatus::Blocked->value => ScanStatus::Blocked->label(),
					)),
			));
	}

	public static function getRelations(): array
	{
		return array(
			RelationManagers\ModuleResultsRelationManager::class,
		);
	}

	public static function getPages(): array
	{
		return array(
			"index" => Pages\ListScans::route("/"),
			"view" => Pages\ViewScan::route("/{record}"),
		);
	}
}
