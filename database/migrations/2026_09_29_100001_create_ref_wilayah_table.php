<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master kode wilayah Kota Baubau untuk kodifikasi NOPD/NORD.
 * kode = 2 digit kode kecamatan + 2 digit kode kelurahan (sentinel 00 = belum dipecah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_wilayah', function (Blueprint $table) {
            $table->id();
            $table->char('kode', 4)->unique();
            $table->char('kode_kecamatan', 2);
            $table->char('kode_kelurahan', 2)->default('00');
            $table->string('kecamatan', 100);
            $table->string('kelurahan', 100)->nullable();
            // Kode asli SW_PATDA (kec 7 digit, kel 10 digit) untuk join ETL data lama
            $table->string('legacy_kode', 16)->nullable()->index();
            $table->string('legacy_kode_kecamatan', 16)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('sumber', 100)->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index('kode_kecamatan');
            $table->index('kecamatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_wilayah');
    }
};
