<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a URL resolves to a public (non-private) IP address.
 * Prevents SSRF attacks where users submit URLs pointing to internal infrastructure.
 */
class SafeExternalUrl implements ValidationRule
{
	private const BLOCKED_HOSTS = array(
		"localhost",
		"127.0.0.1",
		"::1",
		"0.0.0.0",
	);

	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		$host = parse_url($value, PHP_URL_HOST);

		if ($host === null || $host === false || $host === "") {
			$fail("The :attribute must contain a valid hostname.");
			return;
		}

		if (in_array(strtolower($host), self::BLOCKED_HOSTS, true)) {
			$fail("The :attribute must not point to a local address.");
			return;
		}

		$resolvedIps = gethostbynamel($host);

		if ($resolvedIps === false || empty($resolvedIps)) {
			$fail("The :attribute hostname could not be resolved.");
			return;
		}

		foreach ($resolvedIps as $ip) {
			if ($this->isPrivateOrReservedIp($ip)) {
				$fail("The :attribute must not point to a private or reserved IP address.");
				return;
			}
		}
	}

	private function isPrivateOrReservedIp(string $ip): bool
	{
		return filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		) === false;
	}
}
