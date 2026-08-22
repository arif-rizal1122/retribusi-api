<?php

namespace App\Support;

use App\Models\Opd;
use App\Models\SectorOpdMapping;
use Illuminate\Support\Collection;

/**
 * M37-2 — Registri sektor usaha kalkulator bisnis ↔ OPD pengampu (Dok 143).
 *
 * Sumber kebenaran mapping ada di tabel `sector_opd_mappings` (diisi migration).
 * Class ini hanya menyediakan metadata sektor (nama & tarif default) + helper query.
 *
 * CATATAN KETERBATASAN (jangan dianggap tarif resmi):
 *  - Tarif default 10% adalah placeholder PBJT sesuai konfigurasi frontend
 *    (localStorage 'mpad_tax_rates'). Belum ada tabel tarif resmi per sektor di backend.
 *  - Ringkasan transaksi per sektor belum tersedia: data transaksi kalkulator masih
 *    tersimpan di localStorage perangkat (lokal-first). `business_units.sector_id`
 *    baru disiapkan sebagai fondasi validasi — belum ada alur isi dari aplikasi.
 */
class SectorOpdMappingRegistry
{
    /** Nama sektor (sama dengan SECTOR_CONFIGS di frontend). */
    public const SECTOR_NAMES = [
        'restoran'     => 'Restoran',
        'warung'       => 'Warung Makan',
        'hotel'        => 'Penginapan / Hotel Melati',
        'air_baku'     => 'Penjual Air Baku',
        'depot_air'    => 'Depot Air Isi Ulang',
        'laundry'      => 'Laundry',
        'game_center'  => 'Game Center',
        'bilyard'      => 'Bilyard Station',
        'hiburan_malam'=> 'Hiburan Malam',
    ];

    /** Tarif default PBJT per sektor (placeholder — lihat catatan class). */
    public const DEFAULT_TAX_RATES = [
        'restoran'     => 10,
        'warung'       => 10,
        'hotel'        => 10,
        'air_baku'     => 10,
        'depot_air'    => 10,
        'laundry'      => 10,
        'game_center'  => 10,
        'bilyard'      => 10,
        'hiburan_malam'=> 10,
    ];

    /** Nama sektor (fallback id bila belum terdaftar). */
    public static function sectorName(string $sectorId): string
    {
        return self::SECTOR_NAMES[$sectorId] ?? $sectorId;
    }

    /** Tarif default sebuah sektor (persen). */
    public static function defaultTaxRate(string $sectorId): float
    {
        return (float) (self::DEFAULT_TAX_RATES[$sectorId] ?? 10);
    }

    /**
     * Mapping sektor → OPD pengampu, difilter per OPD bila `opdId` diberikan.
     *
     * @return Collection<string, Collection> group by sector_id
     */
    public static function mappings(?int $opdId = null): Collection
    {
        $query = SectorOpdMapping::with('opd:id,name,code')
            ->orderBy('sector_id')
            ->orderByDesc('is_primary');

        if ($opdId !== null) {
            $query->where('opd_id', $opdId);
        }

        return $query->get()->groupBy('sector_id');
    }

    /** Cek apakah sebuah OPD mengampu sebuah sektor. */
    public static function opdHandlesSector(int $opdId, string $sectorId): bool
    {
        return SectorOpdMapping::where('opd_id', $opdId)
            ->where('sector_id', $sectorId)
            ->exists();
    }
}
