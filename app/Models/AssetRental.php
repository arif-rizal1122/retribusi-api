<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Auditable;

class AssetRental extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'rental_code',
        'nomor_kontrak',
        'taxpayer_id',
        'user_id',
        'asset_item_id',
        'opd_id',
        'retribution_type_id',
        'retribution_classification_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'lama_sewa',
        'satuan_sewa',
        'tarif_per_satuan',
        'include_tronton',
        'jarak_tronton_km',
        'biaya_tronton',
        'total_biaya',
        'dp',
        'sisa_pembayaran',
        'tahap_1_amount',
        'tahap_1_status',
        'tahap_2_amount',
        'tahap_2_status',
        'actual_hours',
        'metode_pembayaran',
        'status',
        'verified_by',
        'approved_by',
        'catatan_verifikator',
        'catatan_approval',
        'tanggal_pengembalian',
        'kondisi_pengembalian',
        'denda',
        'lokasi_penggunaan',
        'jenis_pekerjaan',
        'nama_proyek',
        'koordinat',
        'clock_in_at',
        'clock_out_at',
        'survey_akses_jalan',
        'survey_dekat_jalan_raya',
        'survey_keamanan',
        'survey_lahan_luas',
        'survey_kesimpulan',
        'survey_foto_path',
        'survey_rekomendasi_tronton',
        'survey_penjebolan_akses',
        'survey_penjebolan_catatan',
        'survey_rekomendasi_alat',
        'survey_submitted_at',
        'penyelia',
        'hp_penyelia',
        'operator_nama',
        'operator_hp',
        'operator_sim',
        'nomor_spk',
        'nama_konsumen',
        'jenis_bangunan',
        'jumlah_rit',
        'kelurahan',
        'pelaksana_armada',
        'foto_lokasi',
        'bukti_pembayaran_manual',
        'keterangan_tambahan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tarif_per_satuan' => 'float',
        'include_tronton' => 'boolean',
        'jarak_tronton_km' => 'float',
        'biaya_tronton' => 'float',
        'total_biaya' => 'float',
        'dp' => 'float',
        'sisa_pembayaran' => 'float',
        'tahap_1_amount' => 'float',
        'tahap_2_amount' => 'float',
        'actual_hours' => 'float',
        'survey_akses_jalan' => 'boolean',
        'survey_dekat_jalan_raya' => 'boolean',
        'survey_keamanan' => 'boolean',
        'survey_lahan_luas' => 'boolean',
        'survey_penjebolan_akses' => 'boolean',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'survey_submitted_at' => 'datetime',
        'tanggal_pengembalian' => 'date',
        'denda' => 'float',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assetItem(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function retributionType(): BelongsTo
    {
        return $this->belongsTo(RetributionType::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(RetributionClassification::class, 'retribution_classification_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(AssetRentalInspection::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }
}