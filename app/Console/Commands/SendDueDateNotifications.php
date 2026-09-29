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
    protected $signature = 'billing:notify-due {--days= : Specific days before due date (default runs H-7, H-3, and H-0)}';

    /**
     * The console command description.
     */
    protected $description = 'Kirim notifikasi pengingat jatuh tempo berkala (H-7, H-3, H-0) sesuai NOTIF-WRG-005.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\NotificationService $notifService)
    {
        $specificDays = $this->option('days');
        $dayIntervals = $specificDays !== null ? [(int)$specificDays] : [7, 3, 0];

        foreach ($dayIntervals as $days) {
            $targetDate = Carbon::now('Asia/Makassar')->addDays($days)->toDateString();

            $bills = Bill::where('status', 'pending')
                ->whereDate('due_date', $targetDate)
                ->with(['taxpayer', 'taxObject.taxpayer', 'taxObject', 'retributionType'])
                ->get();

            $this->info("Memeriksa tagihan jatuh tempo {$targetDate} (H-{$days}): {$bills->count()} ditemukan.");

            foreach ($bills as $bill) {
                $taxpayer = $bill->taxpayer ?? optional($bill->taxObject)->taxpayer ?? null;
                if (!$taxpayer || empty($taxpayer->phone)) {
                    continue;
                }

                // Check if already notified for this interval
                $billMeta = $bill->metadata ?? [];
                $notifiedDays = $billMeta['due_notified_days'] ?? [];
                if (in_array($days, $notifiedDays)) {
                    continue;
                }

                $namaLayanan = $bill->retributionType?->name ?: ($bill->taxObject?->name ?: 'Layanan M-PAD');
                $dueDateFormatted = Carbon::parse($bill->due_date)->format('d/m/Y');
                $briva = $bill->briva_number ?? ($bill->va_number ?? '-');
                $snap = $bill->snap_token ?? ($bill->payment_code ?? '-');

                $res = $notifService->notifyDueDateReminder(
                    phone: $taxpayer->phone,
                    namaWarga: $taxpayer->name ?: 'Wajib Pajak',
                    nomorSkrd: $bill->bill_number ?: (string)$bill->id,
                    namaLayanan: $namaLayanan,
                    nominal: $bill->total_amount,
                    tanggalJatuhTempo: $dueDateFormatted,
                    sisaHari: $days,
                    kodeSnap: $snap,
                    kodeBriva: $briva,
                    user: $taxpayer->user ?? null,
                    refId: $bill->id
                );

                if ($res['success'] ?? false) {
                    $notifiedDays[] = $days;
                    $billMeta['due_notified_days'] = $notifiedDays;
                    $billMeta['last_due_notified_at'] = now()->toISOString();
                    $bill->update(['metadata' => $billMeta]);
                    $this->line("  [OK] NOTIF-WRG-005 dikirim ke {$taxpayer->name} ({$taxpayer->phone}) untuk H-{$days}");
                }
            }
        }

        $this->info("Siklus pengingat jatuh tempo selesai.");
    }
}
