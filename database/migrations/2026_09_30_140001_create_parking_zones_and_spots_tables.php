<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. parking_zones (Zonasi Kawasan Parkir Dishub Kota Baubau)
        if (!Schema::hasTable('parking_zones')) {
            Schema::create('parking_zones', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->unsignedBigInteger('opd_id')->default(2)->comment('FK opds — 2 = Dishub Kota Baubau');
                $table->string('zone_code', 20)->unique()->comment('Contoh: ZONA-KM-01');
                $table->string('zone_name', 100)->comment('Contoh: Kawasan Pantai Kamali');
                $table->enum('zone_type', ['TEPI_JALAN_UMUM', 'TEMPAT_KHUSUS_PARKIR'])->default('TEPI_JALAN_UMUM');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->geometry('polygon_geom', 'polygon', 4326)->nullable()->comment('Poligon spasial kawasan (EPSG:4326)');
                $table->json('polygon_geojson')->nullable()->comment('GeoJSON poligon batas kawasan untuk Leaflet map');
                $table->decimal('annual_target_revenue', 15, 2)->default(0)->comment('Target PAD tahunan');
                $table->unsignedBigInteger('korlap_id')->nullable()->comment('FK users — Koordinator Lapangan');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('opd_id')->references('id')->on('opds')->cascadeOnDelete();
                $table->foreign('korlap_id')->references('id')->on('users')->nullOnDelete();
                $table->index('zone_code');
                $table->index('zone_type');
                $table->index('is_active');
            });
        }

        // 2. parking_spots (Master Titik / Ruas Parkir Spesifik)
        if (!Schema::hasTable('parking_spots')) {
            Schema::create('parking_spots', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('zone_id');
                $table->string('spot_name', 100)->comment('Contoh: Pantai Kamali Sayap Timur');
                $table->integer('capacity_r2')->default(50)->comment('Daya tampung motor');
                $table->integer('capacity_r4')->default(10)->comment('Daya tampung mobil');
                $table->unsignedBigInteger('assigned_jukir_id')->nullable()->comment('FK users — Jukir resmi bertugas');
                $table->boolean('is_occupied')->default(false);
                $table->geometry('polygon_boundary', 'polygon', 4326)->nullable()->comment('Batas poligon resmi ruas/pelataran');
                $table->json('polygon_geojson')->nullable()->comment('GeoJSON batas untuk Leaflet frontend');
                $table->boolean('is_geofence_locked')->default(true)->comment('Kunci transaksi POS hanya aktif dalam poligon');
                $table->timestamps();

                $table->foreign('zone_id')->references('id')->on('parking_zones')->cascadeOnDelete();
                $table->foreign('assigned_jukir_id')->references('id')->on('users')->nullOnDelete();
                $table->index('is_occupied');
                $table->index('is_geofence_locked');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
        Schema::dropIfExists('parking_zones');
    }
};
