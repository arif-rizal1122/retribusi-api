<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ptsp_data_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->string('nik', 16)->nullable();
            $table->text('alamat_usaha')->nullable();
            $table->string('jenis_usaha', 255)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->enum('status', ['menunggu', 'proses_ptsp', 'nib_terbit'])->default('menunggu');
            $table->string('nib', 16)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptsp_data_candidates');
    }
};
