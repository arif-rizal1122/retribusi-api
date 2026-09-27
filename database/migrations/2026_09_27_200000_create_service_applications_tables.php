<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Permohonan Layanan generik (M-PAD).
 *
 * Generik & future-proof — melayani Reklame (188), PBG (193), Air Tanah (197),
 * Izin TKA (194), dan layanan lain yang menyusul.
 *
 * ⚠️ TIDAK menyentuh tax_object_change_requests (khusus mutasi objek PBB-P2).
 * ⚠️ Penugasan petugas & SLA tahap memakai service_task_assignments yang SUDAH ADA
 *    (SlaTrackerService) — tabel ini hanya menyimpan SLA agregat untuk tampilan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 50)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('service_code', 60)->index();
            $table->string('service_name', 150)->nullable();
            $table->json('payload');
            $table->enum('status', [
                'submitted',
                'ptsp_review',
                'field_assigned',
                'field_checked',
                'bapenda_review',
                'billing_issued',
                'paid',
                'permit_issued',
                'revision_needed',
                'rejected',
            ])->default('submitted')->index();

            // Kontak pemohon (snapshot saat submit — nomor bisa berubah di profil)
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();

            // Penugasan lapangan (cermin dari service_task_assignments utk tampilan cepat)
            $table->unsignedBigInteger('assigned_officer_id')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('field_checked_at')->nullable();
            $table->text('field_notes')->nullable();

            // SLA agregat (sumber kebenaran per-tahap = service_task_assignments)
            $table->timestamp('sla_deadline')->nullable()->index();
            $table->boolean('is_sla_breached')->default(false)->index();
            $table->timestamp('sla_breached_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Hasil akhir
            $table->string('permit_no', 80)->nullable();
            $table->unsignedBigInteger('billing_id')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_code', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('service_application_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->index();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_role', 60)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'created_at']);
        });

        // Kalender hari libur nasional/cuti bersama — untuk SLA hari kerja
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name', 150);
            $table->boolean('is_joint_leave')->default(false); // cuti bersama
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('service_application_logs');
        Schema::dropIfExists('service_applications');
    }
};
