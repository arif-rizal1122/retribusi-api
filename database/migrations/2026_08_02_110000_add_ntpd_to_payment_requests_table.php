<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom ntpd pada payment_requests.
     *
     * NTPD (Nomor Transaksi Penerimaan Daerah) diterbitkan oleh bank persepsi
     * saat pembayaran sukses (via SNAP BRI / VA / QRIS). Disimpan di sini agar
     * setiap PaymentRequest memiliki jejak NTPD untuk rekonsiliasi & bukti bayar.
     */
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('ntpd', 64)->nullable()->after('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('ntpd');
        });
    }
};
