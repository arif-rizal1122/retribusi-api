<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRental extends Model
{
    protected $fillable = [
        'rental_code',
        'nomor_kontrak',
        'taxpayer_id',
        'asset_item_id',
        'room_id',
        'retribution_type_id',
        'retribution_classification_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'lama_sewa',
        'satuan_sewa',
        'periode_bulan',
        'buffer_hari',
        'tarif_per_satuan',
        'total_biaya',
        'dp',
        'is_guarantee_paid',
        'guarantee_paid_at',
        'sisa_pembayaran',
        'tahap_1_amount',
        'tahap_1_status',
        'tahap_1_paid_at',
        'tahap_2_amount',
        'tahap_2_status',
        'tahap_2_paid_at',
        'actual_hours',
        'include_tronton',
        'jarak_tronton_km',
        'biaya_tronton',
        'survey_rekomendasi_tronton',
        'survey_penjebolan_akses',
        'survey_penjebolan_catatan',
        'survey_rekomendasi_alat',
        'metode_pembayaran',
        'status',
        'lokasi_penggunaan',
        'polygon_geom',
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
        'survey_akses_jalan',
        'survey_dekat_jalan_raya',
        'survey_keamanan',
        'survey_lahan_luas',
        'survey_kesimpulan',
        'survey_foto_path',
        'surveyor_user_id',
        'survey_submitted_at',
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
        'buffer_hari' => 'integer',
        'tarif_per_satuan' => 'decimal:2',
        'total_biaya' => 'decimal:2',
        'dp' => 'decimal:2',
        'sisa_pembayaran' => 'decimal:2',
        'tahap_1_amount' => 'decimal:2',
        'tahap_1_paid_at' => 'datetime',
        'tahap_2_amount' => 'decimal:2',
        'tahap_2_paid_at' => 'datetime',
        'actual_hours' => 'decimal:2',
        'denda' => 'decimal:2',
        'include_tronton' => 'boolean',
        'jarak_tronton_km' => 'decimal:2',
        'biaya_tronton' => 'decimal:2',
        'survey_akses_jalan' => 'boolean',
        'survey_dekat_jalan_raya' => 'boolean',
        'survey_keamanan' => 'boolean',
        'survey_lahan_luas' => 'boolean',
        'survey_penjebolan_akses' => 'boolean',
        'survey_submitted_at' => 'datetime',
        'tanggal_verifikasi' => 'datetime',
        'tanggal_approval' => 'datetime',
        'tanggal_pengembalian' => 'date',
        'dokumen' => 'array',
        'is_guarantee_paid' => 'boolean',
        'guarantee_paid_at' => 'datetime',
        'is_demo' => 'boolean',
        'metadata' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(RusunRoom::class, 'room_id');
    }

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

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get GeoJSON representation of polygon_geom.
     */
    public function getPolygonGeojsonAttribute()
    {
        if (!$this->polygon_geom) {
            return null;
        }

        $result = \Illuminate\Support\Facades\DB::selectOne("SELECT ST_AsGeoJSON(polygon_geom) as geojson FROM asset_rentals WHERE id = ?", [$this->id]);
        return $result ? json_decode($result->geojson, true) : null;
    }
}
