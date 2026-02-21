<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>{{ $pdfBrandName }} - Website Audit Report - {{ $project->name }}</title>
	<style>
		* { margin: 0; padding: 0; }
		body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; line-height: 1.5; margin: 40px; }

		/* ── Cover Page ── */
		.cover { margin-bottom: 20px; }
		.cover-top { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
		.cover-top td { vertical-align: top; }
		.brand { font-size: 24px; font-weight: bold; color: #111827; }
		.brand-sub { font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
		.brand-bar { height: 3px; background-color: {{ $accentColor }}; margin-bottom: 30px; width: 60px; }

		.score-row { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-bottom: 24px; }
		.score-box { text-align: center; padding: 20px; border: 4px solid #E5E7EB; border-radius: 12px; width: 160px; }
		.score-box-sm { width: 50%; padding: 16px 10px; }
		.score-value { font-size: 72px; font-weight: bold; line-height: 1; }
		.score-value-sm { font-size: 52px; }
		.score-label { font-size: 13px; color: #4B5563; margin-top: 6px; }
		.score-label-sub { font-size: 11px; }
		.score-green { color: #059669; border-color: #10B981; background-color: #F0FDF4; }
		.score-amber { color: #D97706; border-color: #F59E0B; background-color: #FFFBEB; }
		.score-red { color: #DC2626; border-color: #EF4444; background-color: #FEF2F2; }
		.score-na { color: #9CA3AF; border-color: #E5E7EB; background-color: #F9FAFB; }

		.cover-title-pre { font-size: 14px; color: #6B7280; margin-bottom: 4px; }
		.cover-title { font-size: 28px; font-weight: bold; color: #111827; line-height: 1.2; margin-bottom: 4px; }
		.cover-url { font-size: 13px; color: #6B7280; margin-bottom: 14px; }
		.cover-intro { font-size: 13px; color: #6B7280; line-height: 1.7; margin-bottom: 24px; }



		/* ── Divider ── */
		.divider { border-top: 1px solid #E5E7EB; margin: 20px 0; }

		/* ── Cover Meta ── */
		.meta-table { width: 100%; border-collapse: collapse; }
		.meta-table td { padding: 5px 0; vertical-align: top; font-size: 11px; }
		.meta-label { color: #9CA3AF; width: 120px; text-transform: uppercase; letter-spacing: 0.5px; }
		.meta-value { color: #374151; }
		.keyword-tag { display: inline-block; padding: 2px 7px; margin: 1px 3px 1px 0; background-color: #fff7ed; color: {{ $accentColor }}; font-size: 10px; border-radius: 3px; }

		/* ── Shared ── */
		.page-break { page-break-before: always; }
		.avoid-break { page-break-inside: avoid; }
		.section { margin-bottom: 20px; }

		/* ── Section Headers ── */
		.section-header { margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px solid #E5E7EB; }
		.section-icon { font-size: 18px; margin-right: 6px; }
		.section-title { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
		.section-subtitle { font-size: 12px; color: #6B7280; margin-top: 4px; }
		.section-green .section-header { border-bottom-color: #10B981; }
		.section-green .section-title { color: #059669; }
		.section-amber .section-header { border-bottom-color: #F59E0B; }
		.section-amber .section-title { color: #D97706; }
		.section-red .section-header { border-bottom-color: #EF4444; }
		.section-red .section-title { color: #DC2626; }
		.section-blue .section-header { border-bottom-color: #6366F1; }
		.section-blue .section-title { color: {{ $accentColor }}; }
		.section-indigo .section-header { border-bottom-color: {{ $accentColor }}; }
		.section-indigo .section-title { color: {{ $accentColor }}; }

		/* ── Cards ── */
		.card { margin-bottom: 14px; padding: 14px; border: 1px solid #E5E7EB; border-left: 4px solid #10B981; border-radius: 4px; }
		.card-warning { border-left-color: #F59E0B; }
		.card-fail { border-left-color: #EF4444; }
		.card-info { border-left-color: #6366F1; }
		.card-header { margin-bottom: 6px; }
		.card-title { font-size: 14px; font-weight: bold; color: #111827; }
		.card-badge { display: inline-block; padding: 2px 7px; font-size: 9px; font-weight: bold; text-transform: uppercase; border-radius: 3px; margin-left: 6px; background-color: #D1FAE5; color: #065F46; }
		.badge-warning { background-color: #FEF3C7; color: #92400E; }
		.badge-fail { background-color: #FEE2E2; color: #991B1B; }
		.badge-info { background-color: #E0E7FF; color: #3730A3; }
		.card-findings { margin: 8px 0; }
		.card-finding { padding: 2px 0 2px 14px; font-size: 11px; color: #374151; position: relative; }
		.card-finding::before { content: "\2022"; position: absolute; left: 4px; color: #9CA3AF; }

		/* ── Insight Box ── */
		.card-insight { margin-top: 10px; padding: 10px 12px; background-color: #F8FAFC; border-radius: 4px; }
		.card-insight-amber { background-color: #FFFBEB; }
		.card-insight-red { background-color: #FEF2F2; }
		.card-insight-title { font-size: 10px; font-weight: bold; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
		.card-insight-text { font-size: 11px; color: #6B7280; line-height: 1.6; }
		.card-passing { font-size: 11px; color: #059669; margin-top: 4px; font-style: italic; }

		/* ── Recommendations ── */
		.card-recs { margin-top: 8px; padding: 8px 10px; background-color: #FFFDF5; border-radius: 4px; }
		.card-recs-title { font-size: 10px; font-weight: bold; color: #92400E; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
		.card-rec { padding: 2px 0 2px 14px; font-size: 11px; color: #78350F; position: relative; }
		.card-rec::before { content: "\25B8"; position: absolute; left: 4px; color: #D97706; }
		.card-rec-red { color: #991B1B; }
		.card-rec-red::before { color: #EF4444; }
		.card-recs-red { background-color: #FFF5F5; }
		.card-recs-red .card-recs-title { color: #991B1B; }

		/* ── AI Suggestion Box ── */
		.card-ai { margin-top: 8px; padding: 8px 10px; background-color: #E0E0F0; border-radius: 4px; border-left: 3px solid #5555AA; }
		.card-ai-title { font-size: 10px; font-weight: bold; color: #5555AA; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
		.card-ai-text { font-size: 11px; color: #374151; line-height: 1.5; }
		.card-ai-text p { margin-bottom: 4px; }
		.card-ai-text p:last-child { margin-bottom: 0; }
		.card-ai-text ul, .card-ai-text ol { margin-left: 16px; margin-bottom: 4px; }
		.card-ai-text ul { list-style-type: disc; }
		.card-ai-text ol { list-style-type: decimal; }
		.card-ai-text li { margin-bottom: 2px; }
		.card-ai-text strong { font-weight: bold; }
		.card-ai-text code { background-color: #D5D5E5; padding: 1px 4px; border-radius: 2px; font-size: 10px; }
		.card-ai-text pre { background-color: #D5D5E5; padding: 6px 8px; border-radius: 3px; font-size: 10px; margin-bottom: 4px; overflow: hidden; }
		.card-ai-text pre code { background: transparent; padding: 0; }
		.ai-page-type-badge { display: inline-block; padding: 2px 8px; background-color: {{ $accentColor }}; color: #FFFFFF; font-size: 9px; font-weight: bold; border-radius: 3px; text-transform: uppercase; letter-spacing: 0.5px; }

		/* ── Crawled Pages Table ── */
		.pages-table { width: 100%; border-collapse: collapse; font-size: 11px; }
		.pages-table th { padding: 6px 8px; background-color: #F9FAFB; font-weight: bold; color: #6B7280; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; border-bottom: 2px solid #E5E7EB; text-align: left; }
		.pages-table td { padding: 5px 8px; border-bottom: 1px solid #F3F4F6; color: #374151; }
		.pages-table .page-url { max-width: 320px; overflow: hidden; }
		.page-score { font-weight: bold; font-size: 13px; }
		.page-score-green { color: #059669; }
		.page-score-amber { color: #D97706; }
		.page-score-red { color: #DC2626; }
		.page-score-gray { color: #9CA3AF; }
		.page-status-count { display: inline-block; padding: 1px 5px; font-size: 9px; font-weight: bold; border-radius: 3px; margin-right: 2px; }
		.page-status-ok { background-color: #D1FAE5; color: #065F46; }
		.page-status-warn { background-color: #FEF3C7; color: #92400E; }
		.page-status-bad { background-color: #FEE2E2; color: #991B1B; }
		.page-ai-label { display: inline-block; font-size: 9px; color: #6B7280; margin-bottom: 2px; }

		/* ── WordPress Detail Tables ── */
		.wp-detail-section { margin-top: 8px; margin-bottom: 6px; }
		.wp-detail-header { font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 6px; padding: 6px 10px; background-color: #F3F4F6; border-radius: 4px; }
		.wp-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 6px; }
		.wp-table th { padding: 5px 8px; background-color: #F9FAFB; font-weight: bold; color: #6B7280; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; border-bottom: 2px solid #E5E7EB; text-align: left; }
		.wp-table td { padding: 5px 8px; border-bottom: 1px solid #F3F4F6; color: #374151; vertical-align: top; }
		.wp-row-red { background-color: #FEF2F2; }
		.wp-row-amber { background-color: #FFFBEB; }
		.wp-status-red { display: inline-block; padding: 1px 6px; background-color: #FEE2E2; color: #991B1B; font-weight: bold; font-size: 9px; border-radius: 3px; }
		.wp-status-amber { display: inline-block; padding: 1px 6px; background-color: #FEF3C7; color: #92400E; font-weight: bold; font-size: 9px; border-radius: 3px; }
		.wp-status-green { display: inline-block; padding: 1px 6px; background-color: #D1FAE5; color: #065F46; font-weight: bold; font-size: 9px; border-radius: 3px; }
		.wp-status-gray { display: inline-block; padding: 1px 6px; background-color: #F3F4F6; color: #6B7280; font-weight: bold; font-size: 9px; border-radius: 3px; }
		.wp-tag-red { display: inline-block; padding: 1px 5px; background-color: #FEE2E2; color: #991B1B; font-size: 9px; font-weight: bold; border-radius: 3px; margin-left: 4px; }
		.wp-tag-amber { display: inline-block; padding: 1px 5px; background-color: #FEF3C7; color: #92400E; font-size: 9px; font-weight: bold; border-radius: 3px; margin-left: 4px; }
		.wp-tag-purple { display: inline-block; padding: 1px 5px; background-color: #EDE9FE; color: #5B21B6; font-size: 8px; font-weight: bold; border-radius: 2px; margin-left: 3px; text-transform: uppercase; }
		.wp-vuln-row td { background-color: #FFF5F5; padding: 4px 8px 4px 20px; font-size: 10px; color: #991B1B; border-bottom: 1px solid #FECACA; }
		.wp-vuln-icon { color: #DC2626; margin-right: 2px; }
		.wp-vuln-section { margin-top: 6px; padding: 8px 10px; background-color: #FEF2F2; border-radius: 4px; border-left: 3px solid #EF4444; }
		.wp-vuln-header { font-size: 10px; font-weight: bold; color: #991B1B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
		.wp-vuln-item { padding: 3px 0; font-size: 10px; color: #991B1B; border-bottom: 1px solid #FECACA; }
		.wp-vuln-item:last-child { border-bottom: none; }
		.wp-cvss { display: inline-block; padding: 0 4px; background-color: #FEE2E2; color: #991B1B; font-size: 9px; font-weight: bold; border-radius: 2px; margin-left: 4px; }
		.wp-fixed { display: inline-block; padding: 0 4px; background-color: #D1FAE5; color: #065F46; font-size: 9px; border-radius: 2px; margin-left: 4px; }
		.wp-unfixed { display: inline-block; padding: 0 4px; background-color: #FEE2E2; color: #991B1B; font-size: 9px; border-radius: 2px; margin-left: 4px; }

		/* ── Contact Info ── */
		.contact-section { margin-top: 24px; }
		.contact-label { font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
		.contact-name { font-size: 15px; font-weight: bold; color: #111827; margin-bottom: 2px; }
		.contact-detail { font-size: 12px; color: #4B5563; margin-bottom: 1px; }
		.outro-contact { margin-top: 20px; }
		.outro-contact-name { font-size: 14px; font-weight: bold; color: #111827; margin-bottom: 2px; }
		.outro-contact-detail { font-size: 12px; color: #6B7280; }

		/* ── Outro / Closing Page ── */
		.outro { text-align: center; padding: 60px 40px 40px; }
		.outro-bar { height: 3px; width: 60px; background-color: {{ $accentColor }}; margin: 0 auto 30px; }
		.outro-title { font-size: 22px; font-weight: bold; color: #111827; margin-bottom: 10px; }
		.outro-text { font-size: 13px; color: #6B7280; line-height: 1.8; margin-bottom: 24px; max-width: 440px; margin-left: auto; margin-right: auto; }
		.outro-steps { max-width: 400px; margin: 0 auto 30px; border-collapse: collapse; }
		.outro-step-num-cell { width: 30px; padding: 6px 0; vertical-align: middle; text-align: center; }
		.outro-step-text-cell { padding: 6px 0; vertical-align: middle; font-size: 12px; color: #374151; text-align: left; }
		.outro-step-num { display: inline-block; width: 22px; height: 18px; padding-top: 4px; line-height: 1; text-align: center; background-color: {{ $accentColor }}; color: #FFFFFF; font-size: 10px; font-weight: bold; border-radius: 50%; }
		.outro-divider { border-top: 1px solid #E5E7EB; margin: 24px auto; max-width: 200px; }
		.outro-brand { font-size: 16px; font-weight: bold; color: #111827; margin-bottom: 4px; }
		.outro-tagline { font-size: 11px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 1px; }

		/* ── Footer ── */
		.footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 0; font-size: 9px; color: #9CA3AF; text-align: center; }
	</style>
</head>
<body>
	<div class="footer">Generated by {{ $siteName }} &mdash; {{ $generatedAt }}</div>

	@php
		$resolveScoreClass = function (?int $score): string {
			if ($score === null) return "score-na";
			if ($score >= 80) return "score-green";
			if ($score >= 50) return "score-amber";
			return "score-red";
		};
		$overallClass = $resolveScoreClass($scan->overall_score);
		$seoClass = $resolveScoreClass($scan->seo_score);
		$healthClass = $resolveScoreClass($scan->health_score);
		$totalModules = ($statusCounts["ok"] ?? 0) + ($statusCounts["warning"] ?? 0) + ($statusCounts["bad"] ?? 0) + ($statusCounts["info"] ?? 0);
	@endphp

	{{-- ── Page 1: Cover ── --}}
	<div class="cover">
		<table class="cover-top">
			<tr>
				<td>
					@if($logoPath)
						<img src="{{ $logoPath }}" style="max-width: 120px; max-height: 40px; margin-bottom: 4px;" alt="{{ $pdfBrandName }}">
					@endif
					<div class="brand">{{ $pdfBrandName }}</div>
					<div class="brand-sub">Website Audit Report</div>
				</td>
				<td style="text-align: right; width: 160px;">
					<div class="score-box {{ $overallClass }}">
						<div class="score-value">{{ $scan->overall_score ?? 0 }}</div>
						<div class="score-label">out of 100 &middot; Overall</div>
					</div>
				</td>
			</tr>
		</table>

		<div class="brand-bar"></div>

		<div class="cover-title-pre">A comprehensive analysis of</div>
		<div class="cover-title">{{ $project->name }}</div>
		<div class="cover-url">{{ $project->url }}</div>

		<div class="cover-intro">
			This report is a comprehensive audit of {{ $project->url }}, analyzing {{ $totalModules }} key factors across SEO, security, performance, content quality, and technical health. From on-page optimization and search visibility to platform vulnerabilities, malware detection, and Core Web Vitals &mdash; each check is scored and prioritized so you know exactly what to fix first and why it matters.
			@if($hasAiSuggestions)
				<span style="color: {{ $accentColor }};">This report also includes {{ $aiProviderLabel ?? "AI" }}-powered recommendations with specific, actionable optimizations tailored to your site.</span>
			@endif
		</div>

		<table class="score-row">
			<tr>
				<td class="score-box score-box-sm {{ $seoClass }}">
					<div class="score-value score-value-sm">{!! $scan->seo_score ?? "&mdash;" !!}</div>
					<div class="score-label score-label-sub">out of 100 &middot; SEO Score</div>
				</td>
				<td class="score-box score-box-sm {{ $healthClass }}">
					<div class="score-value score-value-sm">{!! $scan->health_score ?? "&mdash;" !!}</div>
					<div class="score-label score-label-sub">out of 100 &middot; Site Health</div>
				</td>
			</tr>
		</table>
		<div style="font-size: 10px; color: #4B5563; line-height: 1.8; margin-bottom: 20px;">
			<strong>Overall Score</strong> &mdash; A weighted aggregate of all {{ $totalModules }} checks across every category.<br/>
			<strong>SEO Score</strong> &mdash; Calculated independently from SEO-related modules: on-page optimization, technical SEO, content quality, and search visibility.<br/>
			<strong>Site Health Score</strong> &mdash; Calculated independently from infrastructure modules: performance, security, analytics, and technology stack.
		</div>

		<div class="divider"></div>

		<table class="meta-table">
			<tr>
				<td class="meta-label">Website</td>
				<td class="meta-value">{{ $project->url }}</td>
			</tr>
			<tr>
				<td class="meta-label">Scan Date</td>
				<td class="meta-value">{{ $scan->created_at->format("F j, Y") }}</td>
			</tr>
			@if($scan->isCrawlScan())
			<tr>
				<td class="meta-label">Pages Crawled</td>
				<td class="meta-value">{{ $scan->pages_crawled }} {{ Str::plural("page", $scan->pages_crawled) }}</td>
			</tr>
			@endif
			<tr>
				<td class="meta-label">Scan Type</td>
				<td class="meta-value">{{ $scan->isCrawlScan() ? "Multi-Page Crawl" : "Homepage Analysis" }}</td>
			</tr>
			@if($scan->scan_duration_ms)
			<tr>
				<td class="meta-label">Duration</td>
				<td class="meta-value">{{ $scan->formattedDuration() }}</td>
			</tr>
			@endif
			@if($scan->fetcher_used === "zyte")
			<tr>
				<td class="meta-label">Fetcher</td>
				<td class="meta-value">Zyte Browser Rendering (bot protection bypass)</td>
			</tr>
			@endif
			@if(!empty($project->target_keywords))
			<tr>
				<td class="meta-label">Keywords</td>
				<td class="meta-value">
					@foreach($project->target_keywords as $keyword)
						<span class="keyword-tag">{{ $keyword }}</span>
					@endforeach
				</td>
			</tr>
			@endif
		</table>

		@include("reports.partials.pdf-contact-info", array("author" => $reportAuthor, "variant" => "cover"))
	</div>

	{{-- ── AI Executive Summary ── --}}
	@if($scan->ai_executive_summary)
	<div class="page-break"></div>
	<div class="section section-indigo">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#9733;</span>Executive Summary</div>
			<div class="section-subtitle">AI-generated overview of your site's SEO health.</div>
		</div>

		<div class="card card-info avoid-break">
			<div class="card-header">
				<span class="card-title">Overview</span>
				<span class="card-badge badge-info">AI</span>
			</div>
			@if(!empty($scan->ai_executive_summary["summary"]))
				<div class="card-findings">
					<div style="font-size: 11px; color: #374151; line-height: 1.7;">{{ $scan->ai_executive_summary["summary"] }}</div>
				</div>
			@endif
		</div>

		@if(!empty($scan->ai_executive_summary["topIssues"]))
		<div class="card card-fail avoid-break">
			<div class="card-header">
				<span class="card-title">&#9888; Top Issues</span>
				<span class="card-badge badge-fail">Priority</span>
			</div>
			<div class="card-findings">
				@foreach($scan->ai_executive_summary["topIssues"] as $issue)
					@php
						$issueText = is_array($issue) ? ($issue["issue"] ?? ($issue["description"] ?? "")) : $issue;
					@endphp
					<div class="card-finding">{{ $issueText }}</div>
				@endforeach
			</div>
		</div>
		@endif

		@if(!empty($scan->ai_executive_summary["quickWins"]))
		<div class="card avoid-break">
			<div class="card-header">
				<span class="card-title">&#9889; Quick Wins</span>
				<span class="card-badge">Easy Fixes</span>
			</div>
			<div class="card-findings">
				@foreach($scan->ai_executive_summary["quickWins"] as $quickWin)
					<div class="card-finding">
						{{ is_array($quickWin) ? ($quickWin["action"] ?? ($quickWin["description"] ?? "")) : $quickWin }}
						@if(is_array($quickWin) && !empty($quickWin["estimatedPoints"]))
							<span style="color: #9CA3AF; font-size: 9px;">(+{{ $quickWin["estimatedPoints"] }} pts)</span>
						@endif
					</div>
				@endforeach
			</div>
		</div>
		@endif
	</div>
	@endif

	{{-- Deduplicate: one result per module_key, prefer homepage/site-wide --}}
	@php
		$allResults = collect();
		foreach ($groupedResults as $category => $results) {
			foreach ($results as $result) {
				$allResults->push($result);
			}
		}
		$uniqueResults = $allResults->groupBy("module_key")->map(function ($group) {
			return $group->sortBy("scan_page_id")->first();
		});

		$wpModuleKeys = array("wpDetection", "wpPlugins", "wpTheme");
		$securityModuleKeys = array("sslCertificate", "securityHeaders", "mixedContent", "exposedSensitiveFiles", "blacklistCheck");
		$hasWordPress = $uniqueResults->contains(fn($r) => in_array($r->module_key, array("wpPlugins", "wpTheme")));

		$allWordPressModules = $hasWordPress
			? $uniqueResults->filter(fn($r) => in_array($r->module_key, $wpModuleKeys))->values()
			: collect();
		$wordPressModules = $allWordPressModules->filter(fn($r) => $r->status->value !== "ok")->values();
		$passedWordPressModules = $allWordPressModules->filter(fn($r) => $r->status->value === "ok")->values();

		$allSecurityModules = $uniqueResults->filter(fn($r) => in_array($r->module_key, $securityModuleKeys))->values();
		$securityModules = $allSecurityModules->filter(fn($r) => $r->status->value !== "ok")->values();
		$passedSecurityModules = $allSecurityModules->filter(fn($r) => $r->status->value === "ok")->values();

		$excludedKeys = array_merge($wpModuleKeys, $securityModuleKeys);
		$nonSpecialResults = $uniqueResults->reject(fn($r) => in_array($r->module_key, $excludedKeys));

		$passedModules = $nonSpecialResults->filter(fn($r) => $r->status->value === "ok")
			->merge($passedWordPressModules)
			->merge($passedSecurityModules)
			->values();
		$warningModules = $nonSpecialResults->filter(fn($r) => $r->status->value === "warning")->values();
		$failedModules = $nonSpecialResults->filter(fn($r) => $r->status->value === "bad")->values();
		$infoModules = $nonSpecialResults->filter(fn($r) => $r->status->value === "info")->values();
		$moduleDescriptions = config("module-descriptions");
	@endphp

	{{-- ── Needs Attention (Warnings) ── --}}
	@if($warningModules->isNotEmpty())
	<div class="page-break"></div>
	<div class="section section-amber">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#9888;</span>Needs Attention</div>
			<div class="section-subtitle">{{ $warningModules->count() }} {{ Str::plural("check", $warningModules->count()) }} flagged — these issues may be impacting your search performance.</div>
		</div>

		@foreach($warningModules as $result)
			@include("reports.partials.pdf-module-result", array("result" => $result, "findingsLimit" => 4, "showAi" => false))
		@endforeach
	</div>
	@endif

	{{-- ── Critical Issues (Failed) ── --}}
	@if($failedModules->isNotEmpty())
	<div class="page-break"></div>
	<div class="section section-red">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#10008;</span>Critical Issues</div>
			<div class="section-subtitle">{{ $failedModules->count() }} {{ Str::plural("check", $failedModules->count()) }} failed — these should be your top priority to fix.</div>
		</div>

		@foreach($failedModules as $result)
			@include("reports.partials.pdf-module-result", array("result" => $result, "findingsLimit" => 5, "showAi" => false))
		@endforeach
	</div>
	@endif

	{{-- ── WordPress (only when WordPress detected) ── --}}
	@if($wordPressModules->isNotEmpty())
	<div class="page-break"></div>
	<div class="section section-indigo">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#127760;</span>WordPress Analysis</div>
			<div class="section-subtitle">WordPress core, theme, and plugin analysis for your site.</div>
		</div>

		@foreach($wordPressModules as $result)
			@include("reports.partials.pdf-module-result", array("result" => $result, "findingsLimit" => 6, "showAi" => false))
			@include("reports.partials.pdf-wordpress-details", array("result" => $result))
		@endforeach
	</div>
	@endif

	{{-- ── Additional Information (Info) ── --}}
	@if($infoModules->isNotEmpty())
	<div class="page-break"></div>
	<div class="section section-blue">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#8505;</span>Additional Information</div>
			<div class="section-subtitle">{{ $infoModules->count() }} informational {{ Str::plural("check", $infoModules->count()) }} — no action required, provided for reference.</div>
		</div>

		@foreach($infoModules as $result)
			@include("reports.partials.pdf-module-result", array("result" => $result, "findingsLimit" => 4, "showRecs" => false, "showAi" => false))
		@endforeach
	</div>
	@endif

	{{-- ── Security (always shows when security modules have results) ── --}}
	@if($securityModules->isNotEmpty())
	<div class="page-break"></div>
	<div class="section section-indigo">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#128274;</span>Security &amp; Protection</div>
			<div class="section-subtitle">{{ $securityModules->count() }} security {{ Str::plural("check", $securityModules->count()) }} — SSL, headers, mixed content, exposed files, and threat detection.</div>
		</div>

		@foreach($securityModules as $result)
			@include("reports.partials.pdf-module-result", array("result" => $result, "findingsLimit" => 6, "showAi" => false))
		@endforeach
	</div>
	@endif

	{{-- ── Passed Checks (compact summary — no full cards) ── --}}
	@if($passedModules->isNotEmpty())
	<div class="page-break"></div>
	<div class="section section-green">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#10004;</span>Passing Checks</div>
			<div class="section-subtitle">{{ $passedModules->count() }} {{ Str::plural("check", $passedModules->count()) }} passed — no action required.</div>
		</div>

		<table class="pages-table">
			<thead>
				<tr>
					<th style="width: 50%;">Module</th>
					<th style="width: 50%;">Status</th>
				</tr>
			</thead>
			<tbody>
				@foreach($passedModules as $result)
				<tr>
					<td>{{ $moduleLabels[$result->module_key] ?? $result->module_key }}</td>
					<td><span class="page-status-count page-status-ok">Passed</span></td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	@endif

	{{-- ── AI Optimizations ── --}}
	@if($aiResults->isNotEmpty())
	@php
		$pageMap = $scanPages->keyBy("id");
		$homepageAiResults = $aiResults->filter(fn ($r) => $r->scan_page_id === null)->values();
		$perPageAiResults = $aiResults->filter(fn ($r) => $r->scan_page_id !== null)->values();
		$aiByPage = $perPageAiResults->groupBy("scan_page_id");
	@endphp
	<div class="page-break"></div>
	<div class="section section-indigo">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#9733;</span>AI-Powered Optimizations</div>
			<div class="section-subtitle">See how AI can help you craft the perfect titles, descriptions, and content &mdash; tailored to your site for maximum search visibility and click-through rates.</div>
		</div>

		@if($homepageAiResults->isNotEmpty())
		<div style="margin-bottom: 16px;">
			<div class="page-ai-label">&#128196; {{ $project->url }}</div>
			@foreach($homepageAiResults as $result)
				@include("reports.partials.pdf-module-result", array(
					"result" => $result,
					"findingsLimit" => 3,
					"showRecs" => false,
					"showAi" => true,
				))
			@endforeach
		</div>
		@endif

		@foreach($aiByPage as $pageId => $pageResults)
		@php
			$page = $pageMap->get($pageId);
			$pageUrl = $page ? $page->truncatedUrl(80) : "Page #" . $pageId;
		@endphp
		<div style="margin-bottom: 16px;">
			<div class="page-ai-label">&#128196; {{ $pageUrl }}</div>
			@foreach($pageResults as $result)
				@include("reports.partials.pdf-module-result", array(
					"result" => $result,
					"findingsLimit" => 3,
					"showRecs" => false,
					"showAi" => true,
				))
			@endforeach
		</div>
		@endforeach
	</div>
	@endif

	{{-- ── Crawled Pages Summary (only for multi-page crawl scans) ── --}}
	@if($scanPages->count() > 1)
	<div class="page-break"></div>
	<div class="section section-blue">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#128196;</span>Pages Analyzed</div>
			<div class="section-subtitle">{{ $scanPages->count() }} {{ Str::plural("page", $scanPages->count()) }} crawled and scored individually.</div>
		</div>

		<table class="pages-table">
			<thead>
				<tr>
					<th style="width: 55%;">Page URL</th>
					<th style="width: 12%; text-align: center;">Score</th>
					<th style="width: 33%;">Status Breakdown</th>
				</tr>
			</thead>
			<tbody>
				@foreach($scanPages->sortByDesc("page_score") as $scanPage)
				@php
					$pageScoreClass = match(true) {
						$scanPage->page_score === null => "page-score-gray",
						$scanPage->page_score >= 80 => "page-score-green",
						$scanPage->page_score >= 50 => "page-score-amber",
						default => "page-score-red",
					};
					$pageOk = $scanPage->moduleResults->where("status.value", "ok")->count();
					$pageWarn = $scanPage->moduleResults->where("status.value", "warning")->count();
					$pageBad = $scanPage->moduleResults->where("status.value", "bad")->count();
				@endphp
				<tr>
					<td class="page-url">{{ $scanPage->truncatedUrl(70) }}</td>
					<td style="text-align: center;"><span class="page-score {{ $pageScoreClass }}">{{ $scanPage->page_score ?? "N/A" }}</span></td>
					<td>
						@if($pageOk > 0)<span class="page-status-count page-status-ok">{{ $pageOk }} passed</span>@endif
						@if($pageWarn > 0)<span class="page-status-count page-status-warn">{{ $pageWarn }} warning</span>@endif
						@if($pageBad > 0)<span class="page-status-count page-status-bad">{{ $pageBad }} failed</span>@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	@endif

	{{-- ── Additional Pages (project-level, manually added or discovered) ── --}}
	@if($additionalPages->isNotEmpty())
	<div class="page-break"></div>
	<div class="section section-blue">
		<div class="section-header">
			<div class="section-title"><span class="section-icon">&#128196;</span>Additional Pages Analyzed</div>
			<div class="section-subtitle">{{ $additionalPages->count() }} additional {{ Str::plural("page", $additionalPages->count()) }} analyzed individually beyond the homepage.</div>
		</div>

		<table class="pages-table">
			<thead>
				<tr>
					<th style="width: 55%;">Page URL</th>
					<th style="width: 12%; text-align: center;">Score</th>
					<th style="width: 33%;">Status Breakdown</th>
				</tr>
			</thead>
			<tbody>
				@foreach($additionalPages as $additionalPage)
				@php
					$pageScoreClass = match(true) {
						$additionalPage->page_score === null => "page-score-gray",
						$additionalPage->page_score >= 80 => "page-score-green",
						$additionalPage->page_score >= 50 => "page-score-amber",
						default => "page-score-red",
					};
					$pageOk = $additionalPage->moduleResults->where("status.value", "ok")->count();
					$pageWarn = $additionalPage->moduleResults->where("status.value", "warning")->count();
					$pageBad = $additionalPage->moduleResults->where("status.value", "bad")->count();
				@endphp
				<tr>
					<td class="page-url">{{ $additionalPage->truncatedUrl(70) }}</td>
					<td style="text-align: center;"><span class="page-score {{ $pageScoreClass }}">{{ $additionalPage->page_score ?? "N/A" }}</span></td>
					<td>
						@if($pageOk > 0)<span class="page-status-count page-status-ok">{{ $pageOk }} passed</span>@endif
						@if($pageWarn > 0)<span class="page-status-count page-status-warn">{{ $pageWarn }} warning</span>@endif
						@if($pageBad > 0)<span class="page-status-count page-status-bad">{{ $pageBad }} failed</span>@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	@endif

	{{-- ── Closing Page ── --}}
	<div class="page-break"></div>
	<div class="outro">
		<div class="outro-bar"></div>
		<div class="outro-title">What's Next?</div>
		<div class="outro-text">
			This report identified the highest-impact opportunities to improve your website's visibility, performance, and security. Here's how to turn these insights into results:
		</div>

		<table class="outro-steps">
			<tr>
				<td class="outro-step-num-cell"><span class="outro-step-num">1</span></td>
				<td class="outro-step-text-cell"><strong>Start with Critical Issues</strong> &mdash; fix the red items first for the biggest gains.</td>
			</tr>
			<tr>
				<td class="outro-step-num-cell"><span class="outro-step-num">2</span></td>
				<td class="outro-step-text-cell"><strong>Address Warnings</strong> &mdash; tackle the amber items to fine-tune your performance.</td>
			</tr>
			<tr>
				<td class="outro-step-num-cell"><span class="outro-step-num">3</span></td>
				<td class="outro-step-text-cell"><strong>Rescan &amp; Track Progress</strong> &mdash; run a new scan after making changes to measure improvement.</td>
			</tr>
		</table>

		@include("reports.partials.pdf-contact-info", array("author" => $reportAuthor, "variant" => "outro"))

		<div class="outro-divider"></div>

		<div class="outro-brand">{{ $pdfBrandName }}</div>
		<div class="outro-tagline">Your partner in website performance</div>
	</div>
</body>
</html>
