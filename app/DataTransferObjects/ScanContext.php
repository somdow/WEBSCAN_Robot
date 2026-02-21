<?php

namespace App\DataTransferObjects;

use DOMDocument;
use DOMXPath;

final readonly class ScanContext
{
	public function __construct(
		public string $requestedUrl,
		public string $effectiveUrl,
		public string $htmlContent,
		public DOMDocument $domDocument,
		public DOMXPath $xpath,
		public array $responseHeaders,
		public int $httpStatusCode,
		public ?float $timeToFirstByte,
		public ?float $totalTransferTime,
		public ?string $titleContent,
		public int $titleTagCount,
		public ?string $metaDescriptionContent,
		public int $metaDescriptionTagCount,
		public ?string $langAttribute,
		public array $canonicalHrefs,
		public int $canonicalTagCount,
		public array $allHeadingsData,
		public array $viewportContents,
		public int $viewportTagCount,
		public ?string $robotsMetaContent,
		public int $robotsMetaTagCount,
		public ?string $robotsTxtContent,
		public bool $isWordPress,
		public ?string $detectionMethod = null,
		public array $targetKeywords = array(),
		public array $trustPages = array(),
		public array $techStack = array(),
	) {}

	/**
	 * Build the scheme + host root from the effective URL
	 */
	public function domainRoot(): string
	{
		$parts = parse_url($this->effectiveUrl);

		return ($parts["scheme"] ?? "https") . "://" . ($parts["host"] ?? "");
	}

	/**
	 * Create a new ScanContext with updated robotsTxtContent and isWordPress fields.
	 * Used after Phase 1 analyzers produce shared state.
	 */
	public function withPhaseOneResults(?string $robotsTxtContent, bool $isWordPress, ?string $detectionMethod = null, array $techStack = array()): self
	{
		return new self(
			requestedUrl: $this->requestedUrl,
			effectiveUrl: $this->effectiveUrl,
			htmlContent: $this->htmlContent,
			domDocument: $this->domDocument,
			xpath: $this->xpath,
			responseHeaders: $this->responseHeaders,
			httpStatusCode: $this->httpStatusCode,
			timeToFirstByte: $this->timeToFirstByte,
			totalTransferTime: $this->totalTransferTime,
			titleContent: $this->titleContent,
			titleTagCount: $this->titleTagCount,
			metaDescriptionContent: $this->metaDescriptionContent,
			metaDescriptionTagCount: $this->metaDescriptionTagCount,
			langAttribute: $this->langAttribute,
			canonicalHrefs: $this->canonicalHrefs,
			canonicalTagCount: $this->canonicalTagCount,
			allHeadingsData: $this->allHeadingsData,
			viewportContents: $this->viewportContents,
			viewportTagCount: $this->viewportTagCount,
			robotsMetaContent: $this->robotsMetaContent,
			robotsMetaTagCount: $this->robotsMetaTagCount,
			robotsTxtContent: $robotsTxtContent,
			isWordPress: $isWordPress,
			detectionMethod: $detectionMethod,
			targetKeywords: $this->targetKeywords,
			trustPages: $this->trustPages,
			techStack: $techStack,
		);
	}

	/**
	 * Create a new ScanContext with trust page crawl results.
	 * Used after the mini-crawl phase fetches About, Contact, Privacy, Terms pages.
	 */
	public function withTrustPageResults(array $trustPages): self
	{
		return new self(
			requestedUrl: $this->requestedUrl,
			effectiveUrl: $this->effectiveUrl,
			htmlContent: $this->htmlContent,
			domDocument: $this->domDocument,
			xpath: $this->xpath,
			responseHeaders: $this->responseHeaders,
			httpStatusCode: $this->httpStatusCode,
			timeToFirstByte: $this->timeToFirstByte,
			totalTransferTime: $this->totalTransferTime,
			titleContent: $this->titleContent,
			titleTagCount: $this->titleTagCount,
			metaDescriptionContent: $this->metaDescriptionContent,
			metaDescriptionTagCount: $this->metaDescriptionTagCount,
			langAttribute: $this->langAttribute,
			canonicalHrefs: $this->canonicalHrefs,
			canonicalTagCount: $this->canonicalTagCount,
			allHeadingsData: $this->allHeadingsData,
			viewportContents: $this->viewportContents,
			viewportTagCount: $this->viewportTagCount,
			robotsMetaContent: $this->robotsMetaContent,
			robotsMetaTagCount: $this->robotsMetaTagCount,
			robotsTxtContent: $this->robotsTxtContent,
			isWordPress: $this->isWordPress,
			detectionMethod: $this->detectionMethod,
			targetKeywords: $this->targetKeywords,
			trustPages: $trustPages,
			techStack: $this->techStack,
		);
	}

	/**
	 * Find a trust page entry by type (about, contact, privacy, terms, cookie).
	 * Returns null if no trust page of the given type was found.
	 */
	public function getTrustPageByType(string $type): ?array
	{
		foreach ($this->trustPages as $page) {
			if (($page["type"] ?? null) === $type) {
				return $page;
			}
		}

		return null;
	}
}
