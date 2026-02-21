<?php

namespace Tests\Feature;

use App\Enums\CreditState;
use App\Jobs\ProcessPageAnalysisJob;
use App\Jobs\ProcessScanJob;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanPage;
use App\Models\SubscriptionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanCreditReleaseTest extends TestCase
{
	use RefreshDatabase;

	public function test_scan_job_failure_releases_claimed_credit(): void
	{
		$plan = Plan::factory()->free()->create();
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));
		$project = Project::factory()->create(array("organization_id" => $organization->id));

		$usage = SubscriptionUsage::resolveCurrentPeriod($organization);
		$usage->update(array("scans_used" => 1));

		$scan = Scan::factory()->create(array(
			"project_id" => $project->id,
			"triggered_by" => $user->id,
			"credit_state" => CreditState::Claimed->value,
		));

		$job = new ProcessScanJob($scan);
		$job->failed(new \RuntimeException("forced failure"));

		$this->assertEquals(0, $usage->fresh()->scans_used);
		$this->assertEquals(CreditState::Released->value, $scan->fresh()->credit_state);
	}

	public function test_page_analysis_job_failure_releases_claimed_credit(): void
	{
		$plan = Plan::factory()->free()->create();
		$user = User::factory()->create();
		$organization = Organization::factory()->withPlan($plan)->create();
		$organization->users()->attach($user->id, array("role" => "owner"));
		$project = Project::factory()->create(array("organization_id" => $organization->id));

		$usage = SubscriptionUsage::resolveCurrentPeriod($organization);
		$usage->update(array("scans_used" => 1));

		$scanPage = ScanPage::factory()->create(array(
			"project_id" => $project->id,
			"scan_id" => null,
			"credit_state" => CreditState::Claimed->value,
			"analysis_status" => "pending",
		));

		$job = new ProcessPageAnalysisJob($scanPage);
		$job->failed(new \RuntimeException("forced failure"));

		$this->assertEquals(0, $usage->fresh()->scans_used);
		$this->assertEquals(CreditState::Released->value, $scanPage->fresh()->credit_state);
	}
}
