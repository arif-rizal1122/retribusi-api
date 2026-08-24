<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Services\AutoDeductService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutoDeduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    protected string $billNumber;
    protected float $amount;
    protected string $paymentMethod;

    public function __construct(string $billNumber, float $amount, string $paymentMethod = 'va')
    {
        $this->billNumber = $billNumber;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
    }

    public function handle(AutoDeductService $service): void
    {
        $bill = Bill::where('bill_number', $this->billNumber)
            ->with(['taxObject.taxpayer', 'taxpayer'])
            ->first();

        if (!$bill || !$bill->taxObject) {
            Log::warning('ProcessAutoDeduct: Bill/TaxObject not found', [
                'bill_number' => $this->billNumber,
            ]);
            return;
        }

        try {
            $result = $service->processAutoPayment(
                $bill->taxObject,
                $this->amount,
                $this->paymentMethod
            );

            Log::info('ProcessAutoDeduct completed', [
                'bill' => $this->billNumber,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessAutoDeduct failed', [
                'bill' => $this->billNumber,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Will retry based on $tries
        }
    }
}
