<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
	public function run(): void
	{
		$defaults = array(
			"site_name" => "HELLO WEB_SCANS",
			"site_tagline" => "your all-in-one SEO analysis platform",
			"analyzer_count" => "37",
			"support_email" => "support@helloseo.com",
			"enterprise_email" => "hello@helloseo.com",
			"trial_days" => "14",
			"annual_discount_text" => "Save 20%",
			"whatcms_api_key" => "",
		);

		foreach ($defaults as $key => $value) {
			Setting::firstOrCreate(
				array("key" => $key),
				array("value" => $value),
			);
		}
	}
}
