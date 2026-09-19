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
        // 1. Master Kategori Tarif Sampah DLH (Perda No. 1 Tahun 2024 Kota Baubau)
        Schema::create('dlh_sampah_tarif_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tarif', 30)->unique();
            $table->string('nama_kategori', 150);
            $table->enum('kelompok', ['rumah_tangga', 'bisnis_niaga', 'sosial_khusus']);
            $table->decimal('tarif_bulanan', 18, 2)->default(0.00);
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Master Akun Objek Persampahan Property-Centric
        Schema::create('dlh_sampah_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_object_id')->constrained('tax_objects')->cascadeOnDelete();
            $table->string('kode_objek', 50)->unique();
            $table->foreignId('tarif_kategori_id')->constrained('dlh_sampah_tarif_kategori');
            $table->string('penanggung_jawab_nama', 150);
            $table->string('penanggung_jawab_telepon', 25)->nullable();
            $table->string('penanggung_jawab_nik', 20)->nullable();
            $table->text('alamat_lengkap');
            $table->string('kelurahan', 100);
            $table->string('kecamatan', 100);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->unsignedInteger('geofence_radius_meters')->default(30);
            $table->enum('status', ['active', 'inactive', 'exempted'])->default('active');
            $table->timestamps();

            $table->index('kode_objek');
            $table->index('kelurahan');
        });

        // 3. Tagihan Bulanan (e-SKRD Persampahan)
        Schema::create('dlh_sampah_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('dlh_sampah_accounts')->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->string('periode_bulan', 7); // Format: YYYY-MM
            $table->decimal('nominal_pokok', 18, 2);
            $table->decimal('nominal_denda', 18, 2)->default(0.00);
            $table->decimal('total_tagihan', 18, 2);
            $table->enum('status_bayar', ['unpaid', 'paid', 'cancelled'])->default('unpaid');
            $table->date('tgl_jatuh_tempo');
            $table->dateTime('tgl_bayar')->nullable();
            $table->string('metode_bayar', 50)->nullable();
            $table->unsignedBigInteger('collector_user_id')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'periode_bulan'], 'uq_acc_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dlh_sampah_billings');
        Schema::dropIfExists('dlh_sampah_accounts');
        Schema::dropIfExists('dlh_sampah_tarif_kategori');
    }
};
