<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendDueDateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:notify-due {--days=14 : Number of days before due date}';

    /**
     * The console command description.
     */
    protected $description = 'Send automatic notifications (WhatsApp) for bills expiring in given days (default 14 days / H-14).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) ($this->option('days') ?: 14);
        $targetDate = Carbon::now()->addDays($days)->toDateString();

        $bills = Bill::where('status', 'pending')
            ->whereDate('due_date', $targetDate)
            ->with(['taxpayer', 'taxObject.taxpayer', 'taxObject'])
            ->get();

        $this->info("Found " . $bills->count() . " bills due on $targetDate (H-{$days})");

        $waService = app(\App\Services\WhatsAppService::class);

        foreach ($bills as $bill) {
            $taxpayer = $bill->taxpayer ?? optional($bill->taxObject)->taxpayer ?? null;
            if (!$taxpayer || empty($taxpayer->phone)) {
                continue;
            }

            $taxObjectName = $bill->taxObject?->name ?? 'Objek Retribusi/Pajak';
            $formattedAmount = number_format($bill->amount, 0, ',', '.');
            $formattedDueDate = Carbon::parse($bill->due_date)->format('d/m/Y');

            $message = "Yth. Wajib Pajak {$taxpayer->name},\n\nTagihan {$bill->bill_number} untuk objek {$taxObjectName} sebesar Rp {$formattedAmount} akan jatuh tempo pada {$formattedDueDate}.\n\nMohon segera lakukan pembayaran melalui aplikasi M-PAD Kota Baubau atau melalui kanal perbankan rekanan untuk menghindari sanksi administratif denda keterlambatan 1% per bulan.\n\nTerima kasih.\nBAPENDA KOTA BAUBAU";

            $sent = $waService->sendMessage($taxpayer->phone, $message);

            if ($sent) {
                $billMetadata = $bill->metadata ?? [];
                $billMetadata['due_date_notified_at'] = now()->toISOString();
                $billMetadata['due_date_notification_days'] = $days;
                $bill->update(['metadata' => $billMetadata]);
                $this->line("Sent H-{$days} notification to {$taxpayer->name} ({$taxpayer->phone})");
            } else {
                Log::warning("Failed sending H-{$days} notification to {$taxpayer->phone} for Bill #{$bill->id}");
            }
        }

        $this->info("Notifications dispatched successfully.");
    }
}
