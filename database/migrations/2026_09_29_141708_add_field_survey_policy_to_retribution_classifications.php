<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan Policy Flag untuk kendali alur verifikasi:
     * - requires_field_survey: Apakah layanan ini wajib survei lapangan sebelum approval
     * - approval_method: Metode persetujuan (field_survey, document_review, automatic)
     * - field_check_params: Daftar parameter yang wajib diukur petugas di lapangan
     *
     * CATATAN PENTING (keputusan Bang Dadan, 30 Sep 2026):
     * default(false) — BUKAN true. Dengan default true, seluruh layanan retribusi
     * yang sudah ada langsung wajib survei lapangan, dan assertFieldSurveyCompleted()
     * akan memblokir SEMUA approval verifikasi di production sampai setiap permohonan
     * punya tugas lapangan selesai + foto bukti. Aktifkan wajib-survei PER LAYANAN
     * lewat data/UI, bukan lewat default skema.
     */
    public function up(): void
    {
        Schema::table('retribution_classifications', function (Blueprint $table) {
            $table->boolean('requires_field_survey')->default(false)->after('is_self_assessment')
                ->comment('Wajib survei lapangan sebelum approval (default: tidak wajib)');
            $table->string('approval_method', 30)->default('document_review')->after('requires_field_survey')
                ->comment('Metode approval: field_survey, document_review, automatic');
            $table->json('field_check_params')->nullable()->after('approval_method')
                ->comment('Daftar parameter wajib cek fisik lapangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retribution_classifications', function (Blueprint $table) {
            $table->dropColumn(['requires_field_survey', 'approval_method', 'field_check_params']);
        });
    }
};
