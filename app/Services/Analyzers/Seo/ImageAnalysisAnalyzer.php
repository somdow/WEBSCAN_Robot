<?php

namespace App\Services\Analyzers\Seo;

use App\Contracts\AnalyzerInterface;
use App\DataTransferObjects\AnalysisResult;
use App\DataTransferObjects\ScanContext;
use App\Enums\ModuleStatus;
use DOMElement;

class ImageAnalysisAnalyzer implements AnalyzerInterface
{
	private const LEGACY_FORMATS = array("bmp", "tiff", "tif");
	private const MODERN_FORMATS = array("webp", "avif");

	public function moduleKey(): string
	{
		return "imageAnalysis";
	}

	public function label(): string
	{
		return "Image Analysis";
	}

	public function category(): string
	{
		return "Usability & Performance";
	}

	public function weight(): int
	{
		return (int) config("scanning.weights.imageAnalysis", 6);
	}

	public function analyze(ScanContext $scanContext): AnalysisResult
	{
		$xpath = $scanContext->xpath;
		$baseUrl = $scanContext->effectiveUrl;

		$imageNodes = $xpath->query("//body//img[@src and string-length(@src)>0]");
		$totalImages = $imageNodes ? $imageNodes->length : 0;

		if ($totalImages === 0) {
			return new AnalysisResult(
				status: ModuleStatus::Info,
				findings: array(array("type" => "info", "message" => "No images found on this page.")),
				recommendations: array(),
			);
		}

		$metrics = $this->inspectImageNodes($imageNodes, $totalImages, $baseUrl);

		return $this->buildImageFindings($metrics);
	}

	/**
	 * Walk every <img> node and collect per-image details plus aggregate counts.
	 * Returns a structured metrics array consumed by buildImageFindings().
	 */
	private function inspectImageNodes(\DOMNodeList $imageNodes, int $totalImages, string $baseUrl): array
	{
		$missingAltCount = 0;
		$emptyAltCount = 0;
		$missingDimensionsCount = 0;
		$lazyLoadedCount = 0;
		$legacyFormatCount = 0;
		$modernFormatCount = 0;
		$imageDetails = array();

		for ($index = 0; $index < $totalImages; $index++) {
			$node = $imageNodes->item($index);
			if (!($node instanceof DOMElement)) {
				continue;
			}

			$rawSrc = trim($node->getAttribute("src"));

			if (stripos($rawSrc, "data:") === 0) {
				continue;
			}

			$absoluteSrc = $this->resolveImageUrl($rawSrc, $baseUrl);
			$hasAlt = $node->hasAttribute("alt");
			$altText = $hasAlt ? trim($node->getAttribute("alt")) : null;
			$altStatus = "ok";

			if (!$hasAlt) {
				$missingAltCount++;
				$altStatus = "missing";
			} elseif ($altText === "") {
				$emptyAltCount++;
				$altStatus = "empty";
			}

			$hasWidth = $node->hasAttribute("width") && $node->getAttribute("width") !== "";
			$hasHeight = $node->hasAttribute("height") && $node->getAttribute("height") !== "";

			if (!$hasWidth || !$hasHeight) {
				$missingDimensionsCount++;
			}

			$loadingAttr = strtolower(trim($node->getAttribute("loading")));
			if ($loadingAttr === "lazy") {
				$lazyLoadedCount++;
			}

			$extension = $this->extractFileExtension($rawSrc);
			if (in_array($extension, self::LEGACY_FORMATS, true)) {
				$legacyFormatCount++;
			} elseif (in_array($extension, self::MODERN_FORMATS, true)) {
				$modernFormatCount++;
			}

			$imageDetails[] = array(
				"src" => $absoluteSrc,
				"alt" => $altText,
				"alt_status" => $altStatus,
			);
		}

		return array(
			"imageDetails" => $imageDetails,
			"missingAltCount" => $missingAltCount,
			"emptyAltCount" => $emptyAltCount,
			"missingDimensionsCount" => $missingDimensionsCount,
			"lazyLoadedCount" => $lazyLoadedCount,
			"legacyFormatCount" => $legacyFormatCount,
			"modernFormatCount" => $modernFormatCount,
		);
	}

	/**
	 * Convert aggregated image metrics into findings, recommendations, and status.
	 */
	private function buildImageFindings(array $metrics): AnalysisResult
	{
		$imageDetails = $metrics["imageDetails"];
		$missingAltCount = $metrics["missingAltCount"];
		$emptyAltCount = $metrics["emptyAltCount"];
		$missingDimensionsCount = $metrics["missingDimensionsCount"];
		$lazyLoadedCount = $metrics["lazyLoadedCount"];
		$legacyFormatCount = $metrics["legacyFormatCount"];
		$modernFormatCount = $metrics["modernFormatCount"];
		$validCount = count($imageDetails);

		$findings = array();
		$recommendations = array();
		$issues = array();

		$findings[] = array("type" => "info", "message" => "Found {$validCount} images. Missing alt: {$missingAltCount}. Empty alt: {$emptyAltCount}.");
		$findings[] = array("type" => "data", "key" => "imageDetails", "value" => $imageDetails);

		$issues = array_merge(
			$issues,
			$this->checkAltAttributes($missingAltCount, $emptyAltCount, $validCount, $findings, $recommendations),
			$this->checkDimensions($missingDimensionsCount, $findings, $recommendations),
			$this->checkLazyLoading($validCount, $lazyLoadedCount, $findings, $recommendations),
			$this->checkFormats($legacyFormatCount, $modernFormatCount, $validCount, $findings, $recommendations),
		);

		$status = match (true) {
			in_array("bad", $issues, true) => ModuleStatus::Bad,
			in_array("warning", $issues, true) => ModuleStatus::Warning,
			default => ModuleStatus::Ok,
		};

		return new AnalysisResult(status: $status, findings: $findings, recommendations: $recommendations);
	}

