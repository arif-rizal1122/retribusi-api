<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kamus Referensi Cerdas (READ-ONLY) dari data lama 9pajak / SW_PATDA / arsip Drive.
 *
 * PENTING: tabel ini BUKAN tabel transaksi dan TIDAK menyimpan transaksi lama.
 * Fungsinya hanya memperkaya atribut dan memvalidasi keragaman objek pajak/retribusi baru
 * di M-PAD (pendekatan read-only reference dictionary, tanpa impor massal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_legacy_dictionary', function (Blueprint $table) {
            $table->id();

            $table->string('legacy_source', 50);            // 9PAJAK_SW_PATDA, DRIVE_DOCS_MBLB, DATA_PASAR
            $table->string('legacy_code', 60);              // NOP lama / kode lama (unik per sumber)
            $table->string('legacy_npwpd', 30)->nullable(); // NPWPD lama, mis. P10000002087472

            $table->string('owner_nik_or_nib', 32)->nullable();
            $table->string('legal_entity_name')->nullable();      // pemegang izin / badan hukum
            $table->string('commercial_brand_name')->nullable();  // nama gerai / merek komersial

            $table->char('sector_code', 2);                 // 01-09 NOPD, 20-31 NORD
            $table->char('kode_wilayah', 4)->nullable();    // hasil pemetaan ke ref_wilayah
            $table->text('address_text')->nullable();

            // Kapasitas fisik, izin tambang, RKAB, dll. (hotel: kamar; parkir: luas m2; PAT: debit m3; reklame: dimensi)
            $table->json('technical_specs')->nullable();

            $table->string('claim_status', 20)->default('UNCLAIMED'); // UNCLAIMED, CLAIMED, VERIFIED
            $table->unsignedBigInteger('claimed_by_user_id')->nullable();
            $table->unsignedBigInteger('object_number_registry_id')->nullable();
            $table->unsignedBigInteger('taxpayer_id')->nullable();
            $table->timestamp('claimed_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['legacy_source', 'legacy_code']);
            $table->index('legacy_npwpd');
            $table->index('owner_nik_or_nib');
            $table->index('claim_status');
            $table->index(['sector_code', 'kode_wilayah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_legacy_dictionary');
    }
};
