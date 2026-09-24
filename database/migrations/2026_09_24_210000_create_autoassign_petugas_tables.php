<?php
/**
 * Migration: tabel penugasan petugas + audit log + kolom pendukung.
 *
 * REVISI 24 Sep 2026 (keputusan pengelola):
 *  - Satu objek = satu petugas (PIC tunggal)
 *  - Petugas TIDAK bisa menolak tugas (tanpa kolom rejection atas permintaan petugas)
 *  - Tidak ada petugas kompeten -> eskalasi otomatis ke Kabid
 *  - Beban dihitung TOTAL LINTAS LAYANAN
 *  - Prioritas: frekuensi penugasan BULAN INI paling sedikit; seri -> acak
 *  - Backfill = frekuensi jumlah penugasan bulan ini, BUKAN klaim kompetensi
 *  - Jejak penugasan di TABEL AUDIT TERPISAH, bukan di kolom notes
 *
 * SEMUA perubahan bersifat aditif: tidak ada DROP, tidak ada DELETE.
 * 27 baris user_retribution_assignments lama tetap utuh.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1) user_retribution_assignments — master "Kabid tetapkan petugas x layanan"
        //    Kolom baru semuanya nullable/default -> baris lama tetap valid.
        // ------------------------------------------------------------------
        if (Schema::hasTable('user_retribution_assignments')) {
            Schema::table('user_retribution_assignments', function (Blueprint $table) {
                if (! Schema::hasColumn('user_retribution_assignments', 'assigned_by_user_id')) {
                    $table->unsignedBigInteger('assigned_by_user_id')->nullable()->after('retribution_classification_id');
                }
                if (! Schema::hasColumn('user_retribution_assignments', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('assigned_by_user_id');
                }
                if (! Schema::hasColumn('user_retribution_assignments', 'revoked_at')) {
                    $table->timestamp('revoked_at')->nullable()->after('is_active');
                }
            });

            Schema::table('user_retribution_assignments', function (Blueprint $table) {
                $table->index(['user_id', 'is_active'], 'ura_user_active_idx');
                $table->index(['retribution_classification_id', 'is_active'], 'ura_cls_active_idx');
            });
        }

        // ------------------------------------------------------------------
        // 2) service_task_assignment_logs — tabel AUDIT TERPISAH
        //    Mencatat setiap perubahan PIC/status beserta alasannya.
        //    Satu baris audit per peristiwa; tidak pernah dihapus.
        // ------------------------------------------------------------------
        if (! Schema::hasTable('service_task_assignment_logs')) {
            Schema::create('service_task_assignment_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assignment_id');          // -> service_task_assignments.id
                $table->string('event', 40);                          // ASSIGNED | REASSIGNED | CONFIRMED | VERIFIED | FORWARDED | ESCALATED | COMPLETED
                $table->unsignedBigInteger('actor_user_id')->nullable(); // siapa yang melakukan
                $table->unsignedBigInteger('from_user_id')->nullable();  // PIC sebelum (untuk REASSIGNED)
                $table->unsignedBigInteger('to_user_id')->nullable();    // PIC sesudah
                $table->string('stage_from', 40)->nullable();
                $table->string('stage_to', 40)->nullable();
                $table->string('status_from', 20)->nullable();
                $table->string('status_to', 20)->nullable();
                $table->text('reason')->nullable();                   // alasan (wajib untuk REASSIGNED)
                $table->json('meta')->nullable();                     // data tambahan bebas
                $table->timestamp('created_at')->useCurrent();

                $table->index(['assignment_id', 'created_at'], 'stalog_assignment_idx');
                $table->index(['event', 'created_at'], 'stalog_event_idx');
                $table->index(['to_user_id', 'created_at'], 'stalog_to_user_idx');
            });
        }
    }

    public function down(): void
    {
        // Sengaja konservatif: hanya drop tabel audit yang baru dibuat.
        // Kolom di user_retribution_assignments TIDAK di-drop otomatis supaya
        // tidak ada risiko kehilangan data kalau rollback tidak disengaja.
        Schema::dropIfExists('service_task_assignment_logs');
    }
};
