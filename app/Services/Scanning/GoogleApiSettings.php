<?php

namespace App\Services\Scanning;

/**
 * Shared constants for Google API configuration stored in the settings table.
 * Both PageSpeedInsightsClient and WebRiskClient use the same API key.
 */
class GoogleApiSettings
{
	public const SHARED_API_KEY = "google_web_risk_api_key";
}
