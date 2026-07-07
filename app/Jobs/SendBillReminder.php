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

class SendBillReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    protected int $billId;

    public function __construct(int $billId)
    {
        $this->billId = $billId;
    }

    public function handle(AutoDeductService $service): void
    {
        $bill = Bill::with(['taxpayer', 'taxObject'])->find($this->billId);

        if (!$bill || !$bill->taxpayer) {
            Log::warning('SendBillReminder: Bill/Taxpayer not found', ['bill_id' => $this->billId]);
            return;
        }

        try {
            $service->sendBillNotification($bill);
            Log::info('SendBillReminder sent', ['bill' => $bill->bill_number, 'phone' => $bill->taxpayer->phone]);
        } catch (\Exception $e) {
            Log::error('SendBillReminder failed', [
                'bill' => $bill->bill_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
