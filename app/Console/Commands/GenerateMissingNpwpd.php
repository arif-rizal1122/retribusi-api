<?php

namespace App\Console\Commands;

use App\Models\Taxpayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMissingNpwpd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taxpayer:generate-npwpd 
                            {--dry-run : Simulasi generate NPWPD tanpa menyimpan ke database}
                            {--chunk=100 : Jumlah record yang diproses per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate NPWPD resmi otomatis (P.YYYY.XXXXX) bagi Wajib Pajak yang belum memiliki NPWPD';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $year = date('Y');
        $prefix = "P.{$year}.";

        $this->info("=========================================================");
        $this->info("  M-PAD Auto-Generate NPWPD Batch Processor ({$year})");
        $this->info("=========================================================");
        if ($isDryRun) {
            $this->warn("MODE: DRY RUN (Tidak ada perubahan yang disimpan ke DB)");
        }

        // 1. Ambil seluruh ID Wajib Pajak yang belum memiliki NPWPD
        $targetIds = Taxpayer::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereNull('npwpd')
                  ->orWhere('npwpd', '');
            })
            ->orderBy('id')
            ->pluck('id');

        $totalMissing = $targetIds->count();

        if ($totalMissing === 0) {
            $this->info("Semua Wajib Pajak sudah memiliki NPWPD. Tidak ada data yang perlu diproses.");
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$totalMissing} Wajib Pajak tanpa NPWPD.");

        // 2. Hitung sequence tertinggi saat ini untuk tahun berjalan
        $lastSequence = Taxpayer::withoutGlobalScopes()
            ->where('npwpd', 'like', $prefix . '%')
            ->pluck('npwpd')
            ->map(function ($npwpd) use ($prefix) {
                $seq = substr((string) $npwpd, strlen($prefix));
                return ctype_digit($seq) ? (int) $seq : 0;
            })
            ->max() ?? 0;

        $this->info("Nomor urut NPWPD {$prefix} terakhir saat ini: " . str_pad($lastSequence, 5, '0', STR_PAD_LEFT));

        $currentSequence = $lastSequence;
        $updatedCount = 0;
        $nikMap = []; // Cache NIK -> NPWPD to ensure taxpayers with identical NIK share the same NPWPD
        $firstGenerated = null;
        $lastGenerated = null;

        $bar = $this->output->createProgressBar($totalMissing);
        $bar->start();

        // 3. Batch process dengan chunk ID untuk kestabilan update
        foreach ($targetIds->chunk($chunkSize) as $idChunk) {
            $taxpayers = Taxpayer::withoutGlobalScopes()
                ->whereIn('id', $idChunk)
                ->orderBy('id')
                ->get();

            foreach ($taxpayers as $taxpayer) {
                $npwpd = null;

                // Cek apakah NIK sudah dipetakan sebelumnya
                if (!empty($taxpayer->nik)) {
                    if (isset($nikMap[$taxpayer->nik])) {
                        $npwpd = $nikMap[$taxpayer->nik];
                    } else {
                        // Periksa apakah ada record lain dengan NIK ini yang sudah punya NPWPD
                        $existingWithNpwpd = Taxpayer::withoutGlobalScopes()
                            ->where('nik', $taxpayer->nik)
                            ->whereNotNull('npwpd')
                            ->where('npwpd', '!=', '')
                            ->value('npwpd');

                        if ($existingWithNpwpd) {
                            $npwpd = $existingWithNpwpd;
                        }
                    }
                }

                // Jika belum punya NPWPD dari NIK, generate sequence baru
                if (!$npwpd) {
                    $currentSequence++;
                    $npwpd = $prefix . str_pad($currentSequence, 5, '0', STR_PAD_LEFT);
                }

                if (!empty($taxpayer->nik)) {
                    $nikMap[$taxpayer->nik] = $npwpd;
                }

                if (!$firstGenerated) {
                    $firstGenerated = $npwpd;
                }
                $lastGenerated = $npwpd;

                if (!$isDryRun) {
                    DB::table('taxpayers')
                        ->where('id', $taxpayer->id)
                        ->update(['npwpd' => $npwpd]);
                }

                $updatedCount++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=========================================================");
        $this->info("  PROSES SELESAI!");
        $this->info("  Total Wajib Pajak diproses: {$updatedCount}");
        $this->info("  Rentang NPWPD Baru        : {$firstGenerated} s/d {$lastGenerated}");
        $this->info("=========================================================");

        Log::info("Auto-Generate NPWPD Batch Completed", [
            'total_updated' => $updatedCount,
            'dry_run' => $isDryRun,
            'first_npwpd' => $firstGenerated,
            'last_npwpd' => $lastGenerated,
        ]);

        return Command::SUCCESS;
    }
}
