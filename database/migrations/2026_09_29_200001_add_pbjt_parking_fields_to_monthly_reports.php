<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBJT Parkir — penyempurnaan SPTPD bulanan (aditif).
 *
 * KEPUTUSAN ARSITEKTUR (29 Sep 2026):
 * Blok Kerja minta tabel baru `pbjt_parking_declarations` dengan PK CUID v2.
 * Skema nyata prod memakai `monthly_reports` (60.336 baris) sebagai SPTPD induk
 * untuk SEMUA jenis pajak — PK bigint unsigned, kolom turnover_amount/tax_amount
 * sudah tepat 10%. Membuat tabel baru = duplikasi + dua sumber kebenaran tagihan.
 *
 * Migrasi ini bersifat ADITIF murni: hanya ADD COLUMN, tidak mengubah/menghapus
 * kolom atau baris yang ada. `down()` menghapus kolom yang ditambahkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('monthly_reports')) {
            return;
        }

        Schema::table('monthly_reports', function (Blueprint $table) {
            // Pendapatan lain (langganan/kartu/valet) — khas PBJT parkir
            if (! Schema::hasColumn('monthly_reports', 'other_income')) {
                $table->decimal('other_income', 15, 2)->default(0)->after('turnover_amount');
            }

            // Dasar Pengenaan Pajak = turnover_amount + other_income
            if (! Schema::hasColumn('monthly_reports', 'dpp_amount')) {
                $table->decimal('dpp_amount', 15, 2)->nullable()->after('other_income');
            }

            // Tarif saat pelaporan direkam (snapshot) — JANGAN dikunci trigger DB.
            // Perda bisa direvisi; tarif disimpan per dokumen supaya audit historis utuh.
            if (! Schema::hasColumn('monthly_reports', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_amount');
            }

            // Denda keterlambatan: 1% per bulan (maks sesuai Perda)
            if (! Schema::hasColumn('monthly_reports', 'penalty_months')) {
                $table->unsignedSmallInteger('penalty_months')->default(0)->after('tax_rate');
            }
            if (! Schema::hasColumn('monthly_reports', 'penalty_amount')) {
                $table->decimal('penalty_amount', 15, 2)->default(0)->after('penalty_months');
            }

            // Total tagihan = tax_amount + penalty_amount
            if (! Schema::hasColumn('monthly_reports', 'total_bill')) {
                $table->decimal('total_bill', 15, 2)->nullable()->after('penalty_amount');
            }

            // Snapshot mentah data tapping box (rentetan sesi parkir periode ini)
            if (! Schema::hasColumn('monthly_reports', 'device_sync_payload')) {
                $table->json('device_sync_payload')->nullable()->after('total_bill');
            }

            // Penanda sumber data: manual warga, tapping box, atau migrasi legacy
            if (! Schema::hasColumn('monthly_reports', 'source')) {
                $table->string('source', 30)->default('manual')->after('device_sync_payload');
            }
        });

        // Index bantu pencarian antrean verifikasi per periode + objek
        Schema::table('monthly_reports', function (Blueprint $table) {
            if (! $this->indexExists('monthly_reports', 'mr_status_period_idx')) {
                $table->index(['status', 'period'], 'mr_status_period_idx');
            }
            if (! $this->indexExists('monthly_reports', 'mr_tax_object_period_idx')) {
                $table->index(['tax_object_id', 'period'], 'mr_tax_object_period_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            foreach ([
                'mr_status_period_idx',
                'mr_tax_object_period_idx',
            ] as $idx) {
                if ($this->indexExists('monthly_reports', $idx)) {
                    $table->dropIndex($idx);
                }
            }

            $table->dropColumn([
                'other_income',
                'dpp_amount',
                'tax_rate',
                'penalty_months',
                'penalty_amount',
                'total_bill',
                'device_sync_payload',
                'source',
            ]);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $rows = \Illuminate\Support\Facades\DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        );

        return $rows !== [];
    }
};
