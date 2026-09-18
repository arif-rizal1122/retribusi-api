<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest checkout wisatawan asing tidak memiliki taxpayer_id (tamu tanpa akun).
 *
 * Sebelumnya kolom `taxpayer_id` NOT NULL sehingga guest checkout gagal (error 500).
 * Migrasi ini membuatnya nullable SECARA RESMI + menambah kolom penanda tamu
 * (`is_guest_checkout`) agar tagihan tamu terbedakan jelas dari tagihan wajib pajak.
 *
 * Pemulihan (down): hanya boleh dijalankan setelah dipastikan tidak ada baris
 * guest checkout yang tersisa — kalau ada, rollback akan gagal (dengan sengaja),
 * supaya data tamu tidak diam-diam kehilangan identitasnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->boolean('is_guest_checkout')->default(false)->after('passport_number');
            $table->string('guest_name')->nullable()->after('is_guest_checkout');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_phone')->nullable()->after('guest_email');
        });

        // taxpayer_id boleh NULL hanya untuk tamu (dijaga di lapisan aplikasi).
        DB::statement('ALTER TABLE bills MODIFY taxpayer_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        $guestCount = DB::table('bills')->where('is_guest_checkout', true)->count();

        if ($guestCount > 0) {
            throw new RuntimeException(
                'Rollback dibatalkan: masih ada '.$guestCount.' tagihan guest checkout. '.
                'Pindahkan atau hapus data tamu terlebih dahulu.'
            );
        }

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['is_guest_checkout', 'guest_name', 'guest_email', 'guest_phone']);
        });

        DB::statement('ALTER TABLE bills MODIFY taxpayer_id BIGINT UNSIGNED NOT NULL');
    }
};
