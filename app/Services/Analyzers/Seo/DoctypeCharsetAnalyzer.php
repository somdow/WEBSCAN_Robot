<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;

class DoctypeCharsetAnalyzer implements AnalyzerInterface
{
	public function moduleKey(): string
	{
		return "doctypeCharset";
	}

	public function label(): string
	{
		return "Doctype & Charset";
	}

	public function category(): string
	{
		return "Technical SEO";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.doctypeCharset", 4);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$htmlContent = $scanContext->htmlContent;
		$headers = $scanContext->responseHeaders;
		$xpath = $scanContext->xpath;
		$findings = array();
		$recommendations = array();
		$status = ModuleStatus::Ok;

		$hasDoctype = (bool) preg_match("/<!DOCTYPE\s+html/i", $htmlContent);

		if ($hasDoctype) {
			$findings[] = array("type" => "ok", "message" => "HTML5 DOCTYPE declaration found.");
		} else {
			$findings[] = array("type" => "warning", "message" => "No HTML5 DOCTYPE found. A DOCTYPE declaration ensures standards-mode rendering.");
			$recommendations[] = "Add <!DOCTYPE html> as the first line of your HTML document.";
			$status = ModuleStatus::Warning;
		}

		$charset = $this->detectCharset($headers, $xpath);

		if ($charset["found"]) {
			$findings[] = array("type" => "info", "message" => "Charset: {$charset["value"]} (Source: {$charset["source"]})");

			if ($charset["isUtf8"]) {
				$findings[] = array("type" => "ok", "message" => "UTF-8 charset correctly specified.");
			} else {
				$findings[] = array("type" => "warning", "message" => "Charset is not UTF-8. UTF-8 is the recommended encoding for web pages.");
				$recommendations[] = "Switch to UTF-8 encoding for maximum compatibility across languages and browsers.";
				$status = ModuleStatus::Warning;
			}
		} else {
			$findings[] = array("type" => "warning", "message" => "No charset declaration found in HTTP headers or HTML meta tags.");
			$recommendations[] = "Add <meta charset=\"UTF-8\"> to the <head> section of your HTML.";
			$status = ModuleStatus::Warning;
		}

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Detect charset from HTTP headers or HTML meta tags
	 */
	private function detectCharset(array $headers, \DOMXPath $xpath): array
	{
		$contentType = $headers["content-type"] ?? null;
		if ($contentType !== null) {
			$contentTypeStr = is_array($contentType) ? $contentType[0] : $contentType;
			if (preg_match("/charset=([^\s;]+)/i", $contentTypeStr, $matches)) {
				$charsetValue = strtoupper(trim($matches[1]));

				return array("found" => true, "value" => $charsetValue, "source" => "HTTP header", "isUtf8" => $charsetValue === "UTF-8");
			}
		}

		$metaCharset = $xpath->query("//head/meta[@charset]");
		if ($metaCharset && $metaCharset->length > 0) {
			$charsetValue = strtoupper(trim($metaCharset->item(0)->getAttribute("charset")));

			return array("found" => true, "value" => $charsetValue, "source" => "meta charset tag", "isUtf8" => $charsetValue === "UTF-8");
		}

		$metaHttpEquiv = $xpath->query("//head/meta[@http-equiv='Content-Type']/@content");
		if ($metaHttpEquiv && $metaHttpEquiv->length > 0) {
			$contentValue = $metaHttpEquiv->item(0)->nodeValue;
			if (preg_match("/charset=([^\s;]+)/i", $contentValue, $matches)) {
				$charsetValue = strtoupper(trim($matches[1]));

				return array("found" => true, "value" => $charsetValue, "source" => "meta http-equiv", "isUtf8" => $charsetValue === "UTF-8");
			}
		}

		return array("found" => false, "value" => null, "source" => null, "isUtf8" => false);
	}
}
