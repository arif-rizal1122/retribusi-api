<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. KATEGORI ASET (Tanah, Bangunan, Alat Berat, Kendaraan, Mesin, Peralatan)
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('asset_categories')->onDelete('set null');
        });

        // 2. UNIT ASET (item spesifik: Gedung Pancasila, Excavator PC200-8M0, Mobil Sedot Tinja DT9062)
        Schema::create('asset_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable()->comment('Kode aset / nopol / inventaris');
            $table->string('merk_type')->nullable();
            $table->string('spesifikasi')->nullable();
            $table->enum('kondisi', ['Baik', 'Terbatas', 'Tidak Operasional', 'Dialihfungsikan'])->default('Baik');
            $table->enum('status_operasional', ['Operasional', 'Terbatas', 'Tidak Operasional', 'Dialihfungsikan'])->default('Operasional');
            $table->string('lokasi')->nullable();
            $table->string('pic')->nullable()->comment('Penanggung jawab unit');
            $table->decimal('tarif_default', 15, 2)->default(0);
            $table->enum('satuan_tarif', ['per jam', 'per hari', 'per km', 'per bulan', 'per tahun', 'per kegiatan'])->default('per hari');
            $table->boolean('is_demo')->default(false)->comment('Data placeholder, hapus saat data riil masuk');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('asset_categories')->onDelete('cascade');
            $table->foreign('opd_id')->references('id')->on('opds')->onDelete('set null');
            $table->index(['category_id', 'is_active', 'status_operasional']);
        });

        // 3. SEWA / PEMANFAATAN ASET (transaksi)
        Schema::create('asset_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->unique()->comment('Nomor pengajuan sewa');
            $table->unsignedBigInteger('taxpayer_id');
            $table->unsignedBigInteger('asset_item_id');
            $table->unsignedBigInteger('retribution_type_id')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->decimal('lama_sewa', 10, 2)->default(0);
            $table->string('satuan_sewa')->nullable()->comment('per jam / per hari / per km / per bulan / per kegiatan');
            $table->decimal('tarif_per_satuan', 15, 2)->default(0);
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->decimal('dp', 15, 2)->default(0);
            $table->decimal('sisa_pembayaran', 15, 2)->default(0);
            $table->string('metode_pembayaran')->nullable()->comment('Transfer Bank / Tunai / QRIS / e-Wallet');
            $table->enum('status', ['Diajukan', 'Diverifikasi', 'Disetujui', 'Ditolak', 'Selesai', 'Dibatalkan'])->default('Diajukan');

            // Informasi Penggunaan
            $table->text('lokasi_penggunaan')->nullable();
            $table->string('koordinat')->nullable();
            $table->string('jenis_pekerjaan')->nullable();
            $table->string('luas_volume')->nullable();
            $table->string('nama_proyek')->nullable();
            $table->string('nomor_spk')->nullable();
            $table->string('penyelia')->nullable();
            $table->string('hp_penyelia')->nullable();

            // Operator
            $table->string('operator_nama')->nullable();
            $table->string('operator_hp')->nullable();
            $table->string('operator_sim')->nullable();
            $table->string('operator_sertifikat_url')->nullable();

            // Dokumen (JSON array of {name, url})
            $table->json('dokumen')->nullable();

            // Approval & Tracking
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->timestamp('tanggal_approval')->nullable();
            $table->text('catatan_verifikator')->nullable();
            $table->text('catatan_approval')->nullable();
            $table->date('tanggal_pengembalian')->nullable();
            $table->enum('kondisi_pengembalian', ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'])->nullable();
            $table->text('catatan_pengembalian')->nullable();
            $table->decimal('denda', 15, 2)->default(0);

            $table->boolean('is_demo')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('taxpayer_id')->references('id')->on('taxpayers')->onDelete('cascade');
            $table->foreign('asset_item_id')->references('id')->on('asset_items')->onDelete('cascade');
            $table->foreign('retribution_type_id')->references('id')->on('retribution_types')->onDelete('set null');
            $table->index(['taxpayer_id', 'status']);
            $table->index(['asset_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_rentals');
        Schema::dropIfExists('asset_items');
        Schema::dropIfExists('asset_categories');
    }
};
