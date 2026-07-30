<?php

namespace App\Jobs;

use App\Models\Taxpayer;
use App\Services\BpnIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBpnTaxpayerAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Taxpayer $taxpayer;

    /**
     * Create a new job instance.
     */
    public function __construct(Taxpayer $taxpayer)
    {
        $this->taxpayer = $taxpayer;
    }

    /**
     * Execute the job.
     */
    public function handle(BpnIntegrationService $bpnService): void
    {
        $bpnService->syncTaxpayerAssets($this->taxpayer);
    }
}
