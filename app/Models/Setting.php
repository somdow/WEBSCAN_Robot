<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
	protected $primaryKey = "key";
	public $incrementing = false;
	protected $keyType = "string";

	protected $fillable = array(
		"key",
		"value",
	);

	private const CACHE_PREFIX = "setting:";
	private const CACHE_TTL_SECONDS = 3600;
	private const SENSITIVE_KEYS = array(
		"whatcms_api_key",
		"google_web_risk_api_key",
		"zyte_api_key",
	);

	/**
	 * Retrieve a setting value by key, with optional default.
	 * Results are cached for one hour to avoid repeated DB queries.
	 */
	public static function getValue(string $key, ?string $default = null): ?string
	{
		return Cache::remember(
			self::CACHE_PREFIX . $key,
			self::CACHE_TTL_SECONDS,
			function () use ($key, $default) {
				try {
					$setting = self::find($key);
					$storedValue = $setting?->value;
					if ($storedValue === null) {
						return $default;
					}

					if (!self::isSensitiveKey($key)) {
						return $storedValue;
					}

					try {
						return Crypt::decryptString($storedValue);
					} catch (\Throwable) {
						/* Backward compatibility: pre-encryption plaintext values */
						return $storedValue;
					}
				} catch (\Throwable $exception) {
					return $default;
				}
			}
		);
	}

	/**
	 * Set a setting value by key. Creates or updates the record and busts cache.
	 */
	public static function setValue(string $key, ?string $value): void
	{
		$storedValue = $value;
		if (self::isSensitiveKey($key) && $value !== null && $value !== "") {
			$storedValue = Crypt::encryptString($value);
		}

		self::updateOrCreate(
			array("key" => $key),
			array("value" => $storedValue),
		);

		Cache::forget(self::CACHE_PREFIX . $key);
	}

	private static function isSensitiveKey(string $key): bool
	{
		return in_array($key, self::SENSITIVE_KEYS, true);
	}
}
