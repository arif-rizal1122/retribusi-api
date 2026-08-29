<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M37-2 — Mapping sektor usaha → OPD pengampu (Dok 143, kolom "OPD Pengampu Utama").
 *
 * 9 sektor kalkulator bisnis dipetakan ke OPD pengampu yang ada di tabel `opds`.
 * Catatan pemetaan (karena nama OPD di Dok 143 tidak semuanya persis dengan data opds):
 *  - "Dinkop UKM Perdagangan" (Dok 143) → tidak ada OPD "Dinas Koperasi/UKM" terdaftar;
 *    dipetakan ke "Dinas Perindustrian dan Perdagangan" (id 3) — cocok by kata kunci "Perdagangan".
 *  - "PUPR/PDAM" (Dok 143) → PDAM tidak terdaftar sebagai OPD; dipetakan ke "Dinas Pekerjaan
 *    Umum dan Penataan Ruang" (id 9).
 *  - "Pariwisata/Kominfo" & "Pariwisata/Satpol PP" (Dok 143) → kedua OPD dimasukkan.
 *  - Bapenda ditandai is_primary=true karena pemungut PBJT (koordinator M-PAD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_opd_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('sector_id');                       // id sektor kalkulator (restoran, warung, ...)
            $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);     // OPD Pengampu Utama
            $table->string('source_note')->nullable();         // catatan asal pemetaan (Dok 143)
            $table->timestamps();

            $table->unique(['sector_id', 'opd_id']);
            $table->index('sector_id');
        });

        // Fondasi M37-2: validasi sektor per unit usaha (nullable — backward compatible).
        Schema::table('business_units', function (Blueprint $table) {
            $table->string('sector_id')->nullable()->after('unit_type');
            $table->index('sector_id');
        });

        $rows = [
            // Dok 143: Bapenda + Dinas Pariwisata
            ['restoran', 5, true, 'Dok 143: Bapenda + Dinas Pariwisata'],
            ['restoran', 16, false, 'Dok 143: Bapenda + Dinas Pariwisata'],
            // Dok 143: Bapenda + Dinkop UKM Perdagangan (→ Disperindag, lihat catatan kelas)
            ['warung', 5, true, 'Dok 143: Bapenda + Dinkop UKM Perdagangan → Disperindag (tidak ada OPD Dinkop UKM)'],
            ['warung', 3, false, 'Dok 143: Bapenda + Dinkop UKM Perdagangan → Disperindag (tidak ada OPD Dinkop UKM)'],
            // Dok 143: Bapenda + Dinas Pariwisata
            ['hotel', 5, true, 'Dok 143: Bapenda + Dinas Pariwisata'],
            ['hotel', 16, false, 'Dok 143: Bapenda + Dinas Pariwisata'],
            // Dok 143: Bapenda + PUPR/PDAM (→ PUPR, PDAM tidak terdaftar)
            ['air_baku', 5, true, 'Dok 143: Bapenda + PUPR/PDAM → PUPR (PDAM tidak terdaftar)'],
            ['air_baku', 9, false, 'Dok 143: Bapenda + PUPR/PDAM → PUPR (PDAM tidak terdaftar)'],
            // Dok 143: Dinkop UKM (→ Disperindag, satu-satunya OPD bidang perdagangan/UKM)
            ['depot_air', 3, true, 'Dok 143: Dinkop UKM → Disperindag (tidak ada OPD Dinkop UKM)'],
            // Dok 143: Bapenda + Dinkop UKM (→ Disperindag)
            ['laundry', 5, true, 'Dok 143: Bapenda + Dinkop UKM → Disperindag (tidak ada OPD Dinkop UKM)'],
            ['laundry', 3, false, 'Dok 143: Bapenda + Dinkop UKM → Disperindag (tidak ada OPD Dinkop UKM)'],
            // Dok 143: Bapenda + Pariwisata/Kominfo (keduanya dimasukkan)
            ['game_center', 5, true, 'Dok 143: Bapenda + Pariwisata/Kominfo'],
            ['game_center', 16, false, 'Dok 143: Bapenda + Pariwisata/Kominfo'],
            ['game_center', 6, false, 'Dok 143: Bapenda + Pariwisata/Kominfo'],
            // Dok 143: Bapenda + Pariwisata
            ['bilyard', 5, true, 'Dok 143: Bapenda + Pariwisata'],
            ['bilyard', 16, false, 'Dok 143: Bapenda + Pariwisata'],
            // Dok 143: Bapenda + Pariwisata/Satpol PP (keduanya dimasukkan)
            ['hiburan_malam', 5, true, 'Dok 143: Bapenda + Pariwisata/Satpol PP'],
            ['hiburan_malam', 16, false, 'Dok 143: Bapenda + Pariwisata/Satpol PP'],
            ['hiburan_malam', 14, false, 'Dok 143: Bapenda + Pariwisata/Satpol PP'],
        ];

        foreach ($rows as [$sectorId, $opdId, $isPrimary, $note]) {
            if (!Schema::hasTable('opds') || DB::table('opds')->where('id', $opdId)->exists()) {
                DB::table('sector_opd_mappings')->insert([
                    'sector_id'   => $sectorId,
                    'opd_id'      => $opdId,
                    'is_primary'  => $isPrimary,
                    'source_note' => $note,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropIndex(['sector_id']);
            $table->dropColumn('sector_id');
        });

        Schema::dropIfExists('sector_opd_mappings');
    }
};
