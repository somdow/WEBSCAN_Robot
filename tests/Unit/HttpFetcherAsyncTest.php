<?php

namespace Tests\Unit;

use App\DataTransferObjects\FetchResult;
use App\Services\Scanning\HttpFetcher;
use Tests\TestCase;

class HttpFetcherAsyncTest extends TestCase
{
	public function test_fetch_resources_concurrent_returns_keyed_results(): void
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

	public function test_fetch_resources_concurrent_handles_empty_array(): void
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

	public function test_concurrent_fetch_respects_concurrency_limit(): void
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
