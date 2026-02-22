<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PayBill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pay-bill {bill_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Confirm payment for a bill manually via CLI';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $billId = $this->argument('bill_id');
        $bill = Bill::find($billId);

        if (!$bill) {
            $this->error("Bill with ID {$billId} not found.");
            return 1;
        }

        if ($bill->status === 'lunas') {
            $this->warn("Bill #{$bill->bill_number} is already paid.");
            return 0;
        }

        $this->info("Confirming payment for Bill #{$bill->bill_number}...");
        $this->line("Taxpayer: {$bill->taxpayer->name}");
        $this->line("Amount: " . number_format($bill->amount, 0, ',', '.'));

        if ($this->confirm('Proceed with payment?')) {
            $payment = Payment::create([
                'tax_object_id' => $bill->tax_object_id,
                'taxpayer_id' => $bill->taxpayer_id,
                'bill_id' => $bill->id,
                'transaction_id' => 'CMDPAY-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'payment_method' => 'cash',
                'amount' => $bill->amount,
                'status' => 'success',
                'billing_period' => $bill->period,
                'paid_at' => Carbon::now(),
            ]);

            $bill->update(['status' => 'lunas']);

            $this->info("Success! Payment recorded with Transaction ID: {$payment->transaction_id}");
        } else {
            $this->info("Cancelled.");
        }

        return 0;
    }
}
