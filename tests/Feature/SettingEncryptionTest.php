<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingEncryptionTest extends TestCase
{
	use RefreshDatabase;

	public function test_sensitive_setting_is_encrypted_at_rest(): void
	{
		Setting::setValue("zyte_api_key", "super-secret");

		$this->assertEquals("super-secret", Setting::getValue("zyte_api_key"));
		$this->assertDatabaseMissing("settings", array(
			"key" => "zyte_api_key",
			"value" => "super-secret",
		));
	}
}
