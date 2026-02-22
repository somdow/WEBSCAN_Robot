<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
	public function run(): void
	{
		Plan::updateOrCreate(
			array("slug" => "free"),
			array(
				"name" => "Free",
				"description" => "Get started with basic SEO analysis. No credit card required.",
				"price_monthly" => 0,
				"price_annual" => 0,
				"max_users" => 1,
				"max_projects" => 1,
				"max_scans_per_month" => 10,
				"max_pages_per_scan" => 1,
				"max_additional_pages" => 0,
				"max_competitors" => 0,
				"scan_history_days" => 7,
				"ai_tier" => 1,
				"feature_flags" => array(
					"white_label" => false,
				),
				"is_public" => true,
				"sort_order" => 1,
			)
		);

		Plan::updateOrCreate(
			array("slug" => "pro"),
			array(
				"name" => "Pro",
				"description" => "For professionals who need deeper insights and more capacity.",
				"price_monthly" => 49.00,
				"price_annual" => 468.00,
				"max_users" => 3,
				"max_projects" => 5,
				"max_scans_per_month" => 100,
				"max_pages_per_scan" => 25,
				"max_additional_pages" => 10,
				"max_competitors" => 1,
				"scan_history_days" => 90,
				"ai_tier" => 2,
				"feature_flags" => array(
					"white_label" => false,
				),
				"is_public" => true,
				"sort_order" => 2,
			)
		);

		Plan::updateOrCreate(
			array("slug" => "agency"),
			array(
				"name" => "Agency",
				"description" => "Full-featured plan for agencies managing multiple clients.",
				"price_monthly" => 149.00,
				"price_annual" => 1428.00,
				"max_users" => 10,
				"max_projects" => 25,
				"max_scans_per_month" => 500,
				"max_pages_per_scan" => 100,
				"max_additional_pages" => 50,
				"max_competitors" => 5,
				"scan_history_days" => 36500,
				"ai_tier" => 3,
				"feature_flags" => array(
					"white_label" => true,
				),
				"is_public" => true,
				"sort_order" => 3,
			)
		);

		Plan::updateOrCreate(
			array("slug" => "preview"),
			array(
				"name" => "Preview",
				"description" => "Internal preview/gift access plan. Not publicly listed.",
				"stripe_monthly_price_id" => null,
				"stripe_annual_price_id" => null,
				"price_monthly" => 0,
				"price_annual" => 0,
				"max_users" => 10,
				"max_projects" => 25,
				"max_scans_per_month" => 500,
				"max_pages_per_scan" => 100,
				"max_additional_pages" => 50,
				"max_competitors" => 5,
				"scan_history_days" => 36500,
				"ai_tier" => 3,
				"feature_flags" => array(
					"white_label" => true,
				),
				"is_public" => false,
				"sort_order" => 99,
			)
		);
	}
}
