<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pbb_mutations', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 32)->unique()->comment('Nomor Tiket Permohonan Mutasi');
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID User Akun Pemohon');
            $table->string('mutation_type', 32)->index()->comment('SPLIT_KAVLING, RECTIFICATION, TRANSFER_OWNERSHIP, AMALGAMATION, CANCELLATION');
            $table->string('applicant_nik', 16);
            $table->string('applicant_name');
            $table->string('applicant_phone', 20)->nullable();
            $table->text('applicant_address')->nullable();
            $table->string('parent_nop', 25)->nullable()->index()->comment('NOP Target / Induk');
            $table->string('status', 32)->default('PENDING')->index()->comment('PENDING, SURVEY, VERIFIED_L1, PRE_APPROVED_L2, APPROVED, REJECTED');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Skenario 1 (Split Kavling & Piutang Proporsional)
            $table->decimal('parent_land_area', 10, 2)->nullable();
            $table->decimal('total_parent_debt', 15, 2)->nullable()->default(0);
            $table->decimal('proportional_debt_paid', 15, 2)->nullable()->default(0);

            // Skenario 4 (Pembetulan SPPT)
            $table->json('correction_data')->nullable()->comment('Data koreksi nama, alamat, luas bumi/bangunan');

            // Skenario 5 (Balik Nama / Peralihan Hak)
            $table->string('previous_taxpayer_nik', 16)->nullable();
            $table->string('previous_taxpayer_name')->nullable();
            $table->string('new_taxpayer_nik', 16)->nullable();
            $table->string('new_taxpayer_name')->nullable();
            $table->string('bphtb_ntpd_ref', 64)->nullable()->index()->comment('NTPD Bukti Bayar BPHTB');

            // Skenario 6 (Penggabungan / Amalgamation)
            $table->string('primary_target_nop', 25)->nullable()->index();
            $table->json('merged_source_nops_json')->nullable()->comment('Array daftar NOP yang digabung');

            // Skenario 7 (Pembatalan / NOP Ganda)
            $table->string('duplicate_nop_target', 25)->nullable()->index();
            $table->string('cancellation_reason_code', 32)->nullable()->comment('DUPLICATE, DESTROYED_LAND, PUBLIC_FACILITY');

            // Dokumen & Bukti Pendukung
            $table->json('attachment_files')->nullable()->comment('File KTP, Sertifikat, Bukti Hak, dsb');

            // Survei Lapangan & 3-Tier Governance Matrix
            $table->text('survey_notes')->nullable();
            $table->string('survey_photo_path')->nullable();
            $table->json('survey_metadata')->nullable();
            $table->unsignedBigInteger('assigned_officer_id')->nullable();
            $table->unsignedBigInteger('verified_l1_by')->nullable();
            $table->timestamp('verified_l1_at')->nullable();
            $table->unsignedBigInteger('verified_l2_by')->nullable();
            $table->timestamp('verified_l2_at')->nullable();
            $table->unsignedBigInteger('approved_l3_by')->nullable();
            $table->timestamp('approved_l3_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('pbb_split_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbb_mutation_id')->constrained('pbb_mutations')->cascadeOnDelete();
            $table->integer('item_index')->default(1);
            $table->string('target_nik', 16);
            $table->string('target_name');
            $table->decimal('land_area_m2', 10, 2);
            $table->decimal('building_area_m2', 10, 2)->default(0);
            $table->decimal('proportional_debt_amount', 15, 2)->default(0);
            $table->string('generated_nop', 25)->nullable()->index();
            $table->string('status', 32)->default('PENDING');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbb_split_items');
        Schema::dropIfExists('pbb_mutations');
    }
};
