<?php

namespace App\Services\Analyzers\Security;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;

/**
 * Probes for publicly accessible sensitive files that should never be exposed.
 *
 * Site-wide scope — sensitive files live at the domain root level.
 * Exposed .env files, git repositories, config backups, and debug logs
 * can leak database credentials, API keys, and other secrets.
 */
class ExposedSensitiveFilesAnalyzer implements AnalyzerInterface
{
	/**
	 * Files to probe. Each entry specifies the path, a display label,
	 * content markers to confirm real exposure (vs custom 404 pages),
	 * and whether the check is conditional on WordPress detection.
	 */
	private const SENSITIVE_FILES = array(
		array(
			"path" => ".env",
			"label" => ".env (Environment Variables)",
			"severity" => "critical",
			"markers" => array("APP_", "DB_", "MAIL_", "AWS_"),
			"wordpressOnly" => false,
		),
		array(
			"path" => ".git/HEAD",
			"label" => ".git/HEAD (Git Repository)",
			"severity" => "critical",
			"markers" => array("ref: refs/"),
			"wordpressOnly" => false,
		),
		array(
			"path" => ".htaccess",
			"label" => ".htaccess (Apache Config)",
			"severity" => "warning",
			"markers" => array("RewriteEngine", "RewriteRule", "Deny from", "Allow from", "DirectoryIndex"),
			"wordpressOnly" => false,
		),
		array(
			"path" => "phpinfo.php",
			"label" => "phpinfo.php (PHP Information)",
			"severity" => "high",
			"markers" => array("phpinfo()", "PHP Version", "PHP License", "Configuration File"),
			"wordpressOnly" => false,
		),
		array(
			"path" => "debug.log",
			"label" => "debug.log (Debug Log)",
			"severity" => "high",
			"markers" => array("[error]", "Stack trace", "Exception", "PHP Fatal"),
			"wordpressOnly" => false,
		),
		array(
			"path" => "wp-content/debug.log",
			"label" => "wp-content/debug.log (WordPress Debug Log)",
			"severity" => "high",
			"markers" => array("[error]", "Stack trace", "PHP Fatal", "WordPress"),
			"wordpressOnly" => true,
		),
		array(
			"path" => "wp-config.php.bak",
			"label" => "wp-config.php.bak (WordPress Config Backup)",
			"severity" => "critical",
			"markers" => array("DB_NAME", "DB_USER", "DB_PASSWORD", "table_prefix"),
			"wordpressOnly" => true,
		),
		array(
			"path" => "wp-config.php~",
			"label" => "wp-config.php~ (WordPress Config Editor Backup)",
			"severity" => "critical",
			"markers" => array("DB_NAME", "DB_USER", "DB_PASSWORD", "table_prefix"),
			"wordpressOnly" => true,
		),
	);

	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	public function moduleKey(): string
	{
		return "exposedSensitiveFiles";
	}

	public function label(): string
	{
		return "Exposed Sensitive Files";
	}

	public function category(): string
	{
		return "Security";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.exposedSensitiveFiles", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$domainRoot = rtrim($scanContext->domainRoot(), "/");
		$probeResults = $this->probeAllFiles($domainRoot, $scanContext->isWordPress);
		$findings = $this->buildFindings($probeResults);
		$recommendations = $this->buildRecommendations($probeResults["exposedFiles"]);

		$status = empty($probeResults["exposedFiles"]) ? ModuleStatus::Ok : ModuleStatus::Bad;

		return new AnalysisResult(
			status: $status,
			findings: $findings,
			recommendations: $recommendations,
		);
	}

	/**
	 * Probe all applicable sensitive file paths concurrently and collect results.
	 * All probes fire at once (concurrency 8) since there are only ~8 paths.
	 *
	 * @return array{exposedFiles: array, probeCount: int}
	 */
	private function probeAllFiles(string $domainRoot, bool $isWordPress): array
	{
		$applicableSpecs = array();
		$keyedUrls = array();

		foreach (self::SENSITIVE_FILES as $index => $fileSpec) {
			if ($fileSpec["wordpressOnly"] && !$isWordPress) {
				continue;
			}

			$key = "file_{$index}";
			$keyedUrls[$key] = $domainRoot . "/" . $fileSpec["path"];
			$applicableSpecs[$key] = $fileSpec;
		}

		$probeCount = count($keyedUrls);

		if ($probeCount === 0) {
			return array("exposedFiles" => array(), "probeCount" => 0);
		}

		$fetchResults = $this->httpFetcher->fetchResourcesConcurrent($keyedUrls, 5, 8);

		$exposedFiles = array();

		foreach ($applicableSpecs as $key => $fileSpec) {
			$fetchResult = $fetchResults[$key] ?? null;

			if ($fetchResult === null || !$fetchResult->successful || $fetchResult->httpStatusCode !== 200) {
				continue;
			}

			$body = $fetchResult->content ?? "";

			if (trim($body) === "") {
				continue;
			}

			foreach ($fileSpec["markers"] as $marker) {
				if (stripos($body, $marker) !== false) {
					$exposedFiles[] = $fileSpec;
					break;
				}
			}
		}

		return array("exposedFiles" => $exposedFiles, "probeCount" => $probeCount);
	}

	/**
	 * Build findings from probe results.
	 *
	 * @param array{exposedFiles: array, probeCount: int} $probeResults
	 */
	private function buildFindings(array $probeResults): array
	{
		$exposedFiles = $probeResults["exposedFiles"];
		$probeCount = $probeResults["probeCount"];
		$exposedCount = count($exposedFiles);
		$findings = array();

		foreach ($exposedFiles as $fileSpec) {
			$findings[] = array(
				"type" => "bad",
				"message" => "EXPOSED: {$fileSpec['label']} is publicly accessible at /{$fileSpec['path']}",
			);
		}

		$findings[] = array(
			"type" => "data",
			"key" => "probesSummary",
			"value" => "{$probeCount} sensitive file paths probed, {$exposedCount} exposed",
		);

		if (empty($exposedFiles)) {
			$findings[] = array(
				"type" => "ok",
				"message" => "No publicly accessible sensitive files were detected across {$probeCount} probed paths.",
			);
		}

		return $findings;
	}

	/**
	 * Build targeted recommendations based on exposed file severities.
	 */
	private function buildRecommendations(array $exposedFiles): array
	{
		if (empty($exposedFiles)) {
			return array();
		}

		$recommendations = array();
		$hasCritical = collect($exposedFiles)->contains(fn($fileSpec) => $fileSpec["severity"] === "critical");

		if ($hasCritical) {
			$recommendations[] = "URGENT: Critical files containing credentials or secrets are publicly accessible. Immediately restrict access to these files via your web server configuration, then rotate any exposed credentials (database passwords, API keys, etc.).";
		}

		$recommendations[] = "Configure your web server to deny access to sensitive files. For Apache, add to .htaccess: <FilesMatch \"^\\.(env|git|htaccess)\"> Require all denied </FilesMatch>. For Nginx, add: location ~ /\\. { deny all; }";
		$recommendations[] = "Remove unnecessary files from your web root: backup files (.bak, ~), debug logs, and phpinfo scripts should never exist on production servers.";

		return $recommendations;
	}

}
