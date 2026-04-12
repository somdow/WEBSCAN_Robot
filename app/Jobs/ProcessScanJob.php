<?php

namespace App\Jobs;

use App\Enums\ScanStatus;
use App\Models\Scan;
use App\Services\BillingService;
use App\Services\Scanning\ScanOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScanJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $tries = 3;

	public int $timeout = 110;

	/**
	 * Backoff between retries: 10s, 30s, 60s
	 */
	public function backoff(): array
	{
		return array(10, 30, 60);
	}

	public function __construct(
		public readonly Scan $scan,
	) {}

	/**
	 * Timeout for homepage scan: 120 seconds is sufficient for single-page analysis.
	 */
	public function retryUntil(): \DateTime
	{
		return now()->addSeconds(120);
	}

	public function handle(ScanOrchestrator $orchestrator): void
	{
		ini_set("memory_limit", "512M");

		$this->scan->refresh();

		if ($this->scan->status !== ScanStatus::Pending) {
			Log::info("ProcessScanJob skipped — scan already in {$this->scan->status->value} state", array(
				"scan_id" => $this->scan->id,
			));
			return;
		}

		$orchestrator->executeScan($this->scan);
	}

	/**
	 * Handle permanent failure after all retries exhausted.
	 * Ensures the scan never stays stuck in "running" state.
	 */
	public function failed(\Throwable $exception): void
	{
		Log::error("ProcessScanJob permanently failed", array(
			"scan_id" => $this->scan->id,
			"project_id" => $this->scan->project_id,
			"exception" => $exception->getMessage(),
		));

		app(BillingService::class)->releaseCreditForModel($this->scan);

		$this->scan->update(array(
			"status" => ScanStatus::Failed,
		));
	}
}
