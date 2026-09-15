<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kontrak sewa aset (Pemanfaatan Kekayaan Daerah - Sewa Alat Berat/Kendaraan).
     * Dasar: Perda 1/2024 + SOP UPTD Workshop PUPR & form "Sedot Kakus/Mobil Tinja" 2026.
     * Index: asset_rentals (sewa) = sumber inspeksi pra/pasca operasi & penagihan overtime.
     */
    public function up(): void
    {
        Schema::create('asset_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->unique();
            $table->string('nomor_kontrak')->nullable();
            $table->foreignId('taxpayer_id')->constrained('taxpayers')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // pembuat (admin/petugas)
            $table->foreignId('asset_item_id')->constrained('asset_items')->onDelete('cascade');
            $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
            $table->foreignId('retribution_type_id')->nullable()->constrained('retribution_types')->nullOnDelete();
            $table->foreignId('retribution_classification_id')->nullable()->constrained('retribution_classifications')->nullOnDelete();

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('lama_sewa')->default('1'); // tetap string agar fleksibel ("8", "3.5", dst)
            $table->string('satuan_sewa')->default('per jam'); // per jam | per hari
            $table->decimal('tarif_per_satuan', 15, 2)->default(0);
            $table->boolean('include_tronton')->default(false);
            $table->decimal('jarak_tronton_km', 8, 2)->nullable();
            $table->decimal('biaya_tronton', 15, 2)->nullable();
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->decimal('dp', 15, 2)->default(0);
            $table->decimal('sisa_pembayaran', 15, 2)->default(0);
            $table->decimal('tahap_1_amount', 15, 2)->nullable();
            $table->string('tahap_1_status')->nullable();
            $table->decimal('tahap_2_amount', 15, 2)->nullable();
            $table->string('tahap_2_status')->nullable();
            $table->decimal('actual_hours', 10, 2)->nullable();
            $table->string('metode_pembayaran')->default('manual');

            // pending_verification | approved | active | completed | rejected | voided
            $table->string('status')->default('pending_verification');

            $table->string('lokasi_penggunaan')->nullable();
            $table->string('jenis_pekerjaan')->nullable();
            $table->string('nama_proyek')->nullable();
            $table->string('koordinat')->nullable();
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();

            // Survey kelayakan lapangan (UPTD Workshop PUPR)
            $table->boolean('survey_akses_jalan')->nullable();
            $table->boolean('survey_dekat_jalan_raya')->nullable();
            $table->boolean('survey_keamanan')->nullable();
            $table->boolean('survey_lahan_luas')->nullable();
            $table->text('survey_kesimpulan')->nullable();
            $table->string('survey_foto_path')->nullable();
            $table->string('survey_rekomendasi_tronton')->nullable();
            $table->boolean('survey_penjebolan_akses')->nullable();
            $table->text('survey_penjebolan_catatan')->nullable();
            $table->string('survey_rekomendasi_alat')->nullable();
            $table->timestamp('survey_submitted_at')->nullable();

            // Operator / mobilisasi unit
            $table->string('penyelia')->nullable();
            $table->string('hp_penyelia')->nullable();
            $table->string('operator_nama')->nullable();
            $table->string('operator_hp')->nullable();
            $table->string('operator_sim')->nullable();
            $table->string('nomor_spk')->nullable();

            // Form khusus Sedot Kakus / Mobil Tinja (UPTD PUPR Baubau 2026)
            $table->string('nama_konsumen')->nullable();
            $table->string('jenis_bangunan')->nullable();
            $table->unsignedInteger('jumlah_rit')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('pelaksana_armada')->nullable();
            $table->string('foto_lokasi')->nullable();
            $table->string('bukti_pembayaran_manual')->nullable();
            $table->text('keterangan_tambahan')->nullable();

            $table->timestamps();

            $table->index(['opd_id', 'status']);
            $table->index('asset_item_id');
            $table->index('taxpayer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_rentals');
    }
};