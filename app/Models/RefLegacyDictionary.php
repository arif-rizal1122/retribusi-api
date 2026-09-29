<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kamus referensi legacy (read-only). Bukan tabel transaksi.
 */
class RefLegacyDictionary extends Model
{
    protected $table = 'ref_legacy_dictionary';

    protected $fillable = [
        'legacy_source',
        'legacy_code',
        'legacy_npwpd',
        'owner_nik_or_nib',
        'legal_entity_name',
        'commercial_brand_name',
        'sector_code',
        'kode_wilayah',
        'address_text',
        'technical_specs',
        'claim_status',
        'claimed_by_user_id',
        'object_number_registry_id',
        'taxpayer_id',
        'claimed_at',
        'is_active',
    ];

    protected $casts = [
        'technical_specs' => 'array',
        'claimed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeTersedia($query)
    {
        return $query->where('is_active', true)->where('claim_status', 'UNCLAIMED');
    }

    public function namaTampil(): string
    {
        return $this->commercial_brand_name ?: ($this->legal_entity_name ?: 'Objek Usaha');
    }

    /**
     * Spesifikasi teknis (kapasitas fisik / perizinan) yang diserap dari data lama.
     */
    public function spesifikasi(string $key, mixed $default = null): mixed
    {
        return data_get($this->technical_specs, $key, $default);
    }
}
