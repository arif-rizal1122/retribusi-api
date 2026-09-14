<?php

namespace App\Console\Commands;

use App\Services\RusunBillingService;
use Illuminate\Console\Command;

class GenerateRusunMonthlyBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rusun:generate-bills {--month= : Periode bulan tagihan (format YYYY-MM, default bulan berjalan)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tagihan e-SKRD bulanan secara otomatis untuk seluruh penghuni aktif Rusunawa Disperkim';

    /**
     * Execute the console command.
     */
    public function handle(RusunBillingService $service): int
    {
        $month = $this->option('month') ?: now()->format('Y-m');

        $this->info("Memulai pembuatan tagihan bulanan Rusunawa untuk periode: {$month}");

        $count = $service->generateMonthlyBills($month);

        $this->info("Selesai! {$count} tagihan e-SKRD berhasil diterbitkan.");

        return Command::SUCCESS;
    }
}
