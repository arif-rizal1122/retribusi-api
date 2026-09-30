<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. parking_spot_applications (Pendaftaran Mandiri & Hasil Survei Dishub)
        if (!Schema::hasTable('parking_spot_applications')) {
            Schema::create('parking_spot_applications', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->unsignedBigInteger('user_id')->comment('FK users — Calon jukir mitra yang mengajukan');
                $table->ulid('target_zone_id')->nullable()->comment('FK parking_zones usulan kawasan');
                $table->string('proposed_spot_name', 100)->comment('Nama ruas / lokasi yang diajukan');
                $table->geometry('proposed_polygon', 'polygon', 4326)->nullable()->comment('Usulan poligon awal dari jukir');
                $table->json('proposed_polygon_geojson')->nullable()->comment('GeoJSON usulan jukir untuk Leaflet');
                $table->geometry('verified_polygon', 'polygon', 4326)->nullable()->comment('Poligon resmi hasil survei fisik Dishub');
                $table->json('verified_polygon_geojson')->nullable()->comment('GeoJSON verifikasi resmi');
                $table->unsignedBigInteger('surveyor_id')->nullable()->comment('FK users — Petugas survei Dishub');
                $table->json('survey_photos')->nullable()->comment('Array URL foto geotagging batas parkir');
                $table->integer('capacity_r2')->nullable()->comment('Hasil ukur kapasitas R2');
                $table->integer('capacity_r4')->nullable()->comment('Hasil ukur kapasitas R4');
                $table->enum('status', ['SUBMITTED', 'SURVEY_ASSIGNED', 'SURVEYED', 'APPROVED', 'REJECTED'])
                      ->default('SUBMITTED');
                $table->text('rejection_reason')->nullable();
                $table->integer('geofence_tolerance_meters')->default(25);
                $table->ulid('created_spot_id')->nullable()->comment('Ref ID ke parking_spots jika disetujui');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('surveyor_id')->references('id')->on('users')->nullOnDelete();
                $table->index('status');
                $table->index('created_at');
            });
        }

        // 2. kalkulator_simulations (Perekaman Jejak Simulasi Warga di /kalkulator-bisnis/jukir)
        if (!Schema::hasTable('kalkulator_simulations')) {
            Schema::create('kalkulator_simulations', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable()->comment('FK users jika sudah login');
                $table->string('simulation_type', 50)->default('JUKIR_REVENUE');
                $table->string('target_zone_id', 36)->nullable();
                $table->json('input_payload')->comment('{est_r2, est_r4, work_days, zone_id}');
                $table->json('result_payload')->comment('{gross_rev, jukir_net, korlap_share, bpjs_fee, kasda_share}');
                $table->boolean('converted_to_registration')->default(false)->comment('Apakah klik tombol daftar jadi mitra');
                $table->ulid('application_id')->nullable()->comment('Ref ke parking_spot_applications jika mendaftar');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('simulation_type');
                $table->index('converted_to_registration');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kalkulator_simulations');
        Schema::dropIfExists('parking_spot_applications');
    }
};
