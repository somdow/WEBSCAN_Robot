<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanModuleResult;
use App\Models\ScanPage;
use App\Models\User;
use App\Services\Scanning\ScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrawlScanTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;
	private Project $project;

	protected function setUp(): void
	{
		parent::setUp();

		$plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
		$this->project = Project::factory()->create(array("organization_id" => $this->organization->id));
	}

	public function test_free_plan_triggers_single_page_scan(): void
	{
		$this->actingAs($this->user)
			->post(route("scans.store", $this->project))
			->assertRedirect();

		$scan = Scan::latest()->first();

		$this->assertEquals("single", $scan->scan_type);
		$this->assertEquals(1, $scan->max_pages_requested);
		$this->assertEquals(0, $scan->crawl_depth_limit);
	}

	public function test_pro_plan_triggers_homepage_only_scan(): void
	{
		$proPlan = Plan::factory()->pro()->create();
		$this->organization->update(array("plan_id" => $proPlan->id));

		$this->actingAs($this->user)
			->post(route("scans.store", $this->project))
			->assertRedirect();

		$scan = Scan::latest()->first();

		$this->assertEquals("single", $scan->scan_type);
		$this->assertEquals(1, $scan->max_pages_requested);
		$this->assertEquals(0, $scan->crawl_depth_limit);
	}

	public function test_scan_is_crawl_scan_helper(): void
	{
		$singleScan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
			"scan_type" => "single",
		));

		$crawlScan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$this->assertFalse($singleScan->isCrawlScan());
		$this->assertTrue($crawlScan->isCrawlScan());
	}

	public function test_scan_page_belongs_to_scan(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$page = ScanPage::factory()->homepage()->create(array("scan_id" => $scan->id));

		$this->assertEquals($scan->id, $page->scan->id);
		$this->assertTrue($page->is_homepage);
	}

	public function test_scan_has_many_pages(): void
	{
		$scan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		ScanPage::factory()->homepage()->create(array("scan_id" => $scan->id));
		ScanPage::factory()->count(3)->create(array("scan_id" => $scan->id));

		$this->assertCount(4, $scan->pages);
		$this->assertNotNull($scan->homePage);
	}

	public function test_module_result_can_belong_to_scan_page(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$page = ScanPage::factory()->create(array("scan_id" => $scan->id));

		$result = ScanModuleResult::create(array(
			"scan_id" => $scan->id,
			"scan_page_id" => $page->id,
			"module_key" => "titleTag",
			"status" => "ok",
			"findings" => array(),
			"recommendations" => array(),
		));

		$this->assertEquals($page->id, $result->scanPage->id);
	}

	public function test_site_wide_results_have_null_scan_page_id(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$siteWideResult = ScanModuleResult::create(array(
			"scan_id" => $scan->id,
			"scan_page_id" => null,
			"module_key" => "robotsTxt",
			"status" => "ok",
			"findings" => array(),
			"recommendations" => array(),
		));

		$this->assertNull($siteWideResult->scan_page_id);
	}

	public function test_aggregate_score_calculation(): void
	{
		$scan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$homepage = ScanPage::factory()->homepage()->create(array(
			"scan_id" => $scan->id,
			"page_score" => 80,
		));

		$innerPage = ScanPage::factory()->create(array(
			"scan_id" => $scan->id,
			"page_score" => 60,
		));

		$siteWideResult = ScanModuleResult::create(array(
			"scan_id" => $scan->id,
			"module_key" => "robotsTxt",
			"status" => "ok",
			"findings" => array(),
			"recommendations" => array(),
		));

		$calculator = app(ScoreCalculator::class);
		$scanPages = $scan->pages()->get();
		$siteWideResults = $scan->moduleResults()->whereNull("scan_page_id")->get();

		$aggregateScore = $calculator->calculateAggregateScore($scanPages, $siteWideResults, false);

		$this->assertIsInt($aggregateScore);
		$this->assertGreaterThanOrEqual(0, $aggregateScore);
		$this->assertLessThanOrEqual(100, $aggregateScore);
	}

	public function test_page_detail_route_requires_authorization(): void
	{
		$scan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$page = ScanPage::factory()->create(array("scan_id" => $scan->id));

		$otherUser = User::factory()->create();
		$otherOrg = \App\Models\Organization::factory()->create();
		$otherOrg->users()->attach($otherUser, array("role" => "owner"));
		$otherUser->update(array("current_organization_id" => $otherOrg->id));

		$this->actingAs($otherUser)
			->get(route("scans.show-page", array($scan, $page)))
			->assertForbidden();
	}

	public function test_page_detail_route_loads_for_authorized_user(): void
	{
		$scan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$page = ScanPage::factory()->create(array("scan_id" => $scan->id));

		$this->actingAs($this->user)
			->get(route("scans.show-page", array($scan, $page)))
			->assertOk();
	}

	public function test_page_detail_route_rejects_mismatched_scan_page(): void
	{
		$scan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$otherScan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$page = ScanPage::factory()->create(array("scan_id" => $otherScan->id));

		$this->actingAs($this->user)
			->get(route("scans.show-page", array($scan, $page)))
			->assertNotFound();
	}

	public function test_crawl_scan_show_redirects_to_project_page(): void
	{
		$scan = Scan::factory()->crawl()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		ScanPage::factory()->homepage()->create(array("scan_id" => $scan->id));

		$this->actingAs($this->user)
			->get(route("scans.show", $scan))
			->assertRedirect(route("projects.show", array("project" => $this->project, "scan" => $scan)));
	}

	public function test_single_scan_show_redirects_to_project_page(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
			"scan_type" => "single",
		));

		$this->actingAs($this->user)
			->get(route("scans.show", $scan))
			->assertRedirect(route("projects.show", array("project" => $this->project, "scan" => $scan)));
	}

	public function test_scan_page_score_color_helpers(): void
	{
		$goodPage = ScanPage::factory()->create(array(
			"scan_id" => Scan::factory()->create(array(
				"project_id" => $this->project->id,
				"triggered_by" => $this->user->id,
			))->id,
			"page_score" => 85,
		));

		$warnPage = ScanPage::factory()->create(array(
			"scan_id" => $goodPage->scan_id,
			"page_score" => 55,
		));

		$badPage = ScanPage::factory()->create(array(
			"scan_id" => $goodPage->scan_id,
			"page_score" => 30,
		));

		$this->assertEquals("text-emerald-600", $goodPage->scoreColorClass());
		$this->assertEquals("text-amber-600", $warnPage->scoreColorClass());
		$this->assertEquals("text-red-600", $badPage->scoreColorClass());
	}

	public function test_scan_page_truncated_url(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
		));

		$page = ScanPage::factory()->create(array(
			"scan_id" => $scan->id,
			"url" => "https://example.com/very/long/path/that/exceeds/default/limit",
		));

		$truncated = $page->truncatedUrl(30);
		$this->assertLessThanOrEqual(33, mb_strlen($truncated));
	}

	public function test_plan_max_pages_per_scan_fillable(): void
	{
		$plan = Plan::factory()->create(array("max_pages_per_scan" => 50));

		$this->assertEquals(50, $plan->max_pages_per_scan);
	}

	public function test_overview_data_includes_only_sitewide_and_homepage_modules(): void
	{
		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"triggered_by" => $this->user->id,
			"status" => ScanStatus::Completed,
		));

		$homepage = ScanPage::factory()->homepage()->create(array(
			"scan_id" => $scan->id,
			"project_id" => $this->project->id,
			"url" => "https://example.com/",
		));

		$innerPage = ScanPage::factory()->create(array(
			"scan_id" => $scan->id,
			"project_id" => $this->project->id,
			"url" => "https://example.com/about",
			"is_homepage" => false,
		));

		ScanModuleResult::create(array(
			"scan_id" => $scan->id,
			"scan_page_id" => null,
			"module_key" => "robotsTxt",
			"status" => "ok",
			"findings" => array(),
			"recommendations" => array(),
		));

		ScanModuleResult::create(array(
			"scan_id" => $scan->id,
			"scan_page_id" => $homepage->id,
			"module_key" => "viewportMeta",
			"status" => "warning",
			"findings" => array(),
			"recommendations" => array(),
		));

		ScanModuleResult::create(array(
			"scan_id" => $scan->id,
			"scan_page_id" => $innerPage->id,
			"module_key" => "viewportMeta",
			"status" => "bad",
			"findings" => array(),
			"recommendations" => array(),
		));

		$scan->load("moduleResults");
		$viewData = app(\App\Services\Scanning\ScanViewDataService::class)->prepareScanViewData($scan);

		$this->assertNotNull($viewData);
		$this->assertEquals(2, $viewData["groupedResults"]->flatten(1)->count());
		$this->assertEquals(1, $viewData["statusCounts"]["ok"] ?? 0);
		$this->assertEquals(1, $viewData["statusCounts"]["warning"] ?? 0);
		$this->assertEquals(0, $viewData["statusCounts"]["bad"] ?? 0);
	}
}
