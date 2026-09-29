<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry nomor objek NOPD/NORD 14 digit.
 * Menyimpan nomor baku yang terbit, alias kode lama (untuk pencarian petugas loket),
 * dan status klaim objek usaha lama oleh warga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('object_number_registry', function (Blueprint $table) {
            $table->id();

            // Nomor yang berlaku: hasil pasang nomor lama (existing) ATAU generate baru (generated).
            // Dilebarkan ke 30 karakter agar NOP lama (13/18 digit atau bertitik) bisa dipasang apa adanya.
            $table->string('nomor', 30)->unique();
            $table->string('nomor_sumber', 12)->default('generated'); // existing | generated
            $table->char('nomor_baku', 14)->nullable();               // terisi hanya bila bentuk 14 digit baku 7472

            $table->string('jenis', 4)->nullable();                    // NOPD | NORD
            $table->char('sector_code', 2)->nullable();
            $table->char('kode_wilayah', 4)->nullable();
            $table->unsignedSmallInteger('urut')->nullable();

            $table->unsignedBigInteger('object_number_registry_id_parent')->nullable();
            $table->unsignedBigInteger('tax_object_id')->nullable();
            $table->unsignedBigInteger('taxpayer_id')->nullable();
            $table->unsignedBigInteger('opd_id')->nullable();

            // Alias kode historis (NOP lama / NPWPD lama) -> pencarian di loket
            $table->string('legacy_code', 60)->nullable();
            $table->string('legacy_npwpd', 30)->nullable();
            $table->string('legacy_source', 50)->nullable();

            $table->string('claim_status', 20)->default('UNCLAIMED');
            $table->unsignedBigInteger('claimed_by_user_id')->nullable();
            $table->timestamp('claimed_at')->nullable();

            $table->string('status', 20)->default('aktif'); // aktif, retired
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['sector_code', 'kode_wilayah', 'urut']);
            $table->index('legacy_code');
            $table->index(['jenis', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_number_registry');
    }
};
