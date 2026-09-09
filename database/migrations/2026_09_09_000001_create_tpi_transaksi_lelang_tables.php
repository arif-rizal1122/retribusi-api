<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengadopsi DDL Spesifik Modul Pelelangan Ikan (PostgreSQL / MySQL Core M-PAD)
     * Rujukan: 01_Dokumen_Arsitektur_Teknis_dan_Roadmap_TPI_MPAD.pdf Bab VII & Dokumen (172/173).
     */
    public function up(): void
    {
        if (!Schema::hasTable('tpi_transaksi_lelang')) {
            Schema::create('tpi_transaksi_lelang', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('kode_transaksi', 50)->unique(); // TPI-WM/YYYYMMDD/XXXX
                $table->string('kode_tpi', 20); // TPI-WM-NEW, TPI-WM-OLD, TPI-BIMA
                $table->timestamp('waktu_transaksi')->useCurrent();

                // Identitas Kapal & Nelayan
                $table->string('kapal_id', 36)->nullable();
                $table->string('kapal_nama', 150)->nullable();
                $table->decimal('kapal_gt', 6, 2)->default(0);
                $table->string('nomor_kusuka', 50)->nullable();

                // Identitas Bakul / Pedagang
                $table->string('bakul_id', 36)->nullable();
                $table->string('bakul_nama', 150)->nullable();
                $table->string('bakul_nik_nib', 30)->nullable();

                // Output Lelang & Nilai Keuangan
                $table->decimal('volume_lelang_kg', 10, 2)->default(0);
                $table->decimal('nilai_transaksi_lelang', 16, 2)->default(0);
                $table->decimal('biaya_fasilitas', 16, 2)->default(0);
                $table->decimal('retribusi_lelang_persen', 16, 2)->default(0); // 1% lelang
                $table->decimal('total_retribusi', 16, 2)->default(0);

                // Pembayaran & NTPD
                $table->string('status_bayar', 20)->default('TERUTANG'); // LUNAS, TERUTANG
                $table->string('metode_bayar', 20)->default('QRIS'); // QRIS, VA_BANK, TUNAI_OFFICER
                $table->string('ntpd', 50)->nullable()->unique();
                $table->string('bank_ref', 64)->nullable();

                // Petugas & Settlement Batch
                $table->string('officer_user_id', 36)->nullable();
                $table->string('cash_settlement_batch_id', 36)->nullable();

                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index(['kode_tpi', 'waktu_transaksi']);
                $table->index(['status_bayar', 'metode_bayar']);
                $table->index('ntpd');
            });
        }

        if (!Schema::hasTable('tpi_rincian_fasilitas')) {
            Schema::create('tpi_rincian_fasilitas', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('transaksi_lelang_id');
                $table->string('jenis_fasilitas', 40); // TAMBAT_LABUH, SEWA_MEJA, SEWA_TIMBANGAN, ES_CURAH, PARKIR_TRUK
                $table->decimal('qty', 8, 2)->default(1);
                $table->string('satuan', 30);
                $table->decimal('tarif_satuan', 16, 2)->default(0);
                $table->decimal('subtotal', 16, 2)->default(0);
                $table->timestamps();

                $table->foreign('transaksi_lelang_id')
                    ->references('id')
                    ->on('tpi_transaksi_lelang')
                    ->onDelete('cascade');

                $table->index('transaksi_lelang_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpi_rincian_fasilitas');
        Schema::dropIfExists('tpi_transaksi_lelang');
    }
};
