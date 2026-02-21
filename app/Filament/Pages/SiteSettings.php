<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class SiteSettings extends Page
{
	protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;

	protected static string | \UnitEnum | null $navigationGroup = "Platform";

	protected static ?string $navigationLabel = "Site Settings";

	protected static ?string $title = "Site Settings";

	protected static ?int $navigationSort = 40;

	protected string $view = "filament.pages.site-settings";

	public ?array $data = array();

	public function mount(): void
	{
		$this->form->fill(array(
			"site_name" => Setting::getValue("site_name", "HELLO WEB_SCANS"),
			"site_tagline" => Setting::getValue("site_tagline", "your all-in-one SEO analysis platform"),
			"analyzer_count" => Setting::getValue("analyzer_count", "37"),
			"support_email" => Setting::getValue("support_email", "support@helloseo.com"),
			"enterprise_email" => Setting::getValue("enterprise_email", "hello@helloseo.com"),
			"trial_days" => Setting::getValue("trial_days", "14"),
			"annual_discount_text" => Setting::getValue("annual_discount_text", "Save 20%"),
			"whatcms_api_key" => Setting::getValue("whatcms_api_key", ""),
			"google_web_risk_api_key" => Setting::getValue("google_web_risk_api_key", ""),
			"google_threat_commercial_mode" => (bool) Setting::getValue("google_threat_commercial_mode", false),
			"zyte_api_key" => Setting::getValue("zyte_api_key", ""),
			"zyte_enabled" => (bool) Setting::getValue("zyte_enabled", false),
			"registration_enabled" => (bool) Setting::getValue("registration_enabled", false),
		));
	}

	public function form(Schema $schema): Schema
	{
		return $schema
			->gap()
			->components(array(
				Section::make("Registration")
					->description("Control whether new users can sign up.")
					->schema(array(
						Toggle::make("registration_enabled")
							->label("Enable Registration")
							->helperText("OFF = the /register page redirects to login with a 'Registration is currently closed' message. ON = new users can sign up normally.")
							->default(false),
					))
					->columns(1),

				Section::make("Branding & Identity")
					->schema(array(
						TextInput::make("site_name")
							->label("Site Name")
							->helperText("Used in legal pages, onboarding, PDF reports, and email notifications.")
							->required()
							->maxLength(100),
						TextInput::make("site_tagline")
							->label("Tagline")
							->helperText("Short description used in welcome emails and marketing copy.")
							->required()
							->maxLength(200),
						TextInput::make("analyzer_count")
							->label("Analyzer Count")
							->helperText("Number of SEO analyzers displayed in onboarding and marketing. Update when adding modules.")
							->numeric()
							->required(),
					))
					->columns(2),

				Section::make("Contact")
					->schema(array(
						TextInput::make("support_email")
							->label("Support Email")
							->helperText("Displayed on legal pages and deactivation messages. Users contact this to request account deletion.")
							->email()
							->required()
							->maxLength(255),
						TextInput::make("enterprise_email")
							->label("Enterprise Email")
							->helperText("Contact email shown on pricing page for enterprise inquiries.")
							->email()
							->required()
							->maxLength(255),
					))
					->columns(2),

				Section::make("Marketing")
					->schema(array(
						TextInput::make("trial_days")
							->label("Trial Period (Days)")
							->helperText("Number shown in pricing page CTA buttons (e.g. 'Start 14-Day Trial').")
							->numeric()
							->required(),
						TextInput::make("annual_discount_text")
							->label("Annual Discount Badge")
							->helperText("Text on the annual billing toggle badge (e.g. 'Save 20%').")
							->required()
							->maxLength(50),
					))
					->columns(2),

				Section::make("WhatCMS")
					->description("CMS and technology stack detection.")
					->schema(array(
						TextInput::make("whatcms_api_key")
							->label("API Key")
							->helperText("Free tier: 500 detections/month. Leave blank to use HTML-based detection only.")
							->password()
							->revealable()
							->maxLength(255),
						Placeholder::make("whatcms_link")
							->label("")
							->content(new HtmlString(
								"<a href=\"https://whatcms.org/API\" target=\"_blank\" rel=\"noopener\" class=\"fi-api-link\" style=\"color: #f25a15 !important; text-decoration: underline; font-size: 0.875rem; font-weight: 500;\">Get your API key at whatcms.org/API &rarr;</a>"
							)),
					))
					->columns(1),

				Section::make("Google APIs")
					->description("Blacklist, malware detection, and Core Web Vitals.")
					->schema(array(
						TextInput::make("google_web_risk_api_key")
							->label("API Key")
							->helperText("Used for malware/phishing detection and PageSpeed Insights. Same key works for Safe Browsing (free) and Web Risk (commercial). Leave blank to skip blacklist checking.")
							->password()
							->revealable()
							->maxLength(255),
						Toggle::make("google_threat_commercial_mode")
							->label("Commercial Mode (Web Risk API)")
							->helperText("OFF = Google Safe Browsing v4 (free, non-commercial use). ON = Google Web Risk API (commercially licensed, 100K free lookups/month, requires billing). Turn this on when the app goes live as a paid service.")
							->default(false),
						Placeholder::make("google_link")
							->label("")
							->content(new HtmlString(
								"<a href=\"https://console.cloud.google.com/apis/credentials\" target=\"_blank\" rel=\"noopener\" class=\"fi-api-link\" style=\"color: #f25a15 !important; text-decoration: underline; font-size: 0.875rem; font-weight: 500;\">Manage keys at console.cloud.google.com &rarr;</a>"
							)),
					))
					->columns(1),

				Section::make("Zyte")
					->description("Browser rendering fallback for bot-protected sites.")
					->schema(array(
						TextInput::make("zyte_api_key")
							->label("API Key")
							->helperText("Bypasses bot protection (SiteGround, Cloudflare, Sucuri) via browser rendering. Cost: ~\$0.001/page. Only called when direct fetch is blocked. Leave blank to disable.")
							->password()
							->revealable()
							->maxLength(255),
						Toggle::make("zyte_enabled")
							->label("Enable Zyte Fallback")
							->helperText("OFF = blocked sites show a 'Blocked' status. ON = automatically retry blocked sites via Zyte browser rendering (requires API key above).")
							->default(false),
						Placeholder::make("zyte_link")
							->label("")
							->content(new HtmlString(
								"<a href=\"https://app.zyte.com/o/zyte-api/api-access\" target=\"_blank\" rel=\"noopener\" class=\"fi-api-link\" style=\"color: #f25a15 !important; text-decoration: underline; font-size: 0.875rem; font-weight: 500;\">Get your API key at app.zyte.com &rarr;</a>"
							)),
					))
					->columns(1),
			))
			->statePath("data");
	}

	public function save(): void
	{
		$state = $this->form->getState();

		$settingKeys = array(
			"site_name",
			"site_tagline",
			"analyzer_count",
			"support_email",
			"enterprise_email",
			"trial_days",
			"annual_discount_text",
			"whatcms_api_key",
			"google_web_risk_api_key",
			"google_threat_commercial_mode",
			"zyte_api_key",
			"zyte_enabled",
			"registration_enabled",
		);

		foreach ($settingKeys as $key) {
			Setting::setValue($key, $state[$key]);
		}

		Notification::make()
			->title("Settings saved")
			->success()
			->send();
	}
}