	/**
	 * Flag missing and empty alt attributes on images.
	 */
	private function checkAltAttributes(int $missingAltCount, int $emptyAltCount, int $validCount, array &$findings, array &$recommendations): array
	{
		$issues = array();

		if ($missingAltCount > 0) {
			$findings[] = array("type" => "bad", "message" => "{$missingAltCount} image(s) are missing alt attributes entirely. Alt text is essential for accessibility and image SEO.");
			$recommendations[] = "Add descriptive alt attributes to all images. Describe the image content and include relevant keywords where natural.";
			$issues[] = "bad";
		}

		if ($emptyAltCount > 0) {
			$findings[] = array("type" => "warning", "message" => "{$emptyAltCount} image(s) have empty alt attributes. Empty alt is appropriate for decorative images but not for content images.");
			if ($missingAltCount === 0) {
				$recommendations[] = "Review images with empty alt attributes. If they convey information, add descriptive text.";
			}
			$issues[] = "warning";
		}

		if ($missingAltCount === 0 && $emptyAltCount === 0) {
			$findings[] = array("type" => "ok", "message" => "All {$validCount} images have alt attributes.");
		}

		return $issues;
	}

	/**
	 * Flag images missing explicit width/height attributes that cause CLS.
	 */
	private function checkDimensions(int $missingDimensionsCount, array &$findings, array &$recommendations): array
	{
		if ($missingDimensionsCount === 0) {
			return array();
		}

		$findings[] = array("type" => "warning", "message" => "{$missingDimensionsCount} image(s) are missing explicit width/height attributes. This causes layout shifts (CLS) as the browser cannot reserve space before the image loads.");
		$recommendations[] = "Add width and height attributes to all images to prevent Cumulative Layout Shift (CLS), a Core Web Vital metric.";

		return array("warning");
	}

	/**
	 * Report lazy loading adoption when the page has enough images to benefit.
	 */
	private function checkLazyLoading(int $validCount, int $lazyLoadedCount, array &$findings, array &$recommendations): array
	{
		$notLazyCount = $validCount - $lazyLoadedCount;

		if ($validCount <= 3 || $notLazyCount <= 3) {
			return array();
		}

		$findings[] = array("type" => "info", "message" => "{$lazyLoadedCount} of {$validCount} images use loading=\"lazy\". {$notLazyCount} images load eagerly.");

		if ($lazyLoadedCount === 0) {
			$recommendations[] = "Add loading=\"lazy\" to below-the-fold images to defer loading until they are near the viewport, reducing initial page load time.";
		}

		return array();
	}

	/**
	 * Flag legacy image formats and acknowledge modern format adoption.
	 */
	private function checkFormats(int $legacyFormatCount, int $modernFormatCount, int $validCount, array &$findings, array &$recommendations): array
	{
		$issues = array();

		if ($legacyFormatCount > 0) {
			$findings[] = array("type" => "warning", "message" => "{$legacyFormatCount} image(s) use legacy formats (BMP/TIFF). These formats are large and not optimized for web delivery.");
			$recommendations[] = "Convert BMP/TIFF images to modern web formats (WebP or AVIF) for dramatically smaller file sizes.";
			$issues[] = "warning";
		}

		if ($modernFormatCount > 0 && $modernFormatCount === $validCount) {
			$findings[] = array("type" => "ok", "message" => "All images use modern formats (WebP/AVIF).");
		} elseif ($modernFormatCount > 0) {
			$findings[] = array("type" => "info", "message" => "{$modernFormatCount} of {$validCount} images use modern formats (WebP/AVIF).");
		}

		return $issues;
	}

	/**
	 * Extract file extension from an image URL, ignoring query parameters.
	 */
	private function extractFileExtension(string $imageUrl): string
	{
		$path = parse_url($imageUrl, PHP_URL_PATH);

		if ($path === null || $path === false) {
			return "";
		}

		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

		return $extension;
	}

	/**
	 * Resolve a relative image URL against the page's base URL.
	 */
	private function resolveImageUrl(string $imageUrl, string $baseUrl): string
	{
		$imageUrl = trim($imageUrl);

		if ($imageUrl === "" || preg_match("~^(#|data:|javascript:)~i", $imageUrl)) {
			return $imageUrl;
		}

		if (preg_match("~^https?://~i", $imageUrl)) {
			return $imageUrl;
		}

		$baseParts = parse_url($baseUrl);

		if ($baseParts === false || !isset($baseParts["scheme"], $baseParts["host"])) {
			return $imageUrl;
		}

		$scheme = $baseParts["scheme"];
		$host = $baseParts["host"];
		$port = isset($baseParts["port"]) ? ":" . $baseParts["port"] : "";
		$origin = "{$scheme}://{$host}{$port}";

		if (str_starts_with($imageUrl, "//")) {
			return "{$scheme}:{$imageUrl}";
		}

		if (str_starts_with($imageUrl, "/")) {
			return "{$origin}{$imageUrl}";
		}

		$basePath = $baseParts["path"] ?? "/";
		$baseDir = dirname($basePath);

		if ($baseDir === "." || $baseDir === "\\") {
			$baseDir = "/";
		}

		if (!str_ends_with($baseDir, "/")) {
			$baseDir .= "/";
		}

		return "{$origin}{$baseDir}{$imageUrl}";
	}
}
