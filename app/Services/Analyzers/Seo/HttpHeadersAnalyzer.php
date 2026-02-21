<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class HttpHeadersAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "httpHeaders";
	}

	public function label(): string
	{
		return "HTTP Headers";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.httpHeaders", 5);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$headers = $scanContext->responseHeaders;
		$findings = array();
		$recommendations = array();
		$status = ModuleStatus::Ok;

		if (empty($headers)) {
			return new AnalysisResult(
				status: ModuleStatus::Warning,
				findings: array(array("type" => "warning", "message" => "Unable to analyze HTTP response headers.")),
				recommendations: array(),
			);
		}

		$contentEncoding = $headers["content-encoding"] ?? null;
		if ($contentEncoding !== null) {
			$encodingStr = is_array($contentEncoding) ? implode(", ", $contentEncoding) : $contentEncoding;
			if (stripos($encodingStr, "gzip") !== false || stripos($encodingStr, "br") !== false) {
				$findings[] = array("type" => "ok", "message" => "Content compression enabled: {$encodingStr}");
			} else {
				$findings[] = array("type" => "info", "message" => "Content-Encoding: {$encodingStr}");
			}
		} else {
			$findings[] = array("type" => "warning", "message" => "No content compression detected (gzip/brotli). Compressing responses reduces page load time.");
			$recommendations[] = "Enable gzip or Brotli compression on your server to reduce transfer sizes.";
			$status = ModuleStatus::Warning;
		}

		$cacheControl = $headers["cache-control"] ?? null;
		$expires = $headers["expires"] ?? null;

		if ($cacheControl !== null) {
			$cacheStr = is_array($cacheControl) ? implode(", ", $cacheControl) : $cacheControl;
			$findings[] = array("type" => "info", "message" => "Cache-Control: {$cacheStr}");

			if (preg_match("/max-age=(\d+)/", $cacheStr, $matches)) {
				$maxAge = (int) $matches[1];
				if ($maxAge >= 86400) {
					$findings[] = array("type" => "ok", "message" => "Good cache duration: " . round($maxAge / 86400, 1) . " day(s).");
				} else {
					$findings[] = array("type" => "info", "message" => "Short cache duration: " . round($maxAge / 3600, 1) . " hour(s).");
				}
			}
		} elseif ($expires !== null) {
			$expiresStr = is_array($expires) ? $expires[0] : $expires;
			$findings[] = array("type" => "info", "message" => "Expires: {$expiresStr}");
		} else {
			$findings[] = array("type" => "warning", "message" => "No caching headers found (Cache-Control or Expires). Caching improves load performance for returning visitors.");
			$recommendations[] = "Set Cache-Control headers with an appropriate max-age for static resources.";
			$status = ModuleStatus::Warning;
		}

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}
}
