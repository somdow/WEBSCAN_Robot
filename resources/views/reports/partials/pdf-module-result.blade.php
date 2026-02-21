{{--
	Reusable PDF module card partial.

	Required: $result (ScanModuleResult), $moduleLabels (array), $moduleDescriptions (array)
	Optional: $findingsLimit (int, default 5), $showRecs (bool, default true), $showAi (bool, default true)
--}}
@php
	$findingsLimit = $findingsLimit ?? 5;
	$showRecs = $showRecs ?? true;
	$showAi = $showAi ?? true;

	$desc = $moduleDescriptions[$result->module_key] ?? null;
	$findings = collect($result->findings ?? array())->filter(fn($f) => is_array($f) ? (($f["type"] ?? "") !== "data") : true);
	$statusValue = $result->status->value;

	$cardClass = match($statusValue) {
		"ok" => "",
		"warning" => "card-warning",
		"bad" => "card-fail",
		default => "card-info",
	};
	$badgeClass = match($statusValue) {
		"ok" => "",
		"warning" => "badge-warning",
		"bad" => "badge-fail",
		default => "badge-info",
	};
	$badgeLabel = match($statusValue) {
		"ok" => "Passed",
		"warning" => "Warning",
		"bad" => "Failed",
		default => "Info",
	};
	$insightClass = match($statusValue) {
		"bad" => "card-recs-red",
		default => "card-recs",
	};
	$insightTitle = "&#128161; Why This Matters";
	$recsClass = $statusValue === "bad" ? "card-recs-red" : "";
	$recItemClass = $statusValue === "bad" ? "card-rec-red" : "";
	$findingsTitle = match($statusValue) {
		"ok" => "&#10004; What We Found",
		"bad" => "&#9888; Issues Detected",
		"warning" => "&#9888; Needs Improvement",
		default => "&#8505; Details",
	};
@endphp

<div class="card {{ $cardClass }} avoid-break">
	<div class="card-header">
		<table class="card-header-table">
			<tr>
				<td><span class="card-title">{{ $moduleLabels[$result->module_key] ?? $result->module_key }}</span></td>
				<td style="text-align: right;"><span class="card-badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
			</tr>
		</table>
	</div>

	@if($desc)
	<div class="{{ $insightClass }}">
		<div class="card-recs-title">{!! $insightTitle !!}</div>
		<div class="card-insight-text">{{ $desc["description"] }}</div>
		@if($statusValue === "ok" && !empty($desc["passing"]))
			<div class="card-passing">&#10004; {{ $desc["passing"] }}</div>
		@endif
	</div>
	@endif

	@if($findings->isNotEmpty())
	<div class="card-findings">
		<div class="card-recs-title" style="margin-bottom: 6px;">{!! $findingsTitle !!}</div>
		@foreach($findings->take($findingsLimit) as $finding)
			<div class="card-finding">{{ is_array($finding) ? ($finding["message"] ?? ($finding["text"] ?? "")) : $finding }}</div>
		@endforeach
	</div>
	@endif

	@if($showRecs && !empty($result->recommendations))
	<div class="card-recs {{ $recsClass }}">
		<div class="card-recs-title">&#9654; Recommendations</div>
		@foreach($result->recommendations as $rec)
			<div class="card-rec {{ $recItemClass }}">{{ is_array($rec) ? ($rec["text"] ?? ($rec["message"] ?? "")) : $rec }}</div>
		@endforeach
	</div>
	@endif

	@if($showAi && $result->ai_suggestion)
	@php
		$aiContent = $result->ai_suggestion;
		$aiPageType = "";
		if (preg_match("/^PAGE_TYPE:\s*(.+)$/m", $aiContent, $pageTypeMatch)) {
			$aiPageType = trim($pageTypeMatch[1]);
			$aiContent = preg_replace("/^PAGE_TYPE:[^\n]*\n?/m", "", $aiContent);
		}
		$aiContent = preg_replace("/<p>\s*(?:<[^>]+>\s*)*PAGE_TYPE:[^<]*(?:<\/[^>]+>\s*)*<\/p>/i", "", $aiContent);
		$safeAiContent = Str::limit(strip_tags(trim($aiContent), "<p><strong><em><ul><ol><li><br><h3><h4><code><pre><table><thead><tbody><tr><th><td>"), 800);
	@endphp
	<div class="card-ai">
		<div class="card-ai-title">&#9733; AI Suggestion</div>
		@if($aiPageType)
			<div style="margin-bottom: 6px;">
				<span class="ai-page-type-badge">{{ $aiPageType }}</span>
			</div>
		@endif
		<div class="card-ai-text">{!! $safeAiContent !!}</div>
	</div>
	@endif
</div>
