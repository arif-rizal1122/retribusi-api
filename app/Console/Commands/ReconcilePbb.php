<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * M20-1455: Rekonsiliasi harian pembayaran PBB M-PAD vs SIMPBB.
 *
 * Membandingkan total PaymentRequest yang lunas (status paid/settled/success)
 * terhadap TransactionPbb berstatus success pada tanggal yang sama,
 * mendeteksi selisih nominal & jumlah transaksi, lalu mencatatnya
 * ke file log + tabel pbb_reconciliation_logs.
 *
 * Usage:
 *   php artisan pbb:reconcile                       # default: kemarin
 *   php artisan pbb:reconcile --date=2026-08-10     # tanggal tertentu
 *   php artisan pbb:reconcile --force               # tulis log meski selisih 0
 */
class ReconcilePbb extends Command
{
    protected $signature = 'pbb:reconcile
                            {--date= : Tanggal rekonsiliasi (format Y-m-d). Default: kemarin}
                            {--force : Tulis log walaupun tidak ada selisih}';

    protected $description = 'Rekonsiliasi harian PBB: total PaymentRequest lunas vs TransactionPbb success (deteksi selisih)';

    /** Status PaymentRequest yang dianggap lunas */
    protected const PAID_STATUSES = ['paid', 'settled', 'success'];

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $dateStr = $date->format('Y-m-d');
        $this->info("🔍 Rekonsiliasi PBB untuk tanggal: {$dateStr}");

        // ── Sisi M-PAD: PaymentRequest lunas ──────────────────────────────
        $pr = DB::table('payment_requests')
            ->whereIn('status', self::PAID_STATUSES)
            ->whereDate('paid_at', $dateStr)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount_snapshot), 0) as total_amount')
            ->first();

        // ── Sisi SIMPBB: TransactionPbb success ───────────────────────────
        $tp = DB::table('transaction_pbb')
            ->where('payment_status', 'success')
            ->whereDate('created_at', $dateStr)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(total_bayar), 0) as total_amount')
            ->first();

        $prAmount = (float) $pr->total_amount;
        $tpAmount = (float) $tp->total_amount;
        $prCount = (int) $pr->total_count;
        $tpCount = (int) $tp->total_count;
        $diffAmount = round($prAmount - $tpAmount, 2);
        $diffCount = $prCount - $tpCount;
        $hasDiff = $diffAmount != 0 || $diffCount != 0;

        // ── Tampilkan ringkasan ───────────────────────────────────────────
        $this->table(
            ['Sumber', 'Jumlah Transaksi', 'Total Nominal'],
            [
                ['PaymentRequest (lunas)', $prCount, number_format($prAmount, 2)],
                ['TransactionPbb (success)', $tpCount, number_format($tpAmount, 2)],
                ['Selisih', $diffCount, number_format($diffAmount, 2)],
            ]
        );

        if (!$hasDiff && !$this->option('force')) {
            $this->comment("✅ Tidak ada selisih untuk {$dateStr}. Lewati penulisan log (gunakan --force untuk tetap mencatat).");
            return 0;
        }

        // ── Simpan ke file log ────────────────────────────────────────────
        $logDir = storage_path('logs/reconciliation');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/pbb-reconciliation.log';
        $entry = [
            'checked_at' => now()->toDateTimeString(),
            'date' => $dateStr,
            'payment_request' => ['count' => $prCount, 'amount' => $prAmount],
            'transaction_pbb' => ['count' => $tpCount, 'amount' => $tpAmount],
            'difference' => ['count' => $diffCount, 'amount' => $diffAmount],
            'has_difference' => $hasDiff,
        ];
        file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);

        // ── Simpan ke tabel (buat jika belum ada) ─────────────────────────
        $this->ensureTable();
        DB::table('pbb_reconciliation_logs')->insert([
            'reconcile_date' => $dateStr,
            'pr_count' => $prCount,
            'pr_amount' => $prAmount,
            'tpbb_count' => $tpCount,
            'tpbb_amount' => $tpAmount,
            'diff_count' => $diffCount,
            'diff_amount' => $diffAmount,
            'has_difference' => $hasDiff,
            'checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("📝 Log tersimpan: {$logFile} + tabel pbb_reconciliation_logs");

        if ($hasDiff) {
            Log::warning("PBB Reconciliation mismatch {$dateStr}", $entry);
            $this->error("⚠️ Terdeteksi selisih {$dateStr}: nominal {$diffAmount}, transaksi {$diffCount}");
            return 1;
        }

        $this->info("✅ Rekonsiliasi {$dateStr} OK (selisih 0).");
        return 0;
    }

    protected function ensureTable(): void
    {
        if (Schema::hasTable('pbb_reconciliation_logs')) {
            return;
        }
        Schema::create('pbb_reconciliation_logs', function ($table) {
            $table->id();
            $table->date('reconcile_date')->unique();
            $table->unsignedInteger('pr_count')->default(0);
            $table->decimal('pr_amount', 15, 2)->default(0);
            $table->unsignedInteger('tpbb_count')->default(0);
            $table->decimal('tpbb_amount', 15, 2)->default(0);
            $table->integer('diff_count')->default(0);
            $table->decimal('diff_amount', 15, 2)->default(0);
            $table->boolean('has_difference')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }
}
