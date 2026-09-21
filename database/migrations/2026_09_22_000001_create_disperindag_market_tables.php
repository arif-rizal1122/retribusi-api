<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Digitalisasi Pasar M-PAD — Dinas Perdagangan, Perindustrian, Koperasi, dan UKM (Disperindag/Perdaginkop).
 * Sesuai Perda Kota Baubau No. 1 Tahun 2024 (RET-PSR & JU-TKU) dan Data Rekapan Pasar Wameo & Pasar Karya Baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Pasar (Level 1 Hierarki Spasial M-PAD)
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opd_id')->default(3);
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('type', 50)->default('pasar_tradisional'); // pasar_tradisional, pasar_lingkungan
            $table->text('address')->nullable();
            $table->string('district', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['opd_id', 'is_active']);
        });

        // 2. Unit Kios & Los Pasar (Level 3 Hierarki Spasial M-PAD)
        Schema::create('market_stalls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('zone_id')->nullable(); // Level 2: Gedung / Blok di tabel zones
            $table->unsignedBigInteger('tax_object_id')->nullable(); // Universal M-PAD TaxObject
            $table->unsignedBigInteger('taxpayer_id')->nullable(); // Wajib Retribusi / Pedagang
            $table->string('market_name', 255);
            $table->string('building_name', 255);
            $table->enum('stall_type', ['kios', 'los', 'pelataran'])->default('los');
            $table->string('stall_number', 50);
            $table->string('full_code', 100)->index(); // Contoh: WAM-BLOK-A-01, WAM-LOS-A-01
            $table->string('merchant_name', 255)->nullable();
            $table->decimal('tariff_amount', 15, 2)->default(0);
            $table->enum('billing_cycle', ['daily', 'monthly', 'yearly'])->default('monthly');
            $table->enum('status', ['aktif', 'tersegel', 'kosong'])->default('aktif');
            $table->text('notes')->nullable(); // Catatan status (misal: tersegel, blm byr, unit rusak)
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['market_id', 'building_name']);
            $table->index(['stall_type', 'status']);
            $table->foreign('market_id')->references('id')->on('markets')->onDelete('cascade');
        });

        // 3. Penugasan Petugas Penagih Retribusi (Officer-Zone Binding)
        Schema::create('market_officer_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->string('market_name', 255);
            $table->string('officer_name', 255);
            $table->unsignedBigInteger('user_id')->nullable(); // Terhubung ke tabel users jika sudah terdaftar
            $table->json('assigned_areas'); // Array gedung/blok binaan
            $table->boolean('collects_daily_pkl')->default(false); // Penarikan karcis harian pedagang subuh
            $table->string('signed_by', 255)->nullable();
            $table->date('decree_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['market_id', 'officer_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_officer_assignments');
        Schema::dropIfExists('market_stalls');
        Schema::dropIfExists('markets');
    }
};
