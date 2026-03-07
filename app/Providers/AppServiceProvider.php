<?php

namespace App\Providers;

use App\Contracts\AnalyzerInterface;
use App\Services\Analyzers\Content\ContentDuplicateAnalyzer;
use App\Services\Analyzers\Content\ContentKeywordsAnalyzer;
use App\Services\Analyzers\Content\ContentReadabilityAnalyzer;
use App\Services\Analyzers\Content\KeywordConsistencyAnalyzer;
use App\Services\Analyzers\Eeat\EatAuthorAnalyzer;
use App\Services\Analyzers\Eeat\EatBusinessSchemaAnalyzer;
use App\Services\Analyzers\Eeat\EatPrivacyTermsAnalyzer;
use App\Services\Analyzers\Eeat\EatTrustPagesAnalyzer;
use App\Services\Analyzers\Security\ExposedSensitiveFilesAnalyzer;
use App\Services\Analyzers\Security\MixedContentAnalyzer;
use App\Services\Analyzers\Security\SecurityHeadersAnalyzer;
use App\Services\Analyzers\Security\SslCertificateAnalyzer;
use App\Services\Analyzers\Seo\BlacklistCheckAnalyzer;
use App\Services\Analyzers\Seo\CanonicalTagAnalyzer;
use App\Services\Analyzers\Seo\CoreWebVitalsDesktopAnalyzer;
use App\Services\Analyzers\Seo\CoreWebVitalsMobileAnalyzer;
use App\Services\Analyzers\Seo\AccessibilityCheckAnalyzer;
use App\Services\Analyzers\Seo\CompressionCheckAnalyzer;
use App\Services\Analyzers\Seo\DoctypeCharsetAnalyzer;
use App\Services\Analyzers\Seo\DuplicateUrlAnalyzer;
use App\Services\Analyzers\Seo\FaviconAnalyzer;
use App\Services\Analyzers\Seo\H1TagAnalyzer;
use App\Services\Analyzers\Seo\H2H6TagsAnalyzer;
use App\Services\Analyzers\Seo\BreadcrumbAnalyzer;
use App\Services\Analyzers\Seo\HreflangAnalyzer;
use App\Services\Analyzers\Seo\HtmlLangAnalyzer;
use App\Services\Analyzers\Seo\HttpHeadersAnalyzer;
use App\Services\Analyzers\Seo\HttpsRedirectAnalyzer;
use App\Services\Analyzers\Seo\ImageAnalysisAnalyzer;
use App\Services\Analyzers\Seo\BrokenLinksAnalyzer;
use App\Services\Analyzers\Seo\LinkAnalysisAnalyzer;
use App\Services\Analyzers\Seo\MetaDescriptionAnalyzer;
use App\Services\Analyzers\Seo\NoindexCheckAnalyzer;
use App\Services\Analyzers\Seo\PerformanceHintsAnalyzer;
use App\Services\Analyzers\Seo\RedirectChainAnalyzer;
use App\Services\Analyzers\Seo\RobotsMetaAnalyzer;
use App\Services\Analyzers\Seo\RobotsTxtAnalyzer;
use App\Services\Analyzers\Seo\SchemaOrgAnalyzer;
use App\Services\Analyzers\Seo\SchemaValidationAnalyzer;
use App\Services\Analyzers\Seo\SemanticHtmlAnalyzer;
use App\Services\Analyzers\Seo\SitemapAnalysisAnalyzer;
use App\Services\Analyzers\Seo\SocialTagsAnalyzer;
use App\Services\Analyzers\Seo\TitleTagAnalyzer;
use App\Services\Analyzers\Seo\UrlStructureAnalyzer;
use App\Services\Analyzers\Seo\ViewportTagAnalyzer;
use App\Services\Analyzers\Utility\AnalyticsDetectionAnalyzer;
use App\Services\Analyzers\Utility\GoogleMapEmbedAnalyzer;
use App\Services\Analyzers\Utility\TechStackDetectionAnalyzer;
use App\Services\Analyzers\Utility\SerpPreviewAnalyzer;
use App\Services\Analyzers\WordPress\WpDetectionAnalyzer;
use App\Services\Analyzers\WordPress\WpPluginsAnalyzer;
use App\Services\Analyzers\WordPress\WpThemeAnalyzer;
use App\Services\Ai\AiGatewayFactory;
use App\Services\Ai\AiKeyResolver;
use App\Services\Ai\OnDemandAiOptimizer;
use App\Services\Ai\Prompts\ModulePromptFactory;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\Scanning\HttpFetcher;
use App\Services\Scanning\PageSpeedInsightsClient;
use App\Services\Scanning\WebRiskClient;
use App\Services\Scanning\ModuleRegistry;
use App\Services\Scanning\ScanOrchestrator;
use App\Services\Scanning\WhatCmsClient;
use App\Services\Scanning\WordPressApiClient;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->singleton(PageSpeedInsightsClient::class, function ($app) {
			return new PageSpeedInsightsClient($app->make(HttpFetcher::class));
		});

		$this->app->singleton(ModuleRegistry::class, function ($app) {
			$registry = new ModuleRegistry();
			$httpFetcher = $app->make(HttpFetcher::class);
			$wpApiClient = $app->make(WordPressApiClient::class);
			$whatCmsClient = $app->make(WhatCmsClient::class);
			$webRiskClient = $app->make(WebRiskClient::class);
			$pageSpeedClient = $app->make(PageSpeedInsightsClient::class);

			$analyzers = $this->buildAnalyzerList($httpFetcher, $wpApiClient, $whatCmsClient, $webRiskClient, $pageSpeedClient);

			foreach ($analyzers as $analyzer) {
				$registry->register($analyzer);
			}

			return $registry;
		});

		$this->app->singleton(AiKeyResolver::class);

		$this->app->singleton(AiGatewayFactory::class, function ($app) {
			return new AiGatewayFactory($app->make(AiKeyResolver::class));
		});

		$this->app->singleton(OnDemandAiOptimizer::class, function ($app) {
			return new OnDemandAiOptimizer(
				$app->make(AiGatewayFactory::class),
				new ModulePromptFactory(),
			);
		});
	}

	public function boot(): void
	{
		Cashier::useCustomerModel(Organization::class);
		$this->shareSettingsWithViews();
	}

	/**
	 * Share common site settings with all Blade views via a lazy composer.
	 * Deferred until view render time so the database is available.
	 * Settings are cached for 1 hour via Setting::getValue().
	 */
	private function shareSettingsWithViews(): void
	{
		View::composer("*", function ($view) {
			$view->with(array(
				"siteName" => Setting::getValue("site_name", "HELLO WEB_SCANS"),
				"siteTagline" => Setting::getValue("site_tagline", ""),
				"analyzerCount" => Setting::getValue("analyzer_count", "43"),
				"supportEmail" => Setting::getValue("support_email", "support@helloseo.com"),
				"enterpriseEmail" => Setting::getValue("enterprise_email", "hello@helloseo.com"),
				"trialDays" => Setting::getValue("trial_days", "14"),
				"annualDiscountText" => Setting::getValue("annual_discount_text", "Save 20%"),
			));
		});
	}

	/**
	 * Build the full list of analyzer instances in registration order.
	 *
	 * @return AnalyzerInterface[]
	 */
	private function buildAnalyzerList(HttpFetcher $httpFetcher, WordPressApiClient $wpApiClient, WhatCmsClient $whatCmsClient, WebRiskClient $webRiskClient, PageSpeedInsightsClient $pageSpeedClient): array
	{
		return array(
			new RobotsTxtAnalyzer($httpFetcher),
			new WpDetectionAnalyzer($httpFetcher, $wpApiClient, $whatCmsClient),
			new SitemapAnalysisAnalyzer($httpFetcher),
			new TitleTagAnalyzer(),
			new MetaDescriptionAnalyzer(),
			new H1TagAnalyzer(),
			new H2H6TagsAnalyzer(),
			new CanonicalTagAnalyzer(),
			new HtmlLangAnalyzer(),
			new LinkAnalysisAnalyzer(),
			new BrokenLinksAnalyzer($httpFetcher),
			new RobotsMetaAnalyzer(),
			new ViewportTagAnalyzer(),
			new SocialTagsAnalyzer(),
			new SchemaOrgAnalyzer(),
			new SchemaValidationAnalyzer(),
			new PerformanceHintsAnalyzer(),
			new NoindexCheckAnalyzer(),
			new ImageAnalysisAnalyzer(),
			new DoctypeCharsetAnalyzer(),
			new FaviconAnalyzer(),
			new HttpHeadersAnalyzer(),
			new BlacklistCheckAnalyzer($webRiskClient),
			new SecurityHeadersAnalyzer(),
			new MixedContentAnalyzer(),
			new ExposedSensitiveFilesAnalyzer($httpFetcher),
			new SslCertificateAnalyzer(),
			new HttpsRedirectAnalyzer($httpFetcher),
			new DuplicateUrlAnalyzer($httpFetcher),
			new HreflangAnalyzer(),
			new SemanticHtmlAnalyzer(),
			new BreadcrumbAnalyzer(),
			new UrlStructureAnalyzer(),
			new CompressionCheckAnalyzer(),
			new AccessibilityCheckAnalyzer(),
			new CoreWebVitalsMobileAnalyzer($pageSpeedClient),
			new CoreWebVitalsDesktopAnalyzer($pageSpeedClient),
			new RedirectChainAnalyzer(),
			new EatAuthorAnalyzer(),
			new EatBusinessSchemaAnalyzer(),
			new EatTrustPagesAnalyzer(),
			new EatPrivacyTermsAnalyzer(),
			new ContentReadabilityAnalyzer(),
			new ContentKeywordsAnalyzer(),
			new KeywordConsistencyAnalyzer(),
			new ContentDuplicateAnalyzer(),
			new WpPluginsAnalyzer($wpApiClient),
			new WpThemeAnalyzer($httpFetcher, $wpApiClient),
			new TechStackDetectionAnalyzer(),
			new GoogleMapEmbedAnalyzer(),
			new AnalyticsDetectionAnalyzer(),
			new SerpPreviewAnalyzer(),
		);
	}
}
