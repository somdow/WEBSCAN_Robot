{{--
	WordPress detail tables for the PDF report.
	Renders plugin table, theme card, and core vulnerability list from data findings.

	Required: $result (ScanModuleResult)
--}}
@php
	$dataFindings = array();
	foreach ($result->findings ?? array() as $finding) {
		if (($finding["type"] ?? "") === "data" && isset($finding["key"])) {
			$dataFindings[$finding["key"]] = $finding["value"] ?? null;
		}
	}
@endphp

{{-- ── Plugin Detail Table ── --}}
@if($result->module_key === "wpPlugins" && !empty($dataFindings["detectedPlugins"]))
@php
	$plugins = $dataFindings["detectedPlugins"];
	usort($plugins, function ($pluginA, $pluginB) {
		$vulnA = $pluginA["vulnerabilities_count"] ?? 0;
		$vulnB = $pluginB["vulnerabilities_count"] ?? 0;
		if ($vulnA !== $vulnB) {
			return $vulnB <=> $vulnA;
		}
		$statusOrder = array("outdated" => 0, "current" => 1, "unknown" => 2);
		$orderA = $statusOrder[$pluginA["version_status"] ?? "unknown"] ?? 2;
		$orderB = $statusOrder[$pluginB["version_status"] ?? "unknown"] ?? 2;
		if ($orderA !== $orderB) {
			return $orderA <=> $orderB;
		}
		return strcasecmp($pluginA["name"] ?? $pluginA["slug"], $pluginB["name"] ?? $pluginB["slug"]);
	});

	$vulnerableCount = count(array_filter($plugins, fn($p) => ($p["vulnerabilities_count"] ?? 0) > 0));
	$outdatedCount = count(array_filter($plugins, fn($p) => ($p["version_status"] ?? "unknown") === "outdated" && ($p["vulnerabilities_count"] ?? 0) === 0));
@endphp

<div class="wp-detail-section">
	<div class="wp-detail-header">
		Detected Plugins ({{ count($plugins) }})
		@if($vulnerableCount > 0)
			<span class="wp-tag-red">{{ $vulnerableCount }} vulnerable</span>
		@endif
		@if($outdatedCount > 0)
			<span class="wp-tag-amber">{{ $outdatedCount }} outdated</span>
		@endif
	</div>
	<table class="wp-table">
		<thead>
			<tr>
				<th style="width: 40%;">Plugin</th>
				<th style="width: 15%;">Detected</th>
				<th style="width: 15%;">Latest</th>
				<th style="width: 15%;">Status</th>
				<th style="width: 15%;">Vulns</th>
			</tr>
		</thead>
		<tbody>
			@foreach($plugins as $plugin)
			@php
				$pluginName = $plugin["name"] ?? ucwords(str_replace("-", " ", $plugin["slug"] ?? ""));
				$detectedVer = $plugin["detected_version"] ?? null;
				$latestVer = $plugin["latest_version"] ?? null;
				$versionStatus = $plugin["version_status"] ?? "unknown";
				$isPremium = $plugin["is_premium"] ?? false;
				$vulnCount = $plugin["vulnerabilities_count"] ?? 0;
				$vulnerabilities = $plugin["vulnerabilities"] ?? array();

				$statusLabel = match($versionStatus) {
					"current" => "Current",
					"outdated" => "Outdated",
					default => "Unknown",
				};
				$statusClass = match(true) {
					$vulnCount > 0 => "wp-status-red",
					$versionStatus === "outdated" => "wp-status-amber",
					$versionStatus === "current" => "wp-status-green",
					default => "wp-status-gray",
				};
			@endphp
			<tr class="{{ $vulnCount > 0 ? 'wp-row-red' : ($versionStatus === 'outdated' ? 'wp-row-amber' : '') }}">
				<td>
					<strong>{{ $pluginName }}</strong>
					@if($isPremium)
						<span class="wp-tag-purple">Premium</span>
					@endif
				</td>
				<td>{{ $detectedVer ?? "N/A" }}</td>
				<td>{{ $isPremium ? "N/A" : ($latestVer ?? "N/A") }}</td>
				<td><span class="{{ $statusClass }}">{{ $vulnCount > 0 ? "Vulnerable" : $statusLabel }}</span></td>
				<td>{{ $vulnCount > 0 ? $vulnCount : "None" }}</td>
			</tr>
			@if($vulnCount > 0 && !empty($vulnerabilities))
				@foreach($vulnerabilities as $vuln)
				<tr class="wp-vuln-row">
					<td colspan="5">
						<span class="wp-vuln-icon">&#9888;</span>
						<strong>{{ $vuln["cve_id"] ?? "No CVE" }}</strong>
						&mdash; {{ $vuln["name"] ?? "Unknown vulnerability" }}
						@if(!empty($vuln["cvss_score"]))
							<span class="wp-cvss">CVSS {{ number_format($vuln["cvss_score"], 1) }}</span>
						@endif
						@if(!empty($vuln["fixed_in"]))
							<span class="wp-fixed">Fixed in {{ $vuln["fixed_in"] }}</span>
						@elseif($vuln["unfixed"] ?? false)
							<span class="wp-unfixed">No fix available</span>
						@endif
					</td>
				</tr>
				@endforeach
			@endif
			@endforeach
		</tbody>
	</table>
