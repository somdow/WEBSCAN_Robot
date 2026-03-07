<?php

namespace Tests\Feature;

use App\DataTransferObjects\FetchResult;
use App\Services\Scanning\HttpFetcher;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Integration tests for HttpFetcher::fetchResourcesConcurrent().
 * These make live network calls and require outbound internet access.
 */
#[Group("network")]
class HttpFetcherAsyncTest extends TestCase
{
	public function test_concurrent_fetch_returns_keyed_results(): void
	{
		$fetcher = app(HttpFetcher::class);

		$results = $fetcher->fetchResourcesConcurrent(
			array("google" => "https://www.google.com"),
			5,
			1,
		);

		$this->assertArrayHasKey("google", $results);
		$this->assertInstanceOf(FetchResult::class, $results["google"]);
	}

	public function test_concurrent_fetch_handles_empty_array(): void
	{
		$fetcher = app(HttpFetcher::class);

		$results = $fetcher->fetchResourcesConcurrent(array());

		$this->assertIsArray($results);
		$this->assertEmpty($results);
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
		$this->assertTrue($results["valid"]->successful);
		$this->assertNotNull($results["valid"]->httpStatusCode);
		$this->assertFalse($results["invalid"]->successful);
		$this->assertNull($results["invalid"]->httpStatusCode);
		$this->assertNotNull($results["invalid"]->errorMessage);
	}

	public function test_concurrent_fetch_returns_all_results_with_concurrency_cap(): void
	{
		$fetcher = app(HttpFetcher::class);

		$urls = array(
			"a" => "https://www.google.com",
			"b" => "https://www.google.com",
			"c" => "https://www.google.com",
		);

		$results = $fetcher->fetchResourcesConcurrent($urls, 5, 2);

		$this->assertCount(3, $results);
		$this->assertArrayHasKey("a", $results);
		$this->assertArrayHasKey("b", $results);
		$this->assertArrayHasKey("c", $results);
	}
}
