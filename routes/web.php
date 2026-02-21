<?php

use App\Http\Controllers\AiOptimizationController;
use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectPageController;
use App\Http\Controllers\ReactivationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanReportController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
	return view("welcome");
});

Route::get("/pricing", PricingController::class)->name("pricing");

Route::get("/terms", [LegalController::class, "termsOfService"])->name("legal.terms");
Route::get("/privacy", [LegalController::class, "privacyPolicy"])->name("legal.privacy");
Route::get("/acceptable-use", [LegalController::class, "acceptableUse"])->name("legal.acceptable-use");

Route::get("/reactivate", [ReactivationController::class, "show"])->name("reactivate.show");
Route::post("/reactivate", [ReactivationController::class, "store"])->name("reactivate.store");

/* Team invitation acceptance — works for both logged-in and guest users */
Route::get("/invitations/{token}", [TeamInvitationController::class, "accept"])->name("team.invitations.accept");

Route::get("/dashboard", DashboardController::class)
	->middleware(["auth", "verified", "ensure.active"])
	->name("dashboard");

Route::middleware(["auth", "ensure.active"])->group(function () {
	Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
	Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
	Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");
	Route::patch("/profile/ai-settings", [AiSettingsController::class, "update"])->name("ai-settings.update");
});

Route::middleware(["auth", "verified", "ensure.active"])->group(function () {
	Route::post("organizations/{organization}/switch", [OrganizationController::class, "switchOrganization"])->name("organizations.switch");

	Route::post("onboarding", [OnboardingController::class, "store"])
		->middleware("enforce.plan:projects")
		->name("onboarding.store");

	Route::resource("projects", ProjectController::class)->except(array("store"));
	Route::post("projects", [ProjectController::class, "store"])
		->middleware("enforce.plan:projects")
		->name("projects.store");
	Route::post("projects/{project}/scan", [ScanController::class, "store"])
		->middleware("throttle:5,1")
		->name("scans.store");
	Route::get("scans", [ScanController::class, "index"])->name("scans.index");
	Route::get("scans/{scan}/progress", [ScanController::class, "progress"])->name("scans.progress");
	Route::get("scans/{scan}", [ScanController::class, "show"])->name("scans.show");
	Route::get("scans/{scan}/page/{scanPage}", [ScanController::class, "showPage"])->name("scans.show-page");
	Route::post("projects/{project}/pages", [ProjectPageController::class, "store"])
		->middleware("throttle:10,1")
		->name("project-pages.store");
	Route::get("projects/{project}/pages/{scanPage}/progress", [ProjectPageController::class, "progress"])->name("project-pages.progress");
	Route::post("projects/{project}/pages/{scanPage}/rescan", [ProjectPageController::class, "rescan"])
		->middleware("throttle:10,1")
		->name("project-pages.rescan");
	Route::post("projects/{project}/discover", [DiscoveryController::class, "discover"])
		->name("project-pages.discover");
	Route::get("projects/{project}/discovered", [DiscoveryController::class, "discoveredPages"])->name("project-pages.discovered");
	Route::post("projects/{project}/analyze-selected", [DiscoveryController::class, "analyzeSelected"])
		->middleware("throttle:10,1")
		->name("project-pages.analyze-selected");
	Route::post("projects/{project}/competitors", [CompetitorController::class, "store"])
		->middleware("throttle:5,1")
		->name("competitors.store");
	Route::delete("projects/{project}/competitors/{competitor:uuid}", [CompetitorController::class, "destroy"])
		->name("competitors.destroy");
	Route::post("projects/{project}/competitors/{competitor:uuid}/rescan", [CompetitorController::class, "rescan"])
		->middleware("throttle:5,1")
		->name("competitors.rescan");
	Route::get("projects/{project}/competitors/{competitor:uuid}/progress", [CompetitorController::class, "progress"])
		->name("competitors.progress");
	Route::get("projects/{project}/competitors/{competitor:uuid}", [CompetitorController::class, "show"])
		->name("competitors.show");
	Route::get("scans/{scan}/pdf", [ScanReportController::class, "download"])
		->middleware("throttle:5,1")
		->name("scans.pdf");

	Route::prefix("scans/{scan}/ai")->middleware("throttle:10,1")->group(function () {
		Route::post("module/{scanModuleResult}", [AiOptimizationController::class, "optimizeModule"])->name("ai.optimize-module");
		Route::post("executive-summary", [AiOptimizationController::class, "generateExecutiveSummary"])->name("ai.executive-summary");
		Route::get("status", [AiOptimizationController::class, "checkAiStatus"])->name("ai.status");
	});

	Route::prefix("team")->name("team.")->group(function () {
		Route::get("/", [TeamController::class, "index"])->name("index");
		Route::post("/invite", [TeamController::class, "invite"])->name("invite");
		Route::delete("/members/{member}", [TeamController::class, "removeMember"])->name("remove-member");
		Route::delete("/invitations/{invitation}", [TeamController::class, "cancelInvitation"])->name("cancel-invitation");
		Route::post("/invitations/{invitation}/resend", [TeamController::class, "resendInvitation"])->name("resend-invitation");
	});

	Route::get("branding", [BrandingController::class, "edit"])->name("branding.edit");
	Route::patch("branding", [BrandingController::class, "update"])->name("branding.update");
	Route::delete("branding/logo", [BrandingController::class, "destroyLogo"])->name("branding.destroy-logo");

	Route::prefix("billing")->name("billing.")->group(function () {
		Route::get("/", [BillingController::class, "index"])->name("index");
		Route::post("/checkout", [BillingController::class, "checkout"])->name("checkout");
		Route::get("/success", [BillingController::class, "success"])->name("success");
		Route::post("/change-plan", [BillingController::class, "changePlan"])->name("change-plan");
		Route::post("/cancel", [BillingController::class, "cancel"])->name("cancel");
		Route::post("/resume", [BillingController::class, "resume"])->name("resume");
		Route::get("/portal", [BillingController::class, "redirectToCustomerPortal"])->name("portal");
		Route::get("/invoices/{invoiceId}", [BillingController::class, "downloadInvoice"])->name("invoice.download");
	});
});

Route::post("/stripe/webhook", [StripeWebhookController::class, "handleWebhook"])->name("stripe.webhook");

require __DIR__ . "/auth.php";
