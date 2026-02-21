<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use App\Services\Scanning\HttpFetcher;

class RobotsTxtAnalyzer implements AnalyzerInterface
{
	public function __construct(
		private readonly HttpFetcher $httpFetcher,
	) {}

	public function moduleKey(): string
	{
		return "robotsTxt";
	}

	public function label(): string
	{
		return "robots.txt";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.robotsTxt", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$domainRoot = $scanContext->domainRoot();
		$robotsUrl = rtrim($domainRoot, "/") . "/robots.txt";

		$fetchResult = $this->httpFetcher->fetchResource($robotsUrl);
		$findings = array();
		$recommendations = array();

		$findings[] = array("type" => "data", "key" => "robotsTxtUrl", "value" => $robotsUrl);

		if (!$fetchResult->successful || $fetchResult->content === null) {
			$findings[] = array("type" => "warning", "message" => "robots.txt not accessible at {$robotsUrl}");
			$findings[] = array("type" => "data", "key" => "robotsTxtContent", "value" => null);
			$recommendations[] = "Create a robots.txt file at the root of your domain to guide search engine crawlers.";

			return new AnalysisResult(status: ModuleStatus::Warning, findings: $findings, recommendations: $recommendations);
		}

		$content = $fetchResult->content;
		$findings[] = array("type" => "ok", "message" => "robots.txt found and accessible.");
		$findings[] = array("type" => "data", "key" => "robotsTxtContent", "value" => $content);

		if (empty(trim($content))) {
			$findings[] = array("type" => "info", "message" => "robots.txt exists but is empty. All crawlers will use default behavior.");

			return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
		}

		$lines = explode("\n", $content);
		$hasSitemapDirective = false;
		$hasDisallowAll = false;

		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			if (stripos($trimmedLine, "sitemap:") === 0) {
				$hasSitemapDirective = true;
			}
			if (preg_match("/^disallow:\s*\/\s*$/i", $trimmedLine)) {
				$hasDisallowAll = true;
			}
		}

		if ($hasDisallowAll) {
			$findings[] = array("type" => "bad", "message" => "robots.txt contains \"Disallow: /\" which blocks all crawlers from the entire site.");
			$recommendations[] = "Remove the blanket \"Disallow: /\" unless you intentionally want to block all search engine crawling.";

			return new AnalysisResult(status: ModuleStatus::Bad, findings: $findings, recommendations: $recommendations);
		}

		if (!$hasSitemapDirective) {
			$findings[] = array("type" => "info", "message" => "No Sitemap directive found in robots.txt.");
			$recommendations[] = "Add a Sitemap directive to robots.txt (e.g., Sitemap: https://example.com/sitemap.xml).";
		}

		return new AnalysisResult(status: ModuleStatus::Ok, findings: $findings, recommendations: $recommendations);
	}
}
