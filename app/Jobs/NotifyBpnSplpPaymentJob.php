<?php

namespace App\Jobs;

use App\Models\BphtbSubmission;
use App\Models\H2hConnectorLog;
use App\Services\BhumiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyBpnSplpPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 120, 600, 3600, 21600];

    protected int $submissionId;
    protected string $paymentType;

    /**
     * Create a new job instance.
     */
    public function __construct(int $submissionId, string $paymentType = 'BPHTB')
    {
        $this->submissionId = $submissionId;
        $this->paymentType  = $paymentType;
    }

    /**
     * Execute the job.
     */
    public function handle(BhumiService $bhumiService): void
    {
        $submission = BphtbSubmission::find($this->submissionId);

        if (! $submission) {
            Log::warning("NotifyBpnSplpPaymentJob: BphtbSubmission #{$this->submissionId} tidak ditemukan.");
            return;
        }

        try {
            $result = $bhumiService->notifySplpLunas($submission);

            if (! empty($result['pushed'])) {
                Log::info("NotifyBpnSplpPaymentJob: Berhasil mengirim notifikasi SPLP BPN untuk submission #{$submission->id}");
            } elseif (($result['reason'] ?? '') === 'not_configured') {
                Log::info("NotifyBpnSplpPaymentJob: BHUMI belum terkonfigurasi untuk submission #{$submission->id}");
            } else {
                Log::warning("NotifyBpnSplpPaymentJob: Gagal push SPLP BPN submission #{$submission->id}", $result);
                // Throw exception agar job dicoba ulang oleh Laravel Queue sesuai setting $backoff
                if ($this->attempts() < $this->tries) {
                    throw new \RuntimeException("SPLP Push gagal: " . ($result['reason'] ?? 'unknown error'));
                }
            }
        } catch (\Exception $e) {
            Log::error("NotifyBpnSplpPaymentJob Exception: " . $e->getMessage(), [
                'submission_id' => $submission->id,
                'attempt'       => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