</div>
@endif

{{-- ── Theme Detail Card ── --}}
@if($result->module_key === "wpTheme" && !empty($dataFindings["themeDetails"]))
@php
	$theme = $dataFindings["themeDetails"];
	$themeName = $theme["name"] ?? ucwords(str_replace("-", " ", $theme["slug"] ?? ""));
	$themeDetected = $theme["detected_version"] ?? null;
	$themeLatest = $theme["latest_version"] ?? null;
	$themeIsPremium = $theme["is_premium"] ?? false;
	$themeStatus = match(true) {
		$themeLatest === null || $themeDetected === null => "Unknown",
		version_compare($themeDetected, $themeLatest, ">=") => "Current",
		default => "Outdated",
	};
	$themeStatusClass = match($themeStatus) {
		"Current" => "wp-status-green",
		"Outdated" => "wp-status-amber",
		default => "wp-status-gray",
	};
	$themeVulns = $dataFindings["themeVulnerabilities"] ?? array();
@endphp

<div class="wp-detail-section">
	<div class="wp-detail-header">Active Theme</div>
	<table class="wp-table">
		<thead>
			<tr>
				<th style="width: 35%;">Theme</th>
				<th style="width: 20%;">Detected</th>
				<th style="width: 20%;">Latest</th>
				<th style="width: 25%;">Status</th>
			</tr>
		</thead>
		<tbody>
			<tr class="{{ $themeStatus === 'Outdated' ? 'wp-row-amber' : '' }}">
				<td>
					<strong>{{ $themeName }}</strong>
					@if($themeIsPremium)
						<span class="wp-tag-purple">Premium</span>
					@endif
				</td>
				<td>{{ $themeDetected ?? "Unknown" }}</td>
				<td>{{ $themeIsPremium ? "N/A" : ($themeLatest ?? "Unknown") }}</td>
				<td><span class="{{ $themeStatusClass }}">{{ $themeStatus }}</span></td>
			</tr>
		</tbody>
	</table>

	@if(!empty($themeVulns))
	<div class="wp-vuln-section">
		<div class="wp-vuln-header">&#9888; Theme Vulnerabilities ({{ count($themeVulns) }})</div>
		@foreach($themeVulns as $vuln)
		<div class="wp-vuln-item">
			<strong>{{ $vuln["cve_id"] ?? "No CVE" }}</strong>
			&mdash; {{ $vuln["name"] ?? "Unknown vulnerability" }}
			@if(!empty($vuln["cvss_score"]))
				<span class="wp-cvss">CVSS {{ number_format($vuln["cvss_score"], 1) }}</span>
			@endif
			@if(!empty($vuln["fixed_in"]))
				<span class="wp-fixed">Fixed in {{ $vuln["fixed_in"] }}</span>
			@elseif($vuln["unfixed"] ?? false)
				<span class="wp-unfixed">No fix available</span>
			@endif
		</div>
		@endforeach
	</div>
	@endif
</div>
@endif

{{-- ── Core Vulnerabilities ── --}}
@if($result->module_key === "wpDetection" && !empty($dataFindings["coreVulnerabilities"]))
@php
	$coreVulns = $dataFindings["coreVulnerabilities"];
@endphp

<div class="wp-detail-section">
	<div class="wp-vuln-section" style="margin-top: 0;">
		<div class="wp-vuln-header">&#9888; WordPress Core Vulnerabilities ({{ count($coreVulns) }})</div>
		@foreach($coreVulns as $vuln)
		<div class="wp-vuln-item">
			<strong>{{ $vuln["cve_id"] ?? "No CVE" }}</strong>
			&mdash; {{ $vuln["name"] ?? "Unknown vulnerability" }}
			@if(!empty($vuln["cvss_score"]))
				<span class="wp-cvss">CVSS {{ number_format($vuln["cvss_score"], 1) }}</span>
			@endif
			@if(!empty($vuln["fixed_in"]))
				<span class="wp-fixed">Fixed in {{ $vuln["fixed_in"] }}</span>
			@elseif($vuln["unfixed"] ?? false)
				<span class="wp-unfixed">No fix available</span>
			@endif
		</div>
		@endforeach
	</div>
</div>
@endif
