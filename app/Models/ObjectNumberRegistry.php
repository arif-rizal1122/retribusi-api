<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjectNumberRegistry extends Model
{
    protected $table = 'object_number_registry';

    protected $fillable = [
        'nomor',
        'nomor_sumber',
        'nomor_baku',
        'jenis',
        'sector_code',
        'kode_wilayah',
        'urut',
        'tax_object_id',
        'taxpayer_id',
        'opd_id',
        'legacy_code',
        'legacy_npwpd',
        'legacy_source',
        'claim_status',
        'claimed_by_user_id',
        'claimed_at',
        'status',
        'latitude',
        'longitude',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'claimed_at' => 'datetime',
        'urut' => 'integer',
    ];

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeJenis($query, string $jenis)
    {
        return $query->where('jenis', strtoupper($jenis));
    }

    public function labelJenis(): string
    {
        return (string) config('kodifikasi.jenis.'.$this->jenis, $this->jenis);
    }
}
