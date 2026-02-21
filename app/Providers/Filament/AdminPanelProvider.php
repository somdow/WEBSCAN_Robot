<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\AdminLogin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
	public function panel(Panel $panel): Panel
	{
		return $panel
			->default()
			->id("admin")
			->path("admin")
			->login(AdminLogin::class)
			->brandName("HELLO WEB_SCANS Admin")
			->darkMode(false)
			->colors(array(
				"primary" => Color::Orange,
			))
			->navigationGroups(array(
				NavigationGroup::make()
					->label("Platform"),
				NavigationGroup::make()
					->label("Content"),
				NavigationGroup::make()
					->label("Billing"),
				NavigationGroup::make()
					->label("Monitoring"),
			))
			->renderHook(
				PanelsRenderHook::HEAD_END,
				fn (): HtmlString => new HtmlString("
					<style>
						.fi-body {
							background-color: #EBEBEB !important;
						}
						.fi-sidebar,
						.fi-sidebar nav {
							background-color: transparent !important;
							border-right: none !important;
						}
						.fi-topbar,
						.fi-topbar nav {
							background-color: transparent !important;
							border-bottom: none !important;
							box-shadow: none !important;
						}
						.fi-header {
							border-bottom: none !important;
							box-shadow: none !important;
						}
						.fi-input,
						.fi-input-wrp,
						textarea {
							background-color: #E5E7EB !important;
						}
					</style>
				"),
			)
			->discoverResources(in: app_path("Filament/Resources"), for: "App\Filament\Resources")
			->discoverPages(in: app_path("Filament/Pages"), for: "App\Filament\Pages")
			->pages(array(
				Dashboard::class,
			))
			->discoverWidgets(in: app_path("Filament/Widgets"), for: "App\Filament\Widgets")
			->widgets(array(
				AccountWidget::class,
			))
			->middleware(array(
				EncryptCookies::class,
				AddQueuedCookiesToResponse::class,
				StartSession::class,
				AuthenticateSession::class,
				ShareErrorsFromSession::class,
				VerifyCsrfToken::class,
				SubstituteBindings::class,
				DisableBladeIconComponents::class,
				DispatchServingFilamentEvent::class,
			))
			->authMiddleware(array(
				Authenticate::class,
			));
	}
}
