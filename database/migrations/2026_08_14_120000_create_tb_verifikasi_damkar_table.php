<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M43-8 (Fase 3) — Tabel verifikasi teknis proteksi kebakaran oleh Damkar.
     * Damkar = verifikator teknis syarat PBG, BUKAN pemungut retribusi.
     */
    public function up(): void
    {
        Schema::create('tb_verifikasi_damkar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_object_id')->nullable()->index();
            $table->string('building_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('notes')->nullable();
            $table->text('rekomendasi_teknis')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_verifikasi_damkar');
    }
};
