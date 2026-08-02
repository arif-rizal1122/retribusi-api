<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel pengajuan perubahan data unit pajak (Change Request) - M19.
     * Wajib pajak mengajukan perubahan field tertentu (nama/alamat/luas/klasifikasi),
     * admin Bapenda me-review lalu approve (field diterapkan ke tax_objects + audit trail)
     * atau reject dengan catatan.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tax_object_change_requests')) {
            Schema::create('tax_object_change_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tax_object_id')->constrained('tax_objects')->cascadeOnDelete();
                $table->foreignId('taxpayer_id')->constrained('taxpayers')->cascadeOnDelete();
                $table->json('field_changes')->comment('Diff field lama vs baru, contoh: {"name":{"old":"...","new":"..."}}');
                $table->json('documents')->nullable()->comment('Array path/URL dokumen pendukung');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete()->comment('Admin Bapenda yang me-review');
                $table->text('review_notes')->nullable()->comment('Catatan review / alasan penolakan');
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['tax_object_id', 'status']);
                $table->index(['taxpayer_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_object_change_requests');
    }
};
