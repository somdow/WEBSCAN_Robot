<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanPageIntegrityTest extends TestCase
{
	use RefreshDatabase;

	public function test_scan_page_derives_project_id_from_scan_when_missing(): void
	{
		$plan = Plan::factory()->free()->create();
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));
		$project = Project::factory()->create(array("organization_id" => $organization->id));

		$scan = Scan::factory()->create(array(
			"project_id" => $project->id,
			"triggered_by" => $user->id,
		));

		$scanPage = ScanPage::create(array(
			"project_id" => null,
			"scan_id" => $scan->id,
			"url" => "https://example.com/about",
			"is_homepage" => false,
			"crawl_depth" => 1,
		));

		$this->assertEquals($project->id, $scanPage->fresh()->project_id);
	}
}
