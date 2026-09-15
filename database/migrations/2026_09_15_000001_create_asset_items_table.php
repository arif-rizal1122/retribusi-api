<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data aset daerah (alat berat / kendaraan / sedot kakus) milik OPD PUPR.
     * Dasar: Perda 1/2024 - Pemanfaatan Kekayaan Daerah (Kategori Jasa Usaha).
     */
    public function up(): void
    {
        Schema::create('asset_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('category')->nullable(); // alat-berat | kendaraan | sedot-kakus
            $table->string('merk_type')->nullable();
            $table->text('spesifikasi')->nullable();
            $table->string('kondisi')->default('Baik'); // Baik | Rusak Ringan | Rusak Berat
            $table->string('status_operasional')->default('Tersedia'); // Tersedia | Dipakai | Rusak | Mutasi
            $table->string('lokasi')->nullable();
            $table->decimal('tarif', 15, 2)->default(0);
            $table->string('satuan_tarif')->default('per jam'); // per jam | per hari | per rit
            $table->boolean('wajib_tronton')->default(false);
            $table->string('image_url')->nullable();
            $table->string('pic')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();

            $table->index(['opd_id', 'status_operasional']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_items');
    }
};