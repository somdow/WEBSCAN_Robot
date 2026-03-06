# Scan Parallelization Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Reduce scan time from ~60s to ~25-35s by parallelizing HTTP I/O within analyzers and across independent scan phases.

**Architecture:** Add async HTTP methods to `HttpFetcher` using Guzzle's native promise system (`getAsync`, `Promise\Utils::settle`). Modify HTTP-heavy analyzers to fire requests concurrently instead of sequentially. Group independent CWV API calls to run in parallel. No new dependencies — Guzzle async is already available.

**Tech Stack:** GuzzleHttp\Promise, GuzzleHttp\Pool, GuzzleHttp\Client::getAsync/headAsync, existing HttpFetcher + Laravel Http

---

## Summary of Changes

| Target | Current | After | Est. Savings |
|--------|---------|-------|-------------|
| BrokenLinksAnalyzer (50 URLs) | Sequential | 5-concurrent pool | 15-25s |
| CWV mobile + desktop | Sequential (2 API calls) | Parallel promises | 8-15s |
| TrustPageCrawler (6 pages) | Sequential | Parallel promises | 3-8s |
| ExposedSensitiveFiles (8 paths) | Sequential | Parallel promises | 2-5s |
| SitemapAnalysis child fetches | Sequential (up to 50) | 5-concurrent pool | 3-10s |

**Note:** Savings overlap since some phases run during different scan stages. Net expected: ~25-35s total scan time.

---

### Task 1: Add Async Fetch Methods to HttpFetcher

**Files:**
- Modify: `app/Services/Scanning/HttpFetcher.php`
- Create: `tests/Unit/HttpFetcherAsyncTest.php`

**Why:** Every HTTP-heavy analyzer uses `HttpFetcher::fetchResource()`. Adding async variants lets analyzers fire multiple requests concurrently without changing their interface.

**Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit;

