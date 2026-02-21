<?php

namespace App\DataTransferObjects;

final readonly class FetchResult
{
	public function __construct(
		public bool $successful,
		public ?string $content,
		public array $headers,
		public ?int $httpStatusCode,
		public ?string $effectiveUrl,
		public ?float $timeToFirstByte,
		public ?float $totalTransferTime,
		public ?string $errorMessage,
		public bool $insecureTransportUsed = false,
	) {}
}
