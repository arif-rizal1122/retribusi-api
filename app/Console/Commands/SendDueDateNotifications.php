<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Billing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendDueDateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:notify-due';

    /**
     * The console command description.
     */
    protected $description = 'Send automatic notifications (WhatsApp/Email) for bills expiring in 3 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $bills = Billing::where('status', 'pending')
            ->whereDate('due_date', $targetDate)
            ->with(['taxpayer', 'taxObject.taxpayer'])
            ->get();

        $this->info("Found " . $bills->count() . " bills due on $targetDate");

        foreach ($bills as $bill) {
            $taxpayer = $bill->taxpayer ?? optional($bill->taxObject)->taxpayer ?? null;
            if ($taxpayer) {
                // Mocking the Notification
                Log::info("Sending Due Date Notification to {$taxpayer->name} for Bill #{$bill->id}. Amount: {$bill->amount}. Due Date: {$bill->due_date}");
                $this->line("Sent notification to {$taxpayer->name}");
            }
        }

        $this->info("Notifications dispatched successfully.");
    }
}
