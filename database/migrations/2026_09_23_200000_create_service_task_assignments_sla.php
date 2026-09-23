<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 2319 — SLA Tracker Penugasan (6 tahapan + eskalasi).
 *
 * TEMUAN AWAL: nol implementasi. Tidak ada tabel SLA, tidak ada kolom
 * sla_deadline / escalation_alert_at di manapun.
 *
 * Tabel `petugas_tasks` (71 baris) TIDAK diubah skemanya — dipakai apa adanya.
 * Tabel baru ini menampung pelacakan SLA lintas tahapan layanan, karena satu
 * permohonan dapat melewati beberapa tahap (verifikasi -> survei -> approval)
 * yang PIC-nya berbeda, sehingga tidak bisa ditampung satu kolom di petugas_tasks.
 *
 * Matriks SLA (docs/00_kebijakan_strategis/TUPOKSI_KABID_KASUBID_WILAYAH_I_II.md):
 *   1. Verifikasi Permohonan Baru    PIC Kasubid Pendataan I/II   <= 12 jam
 *   2. Survei & Pengukuran Fisik     PIC Petugas Lapangan         <= 24 jam
 *   3. Otorisasi Draf SKPD/SKRD      PIC Kepala Bidang I/II       <=  4 jam
 *   4. Penerbitan e-SSPD             PIC Auto-TTE Hook            <=  5 detik
 *   5. Penerbitan STPD Denda         PIC Kasubid Penagihan II     tgl 16-17
 *   6. Closing Rekonsiliasi Kasda    PIC Kasubid Penagihan I/II   harian 17.00
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_task_assignments')) {
            return;
        }

        Schema::create('service_task_assignments', function (Blueprint $table) {
            $table->id();

            // Referensi polimorfik ke dokumen sumber (permohonan, bill, notice).
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Tahapan & pembagian wilayah.
            $table->string('stage', 40);
            $table->string('tax_domain', 20)->nullable();
            $table->string('tax_sector_code', 40)->nullable();

            // Penanggung jawab.
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();

            // Status & alasan penolakan.
            $table->string('status', 20)->default('PENDING');
            $table->text('rejection_reason')->nullable();

            // SLA.
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('escalation_alert_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Pemenuhan SLA (diisi saat selesai).
            $table->boolean('sla_met')->nullable();
            $table->integer('duration_minutes')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stage', 'status'], 'sta_stage_status_idx');
            $table->index(['assigned_to_user_id', 'status'], 'sta_assignee_status_idx');
            $table->index(['sla_deadline'], 'sta_deadline_idx');
            $table->index(['reference_type', 'reference_id'], 'sta_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_task_assignments');
    }
};
