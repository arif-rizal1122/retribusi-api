<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom survey lapangan 4 butir, nomor kontrak, dan termin pembayaran ke asset_rentals.
     */
    public function up(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_rentals', 'nomor_kontrak')) {
                $table->string('nomor_kontrak')->nullable()->after('rental_code')->comment('Nomor kontrak resmi contoh 04/SPSR.7/LOADER/V/2026');
            }

            // 4 Butir Indikator Survey Kelayakan Lapangan
            if (!Schema::hasColumn('asset_rentals', 'survey_akses_jalan')) {
                $table->boolean('survey_akses_jalan')->nullable()->after('dokumen')->comment('Akses keluar masuk lebar (alat bisa masuk)');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_dekat_jalan_raya')) {
                $table->boolean('survey_dekat_jalan_raya')->nullable()->after('survey_akses_jalan')->comment('Lokasi dekat dengan jalan raya');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_keamanan')) {
                $table->boolean('survey_keamanan')->nullable()->after('survey_dekat_jalan_raya')->comment('Keamanan alat terjamin dekat perumahan');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_lahan_luas')) {
                $table->boolean('survey_lahan_luas')->nullable()->after('survey_keamanan')->comment('Lahan memadai untuk olah gerak');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_kesimpulan')) {
                $table->text('survey_kesimpulan')->nullable()->after('survey_lahan_luas');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_foto_path')) {
                $table->string('survey_foto_path')->nullable()->after('survey_kesimpulan');
            }
            if (!Schema::hasColumn('asset_rentals', 'surveyor_user_id')) {
                $table->unsignedBigInteger('surveyor_user_id')->nullable()->after('survey_foto_path');
                $table->foreign('surveyor_user_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_submitted_at')) {
                $table->timestamp('survey_submitted_at')->nullable()->after('surveyor_user_id');
            }

            // Skema Pembayaran Multi-Termin (Tahap 1 DP/Sewa Awal & Tahap 2 Pelunasan Jam HM Aktual)
            if (!Schema::hasColumn('asset_rentals', 'tahap_1_amount')) {
                $table->decimal('tahap_1_amount', 15, 2)->nullable()->after('total_biaya');
            }
            if (!Schema::hasColumn('asset_rentals', 'tahap_1_status')) {
                $table->string('tahap_1_status', 20)->default('Pending')->after('tahap_1_amount');
            }
            if (!Schema::hasColumn('asset_rentals', 'tahap_1_paid_at')) {
                $table->timestamp('tahap_1_paid_at')->nullable()->after('tahap_1_status');
            }
            if (!Schema::hasColumn('asset_rentals', 'tahap_2_amount')) {
                $table->decimal('tahap_2_amount', 15, 2)->nullable()->after('tahap_1_paid_at');
            }
            if (!Schema::hasColumn('asset_rentals', 'tahap_2_status')) {
                $table->string('tahap_2_status', 20)->default('Pending')->after('tahap_2_amount');
            }
            if (!Schema::hasColumn('asset_rentals', 'tahap_2_paid_at')) {
                $table->timestamp('tahap_2_paid_at')->nullable()->after('tahap_2_status');
            }
            if (!Schema::hasColumn('asset_rentals', 'actual_hours')) {
                $table->decimal('actual_hours', 8, 2)->nullable()->after('tahap_2_paid_at')->comment('Jam kerja Hour Meter (HM) aktual');
            }
        });
    }

    /**
     * Rollback migrasi.
     */
    public function down(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            if (Schema::hasColumn('asset_rentals', 'surveyor_user_id')) {
                $table->dropForeign(['surveyor_user_id']);
            }
            $table->dropColumn([
                'nomor_kontrak',
                'survey_akses_jalan',
                'survey_dekat_jalan_raya',
                'survey_keamanan',
                'survey_lahan_luas',
                'survey_kesimpulan',
                'survey_foto_path',
                'surveyor_user_id',
                'survey_submitted_at',
                'tahap_1_amount',
                'tahap_1_status',
                'tahap_1_paid_at',
                'tahap_2_amount',
                'tahap_2_status',
                'tahap_2_paid_at',
                'actual_hours',
            ]);
        });
    }
};
