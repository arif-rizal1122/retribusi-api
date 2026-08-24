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
        Schema::create('pbb_nop_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID Wajib Pajak / User App');
            $table->string('nik', 16)->comment('NIK Pemohon');
            $table->string('name')->comment('Nama Pemohon / Pemilik Aset');
            $table->text('address')->comment('Alamat Objek Pajak');
            $table->decimal('land_area', 10, 2)->comment('Luas Tanah dalam meter persegi');
            $table->decimal('building_area', 10, 2)->nullable()->comment('Luas Bangunan dalam meter persegi (jika ada)');
            $table->string('ktp_file_path')->comment('Path file scan KTP');
            $table->string('akte_file_path')->comment('Path file Akte Tanah / Sertifikat');
            $table->string('imb_file_path')->nullable()->comment('Path file IMB (jika ada bangunan)');
            $table->enum('status', ['PENDING', 'SURVEY', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->text('survey_notes')->nullable()->comment('Catatan hasil peninjauan lapangan Bapenda');
            $table->string('survey_photo_path')->nullable()->comment('Path foto hasil peninjauan lapangan');
            $table->string('nop', 20)->nullable()->comment('NOP yang diterbitkan setelah approve');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbb_nop_applications');
    }
};
