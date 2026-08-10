<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRental extends Model
{
    protected $fillable = [
        'rental_code',
        'taxpayer_id',
        'asset_item_id',
        'retribution_type_id',
        'retribution_classification_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'lama_sewa',
        'satuan_sewa',
        'tarif_per_satuan',
        'total_biaya',
        'dp',
        'sisa_pembayaran',
        'metode_pembayaran',
        'status',
        'lokasi_penggunaan',
        'koordinat',
        'jenis_pekerjaan',
        'luas_volume',
        'nama_proyek',
        'nomor_spk',
        'penyelia',
        'hp_penyelia',
        'operator_nama',
        'operator_hp',
        'operator_sim',
        'operator_sertifikat_url',
        'dokumen',
        'tanggal_verifikasi',
        'tanggal_approval',
        'catatan_verifikator',
        'catatan_approval',
        'verified_by_user_id',
        'approved_by_user_id',
        'tanggal_pengembalian',
        'kondisi_pengembalian',
        'catatan_pengembalian',
        'denda',
        'is_demo',
        'metadata',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'lama_sewa' => 'decimal:2',
        'tarif_per_satuan' => 'decimal:2',
        'total_biaya' => 'decimal:2',
        'dp' => 'decimal:2',
        'sisa_pembayaran' => 'decimal:2',
        'denda' => 'decimal:2',
        'tanggal_verifikasi' => 'datetime',
        'tanggal_approval' => 'datetime',
        'tanggal_pengembalian' => 'date',
        'dokumen' => 'array',
        'is_demo' => 'boolean',
        'metadata' => 'array',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class, 'taxpayer_id');
    }

    public function assetItem(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class, 'asset_item_id');
    }

    public function retributionType(): BelongsTo
    {
        return $this->belongsTo(RetributionType::class, 'retribution_type_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
