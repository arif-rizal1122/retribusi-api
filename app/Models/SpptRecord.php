<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpptRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'nop',
        'taxpayer_name',
        'taxpayer_address',
        'object_address',
        'kelurahan',
        'kecamatan',
        'tax_year',
        'land_area_m2',
        'building_area_m2',
        'land_class',
        'building_class',
        'land_njop_per_m2',
        'building_njop_per_m2',
        'total_land_njop',
        'total_building_njop',
        'njoptkp',
        'taxable_njop',
        'tax_rate',
        'tax_amount',
        'due_date',
        'payment_locations',
        'payment_status',
        'data_source',
        'bpn_sync_status',
        'bpn_last_synced_at',
        'conflict_fields',
    ];

    protected $casts = [
        'land_area_m2' => 'decimal:2',
        'building_area_m2' => 'decimal:2',
        'land_njop_per_m2' => 'decimal:2',
        'building_njop_per_m2' => 'decimal:2',
        'total_land_njop' => 'decimal:2',
        'total_building_njop' => 'decimal:2',
        'njoptkp' => 'decimal:2',
        'taxable_njop' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_locations' => 'array',
        'conflict_fields' => 'array',
        'bpn_last_synced_at' => 'datetime',
    ];

    /** NJOPTKP default mengikuti formula PBB-P2 M-PAD: (njop - 10.000.000) * 0.003 */
    public const DEFAULT_NJOPTKP = 10_000_000;
    /** Tarif default PBB-P2 Kota Baubau (0.3%) sesuai formula M-PAD */
    public const DEFAULT_TAX_RATE = 0.0030;

    /** NJOP gabungan (tanah + bangunan) — kompatibel dengan field `njop` form PBB-P2 M-PAD */
    public function getNjopAttribute(): float
    {
        return round(($this->total_land_njop ?? 0) + ($this->total_building_njop ?? 0), 2);
    }

    /**
     * Hitung otomatis NJOP & pajak terutang dari rincian fisik.
     * Mengikuti pola M-PAD: (NJOP total - NJOPTKP) × tarif.
     * Default NJOPTKP 10 juta, tarif 0.3% (bisa dioverride per record).
     */
    public function calculateTax(?float $njoptkp = null): array
    {
        $njoptkp = $njoptkp ?? self::DEFAULT_NJOPTKP;
        $rate = (float) ($this->tax_rate ?? self::DEFAULT_TAX_RATE);

        $totalLand = round(($this->land_area_m2 ?? 0) * ($this->land_njop_per_m2 ?? 0), 2);
        $totalBuilding = round(($this->building_area_m2 ?? 0) * ($this->building_njop_per_m2 ?? 0), 2);
        $totalNjop = $totalLand + $totalBuilding;
        $taxable = max(0, round($totalNjop - $njoptkp, 2));
        $taxAmount = round($taxable * $rate, 2);

        return [
            'total_land_njop' => $totalLand,
            'total_building_njop' => $totalBuilding,
            'njoptkp' => $njoptkp,
            'taxable_njop' => $taxable,
            'tax_amount' => $taxAmount,
        ];
    }
}
