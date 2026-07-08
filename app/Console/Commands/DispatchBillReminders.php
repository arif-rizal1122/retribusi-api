<?php

namespace App\Console\Commands;

use App\Jobs\SendBillReminder;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchBillReminders extends Command
{
    protected $signature = 'auto-deduct:remind-bills
        {--days=7 : Kirim reminder untuk bill yang jatuh tempo dalam X hari ke depan}
        {--overdue : Juga kirim untuk bill yang sudah lewat jatuh tempo}
        {--dry-run : Hitung jumlah tanpa kirim}';

    protected $description = 'Kirim notifikasi WA untuk tagihan yang akan jatuh tempo';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $now = Carbon::now();
        $targetEnd = $now->copy()->addDays($days);

        $this->info("🔍 Mencari tagihan jatuh tempo antara {$now->format('d M Y')} - {$targetEnd->format('d M Y')}...");

        $bills = Bill::where('status', 'pending')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now, $targetEnd])
            ->whereHas('taxpayer', function ($q) {
                $q->whereNotNull('phone');
            })
            ->with('taxpayer')
            ->get();

        $this->line("  -> {$bills->count()} tagihan ditemukan");

        if ($this->option('overdue')) {
            $overdueBills = Bill::where('status', 'pending')
                ->where('due_date', '<', $now)
                ->whereHas('taxpayer', function ($q) {
                    $q->whereNotNull('phone');
                })
                ->count();

            $this->line("  -> {$overdueBills} tagihan overdue juga akan dikirimi reminder");
        }

        if ($dryRun) {
            $this->warn("  [DRY RUN] Tidak ada notifikasi yang dikirim");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($bills->count());
        $bar->start();

        $sent = 0;
        foreach ($bills as $bill) {
            SendBillReminder::dispatch($bill->id);
            $sent++;
            $bar->advance();
        }

        if ($this->option('overdue')) {
            Bill::where('status', 'pending')
                ->where('due_date', '<', $now)
                ->whereHas('taxpayer', function ($q) {
                    $q->whereNotNull('phone');
                })
                ->chunk(100, function ($overdueBills) use (&$sent, $bar) {
                    foreach ($overdueBills as $bill) {
                        SendBillReminder::dispatch($bill->id);
                        $sent++;
                    }
                });
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ {$sent} notifikasi tagihan telah diantrekan ke WA Gateway");

        return Command::SUCCESS;
    }
}