use App\Services\Scanning\HttpFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class HttpFetcherAsyncTest extends TestCase
{
	public function test_fetch_resources_concurrent_returns_keyed_results(): void
	{
		$fetcher = app(HttpFetcher::class);

		// fetchResourcesConcurrent should accept keyed URL array and return keyed FetchResult array
		$this->assertTrue(method_exists($fetcher, "fetchResourcesConcurrent"));
	}

	public function test_fetch_resources_concurrent_returns_results_for_all_urls(): void
	{
		$fetcher = app(HttpFetcher::class);

		// Use real HTTP to verify the method works end-to-end
		// (integration test — will be slow but proves the async pipeline)
		$results = $fetcher->fetchResourcesConcurrent(
			array("google" => "https://www.google.com"),
			5,
			1,
		);

		$this->assertArrayHasKey("google", $results);
		$this->assertInstanceOf(\App\DataTransferObjects\FetchResult::class, $results["google"]);
	}

	public function test_concurrent_fetch_isolates_failures(): void
	{
		$fetcher = app(HttpFetcher::class);

		$results = $fetcher->fetchResourcesConcurrent(
			array(
				"valid" => "https://www.google.com",
				"invalid" => "https://this-domain-definitely-does-not-exist-xyz123.com",
			),
			5,
			2,
		);

		$this->assertCount(2, $results);
		$this->assertNotNull($results["valid"]->httpStatusCode);
		$this->assertNull($results["invalid"]->httpStatusCode);
	}
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HttpFetcherAsyncTest`
Expected: FAIL — method `fetchResourcesConcurrent` does not exist

**Step 3: Implement fetchResourcesConcurrent in HttpFetcher**

Add this method to `HttpFetcher`. It uses Guzzle's `Pool` to fire multiple requests with a configurable concurrency limit, returning keyed `FetchResult` objects.

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

/**
 * Fetch multiple URLs concurrently, returning keyed FetchResult objects.
 * Failed requests return a FetchResult with null status and error message.
 *
 * @param array<string, string> $urls Keyed array: ["label" => "https://..."]
 * @param int $timeoutSeconds Timeout per request
 * @param int $concurrency Max simultaneous requests (default 5)
 * @return array<string, FetchResult> Keyed results matching input keys
 */
public function fetchResourcesConcurrent(array $urls, int $timeoutSeconds = 5, int $concurrency = 5): array
{
	if (empty($urls)) {
		return array();
	}

	$userAgent = config("scanning.user_agent");
	$client = new GuzzleClient(array(
		"timeout" => $timeoutSeconds,
		"connect_timeout" => $timeoutSeconds,
		"verify" => true,
		"headers" => array(
			"User-Agent" => $userAgent,
		),
	));

	$results = array();
	$keys = array_keys($urls);

	$requests = function () use ($urls) {
		foreach ($urls as $key => $url) {
			yield $key => new Request("GET", $url);
		}
	};

	$pool = new Pool($client, $requests(), array(
		"concurrency" => $concurrency,
		"fulfilled" => function (GuzzleResponse $response, $key) use (&$results) {
			$results[$key] = new FetchResult(
				htmlContent: (string) $response->getBody(),
				httpStatusCode: $response->getStatusCode(),
				effectiveUrl: "",
				headers: $this->flattenHeaders($response->getHeaders()),
			);
		},
		"rejected" => function (\Throwable $exception, $key) use (&$results) {
			$results[$key] = new FetchResult(
				htmlContent: "",
				httpStatusCode: null,
				effectiveUrl: "",
				errorMessage: $exception->getMessage(),
			);
		},
	));

	$pool->promise()->wait();

	return $results;
}

/**
 * Flatten Guzzle's multi-value header arrays to single strings.
 */
private function flattenHeaders(array $headers): array
{
	$flat = array();
	foreach ($headers as $name => $values) {
		$flat[strtolower($name)] = implode(", ", $values);
	}
	return $flat;
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=HttpFetcherAsyncTest`
Expected: PASS (3 tests)

**Step 5: Commit**

```bash
git add app/Services/Scanning/HttpFetcher.php tests/Unit/HttpFetcherAsyncTest.php
git commit -m "Add concurrent HTTP fetch method to HttpFetcher using Guzzle Pool"
```

---

### Task 2: Parallelize BrokenLinksAnalyzer (Biggest Win)

**Files:**
- Modify: `app/Services/Analyzers/Seo/BrokenLinksAnalyzer.php`

**Why:** Probes up to 50 URLs sequentially at ~0.5-1s each = 25-50s. With 5 concurrent: ~5-10s.

**Step 1: Replace sequential probeUrls with concurrent version**

Replace the `probeUrls()` method to use `HttpFetcher::fetchResourcesConcurrent()`:

```php
private function probeUrls(array $urls): array
{
	$broken = array();
	$serverErrors = array();
	$unreachable = array();
	$okCount = 0;

	$keyedUrls = array();
	foreach ($urls as $index => $url) {
		$keyedUrls["link_{$index}"] = $url;
	}

	$results = $this->httpFetcher->fetchResourcesConcurrent($keyedUrls, 5, 5);

	foreach ($urls as $index => $url) {
		$key = "link_{$index}";
		$fetchResult = $results[$key] ?? null;

		if ($fetchResult === null) {
			$unreachable[] = array(
				"url" => $url,
				"reason" => "Probe failed",
			);
			continue;
		}

		$statusCode = $fetchResult->httpStatusCode;

		if ($statusCode === null) {
			$unreachable[] = array(
				"url" => $url,
				"reason" => $fetchResult->errorMessage ?? "Connection failed",
			);
			continue;
		}

		if ($statusCode === 404 || $statusCode === 410) {
			$broken[] = array(
				"url" => $url,
				"statusCode" => $statusCode,
				"reason" => $statusCode === 404 ? "Not Found" : "Gone",
			);
			continue;
		}

		if ($statusCode >= 500) {
			$serverErrors[] = array(
				"url" => $url,
				"statusCode" => $statusCode,
				"reason" => "Server Error ({$statusCode})",
			);
			continue;
		}

		$okCount++;
	}

	return array(
		"broken" => $broken,
		"serverErrors" => $serverErrors,
		"unreachable" => $unreachable,
		"okCount" => $okCount,
	);
}
```

**Step 2: Run existing tests**

Run: `php artisan test --filter=BrokenLink`
Expected: PASS (existing tests still pass with new implementation)

**Step 3: Commit**

```bash
git add app/Services/Analyzers/Seo/BrokenLinksAnalyzer.php
git commit -m "Parallelize broken links probing with 5-concurrent Guzzle pool"
```

---

### Task 3: Parallelize ExposedSensitiveFilesAnalyzer

**Files:**
- Modify: `app/Services/Analyzers/Security/ExposedSensitiveFilesAnalyzer.php`

**Why:** Probes ~8 paths sequentially. With concurrent: all 8 fire at once.

**Step 1: Refactor to use concurrent fetching**

Find the method that loops over file specs and probes each. Replace the sequential loop with:
1. Build a keyed URL map from all file specs
2. Call `fetchResourcesConcurrent()` with all URLs at once (concurrency 8)
3. Iterate results and apply content marker validation

The content marker validation (checking response body for specific strings) stays the same — it just operates on the FetchResult objects returned from the concurrent fetch instead of inline.

**Step 2: Run existing tests**

Run: `php artisan test --filter=ExposedSensitive`
Expected: PASS

**Step 3: Commit**

```bash
git add app/Services/Analyzers/Security/ExposedSensitiveFilesAnalyzer.php
git commit -m "Parallelize exposed sensitive files probing with concurrent fetch"
```

---

### Task 4: Parallelize TrustPageCrawler

**Files:**
- Modify: `app/Services/Scanning/TrustPageCrawler.php`

**Why:** Fetches up to 6 trust pages sequentially at ~1-2s each = 6-12s. Concurrent: ~2-3s.

**Step 1: Refactor trust page fetching**

Find the method that fetches trust pages one at a time. Replace with:
1. Collect all trust page URLs first (the URL matching/filtering step stays unchanged)
2. Call `HttpFetcher::fetchResourcesConcurrent()` with all matched URLs at once
3. Process each FetchResult through the existing content analysis logic

**Step 2: Run existing tests**

Run: `php artisan test --filter=TrustPage`
Expected: PASS

**Step 3: Commit**

```bash
git add app/Services/Scanning/TrustPageCrawler.php
git commit -m "Parallelize trust page fetching with concurrent HTTP requests"
```

---

### Task 5: Parallelize CWV Mobile + Desktop

**Files:**
- Modify: `app/Services/Scanning/ScanOrchestrator.php`
- Modify: `app/Services/Scanning/PageSpeedInsightsClient.php`

**Why:** Two independent PageSpeed API calls run back-to-back (~8-15s each). Running both in parallel saves 8-15s.

**Step 1: Add async PageSpeed method**

Add `fetchAsync(string $url, string $strategy): PromiseInterface` to PageSpeedInsightsClient that returns a Guzzle promise instead of blocking. The method builds the same API URL but uses `$client->getAsync()` instead of `fetchResource()`.

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\PromiseInterface;

public function fetchAsync(string $targetUrl, string $strategy): PromiseInterface
{
	if (!$this->isConfigured()) {
		return \GuzzleHttp\Promise\Create::promiseFor($this->buildErrorResult("No PageSpeed Insights API key configured."));
	}

	$apiUrl = $this->buildApiUrl($targetUrl, $strategy);
	$client = new GuzzleClient(array("timeout" => 25, "verify" => true));

	return $client->getAsync($apiUrl)->then(
		function ($response) use ($strategy) {
			$body = json_decode((string) $response->getBody(), true);
			return $this->parseApiResponse($body, $strategy);
		},
		function (\Throwable $exception) use ($strategy) {
			return $this->buildErrorResult("PageSpeed API error: " . $exception->getMessage());
		},
	);
}
```

This requires extracting `buildApiUrl()` and `parseApiResponse()` as reusable private methods from the existing synchronous `fetch()` method.

**Step 2: Modify ScanOrchestrator to fire both CWV calls in parallel**

In `executeSinglePageScan()`, replace the sequential CWV mobile + desktop block:

```php
// Before (sequential):
$this->reportProgress($scan, 48, "Measuring Core Web Vitals (mobile)...");
$this->runCoreWebVitalsMobile($scan, $scanContext);
$this->reportProgress($scan, 56, "Measuring Core Web Vitals (desktop)...");
$screenshotBase64 = $this->runCoreWebVitalsDesktop($scan, $scanContext);

// After (parallel):
$this->reportProgress($scan, 48, "Measuring Core Web Vitals...");
$screenshotBase64 = $this->runCoreWebVitalsParallel($scan, $scanContext);
```

Create `runCoreWebVitalsParallel()` that:
1. Fires both mobile + desktop PageSpeed API calls as async promises
2. Waits for both with `Promise\Utils::all()`
3. Processes results through the existing CWV analyzer logic
4. Returns the screenshot base64 from the desktop result

**Step 3: Run existing CWV/scan tests**

Run: `php artisan test --filter=CrawlScanTest`
Expected: PASS

**Step 4: Commit**

```bash
git add app/Services/Scanning/PageSpeedInsightsClient.php app/Services/Scanning/ScanOrchestrator.php
git commit -m "Parallelize CWV mobile and desktop PageSpeed API calls"
```

---

### Task 6: Parallelize SitemapAnalysis Child Fetches

**Files:**
- Modify: `app/Services/Analyzers/Seo/SitemapAnalysisAnalyzer.php`

**Why:** Sitemap index files can reference up to 50 child sitemaps, each fetched sequentially. With 5 concurrent: ~10 batches instead of 50 sequential.

**Step 1: Refactor child sitemap fetching**

Find the loop that fetches child sitemaps from a sitemap index. Replace with:
1. Collect all child sitemap URLs
2. Call `HttpFetcher::fetchResourcesConcurrent()` with concurrency 5
3. Parse each XML response the same way

**Step 2: Run tests**

Run: `php artisan test --filter=Sitemap`
Expected: PASS

**Step 3: Commit**

```bash
git add app/Services/Analyzers/Seo/SitemapAnalysisAnalyzer.php
git commit -m "Parallelize sitemap index child fetches with concurrent pool"
```

---

### Task 7: Update Progress Labels

**Files:**
- Modify: `app/Services/Scanning/ScanOrchestrator.php`

**Why:** Progress messages like "Measuring Core Web Vitals (mobile)..." followed by "...(desktop)..." no longer make sense when both run simultaneously. Update to reflect grouped phases.

**Step 1: Update progress labels**

```php
// Replace granular labels with grouped ones:
$this->reportProgress($scan, 22, "Running security & connectivity checks...");
// (covers blacklist, security, HTTPS, duplicate URL)

$this->reportProgress($scan, 40, "Measuring Core Web Vitals...");
// (covers both mobile + desktop)

$this->reportProgress($scan, 60, "Analyzing content & trust signals...");
// (covers trust pages + SEO analyzers)
```

Adjust percentage values to reflect actual time distribution after parallelization.

**Step 2: Verify scan completes with correct progress**

Run a manual scan and verify the progress bar updates correctly.

**Step 3: Commit**

```bash
git add app/Services/Scanning/ScanOrchestrator.php
git commit -m "Update progress labels for parallelized scan phases"
```

---

### Task 8: Integration Test — Full Scan Timing

**Files:**
- No new files — manual verification

**Step 1: Run a real scan and measure time**

Before parallelization (baseline from recent scans), note the scan duration from `scans.created_at` to `scans.completed_at`.

After all parallelization changes, run the same scan and compare.

**Expected improvement:** 40-50% reduction in scan time (60s → 25-35s).

**Step 2: Verify error isolation**

Scan a site where some checks will fail (e.g., broken links, missing SSL). Verify that:
- Failed concurrent requests don't crash other requests
- All module results are still created correctly
- Score calculation is unchanged

**Step 3: Final commit**

```bash
git add -A
git commit -m "Scan parallelization complete — concurrent HTTP across analyzers"
```

---

## Implementation Order & Dependencies

```
Task 1 (HttpFetcher async) ──→ Task 2 (BrokenLinks)
                            ├─→ Task 3 (ExposedFiles)
                            ├─→ Task 4 (TrustPages)
                            └─→ Task 6 (Sitemaps)

Task 5 (CWV parallel) ──────→ independent (uses Guzzle directly)

Task 7 (Progress labels) ───→ after Tasks 1-6

Task 8 (Integration test) ──→ after all tasks
```

Tasks 2, 3, 4, 6 can be done in any order after Task 1. Task 5 is fully independent.

---

## Risk Mitigations

| Risk | Mitigation |
|------|-----------|
| Concurrent requests overwhelm target server | Cap concurrency at 5 (broken links) or total URLs (trust/exposed) |
| One failed request crashes the pool | `Pool` with `rejected` callback isolates failures per-request |
| Rate limits on PageSpeed API | Only 2 concurrent (mobile+desktop) — well within limits |
| Windows/Laragon compatibility | Guzzle async uses cURL multi-handle, works on all platforms |
| Progress bar percentages feel wrong | Task 7 adjusts percentages to match new timing |
| Existing tests break | Each task runs existing tests before committing |
