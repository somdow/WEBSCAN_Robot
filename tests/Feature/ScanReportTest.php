<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanReportTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private Organization $organization;
	private Project $project;
	private Plan $plan;

	protected function setUp(): void
	{
		parent::setUp();

		$this->plan = Plan::factory()->free()->create();
		$this->user = User::factory()->create();
		$this->organization = Organization::factory()->withPlan($this->plan)->create();
		$this->organization->users()->attach($this->user->id, array("role" => "owner"));
		$this->project = Project::factory()->create(array("organization_id" => $this->organization->id));
	}

	public function test_pdf_download_requires_authentication(): void
	{
		$scan = Scan::factory()->create(array("project_id" => $this->project->id));

		$this->get(route("scans.pdf", $scan))
			->assertRedirect(route("login"));
	}

	public function test_pdf_download_requires_project_access(): void
	{
		$otherUser = User::factory()->create();
		$otherOrg = Organization::factory()->withPlan($this->plan)->create();
		$otherOrg->users()->attach($otherUser->id, array("role" => "owner"));

		$scan = Scan::factory()->create(array("project_id" => $this->project->id));

		$this->actingAs($otherUser)
			->get(route("scans.pdf", $scan))
			->assertForbidden();
	}

	public function test_pdf_download_requires_completed_scan(): void
	{
		$scan = Scan::factory()->running()->create(array("project_id" => $this->project->id));

		$this->actingAs($this->user)
			->get(route("scans.pdf", $scan))
			->assertRedirect(route("projects.show", array("project" => $this->project, "scan" => $scan)));
	}

	/**
	 * @group pdf
	 */
	public function test_pdf_download_succeeds_for_completed_scan(): void
	{
		ini_set("memory_limit", "256M");

		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"overall_score" => 75,
		));

		$response = $this->actingAs($this->user)
			->get(route("scans.pdf", $scan));

		$response->assertOk();
		$response->assertHeader("content-type", "application/pdf");
	}

	/**
	 * @group pdf
	 */
	public function test_pdf_download_has_correct_filename(): void
	{
		ini_set("memory_limit", "256M");

		$scan = Scan::factory()->create(array(
			"project_id" => $this->project->id,
			"overall_score" => 85,
		));

		$response = $this->actingAs($this->user)
			->get(route("scans.pdf", $scan));

		$disposition = $response->headers->get("content-disposition");
		$this->assertStringContains("website-audit-", $disposition);
		$this->assertStringContains(".pdf", $disposition);
	}

	private function assertStringContains(string $needle, ?string $haystack): void
	{
		$this->assertNotNull($haystack);
		$this->assertTrue(
			str_contains($haystack, $needle),
			"Failed asserting that '{$haystack}' contains '{$needle}'."
		);
	}
}
