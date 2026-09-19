<?php

namespace App\Models\Dlh;

use App\Models\TaxObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DlhSampahAccount extends Model
{
    use HasFactory;

    protected $table = 'dlh_sampah_accounts';

    protected $fillable = [
        'tax_object_id',
        'kode_objek',
        'tarif_kategori_id',
        'penanggung_jawab_nama',
        'penanggung_jawab_telepon',
        'penanggung_jawab_nik',
        'alamat_lengkap',
        'kelurahan',
        'kecamatan',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'geofence_radius_meters' => 'integer',
    ];

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class, 'tax_object_id');
    }

    public function tarifKategori(): BelongsTo
    {
        return $this->belongsTo(DlhSampahTarifKategori::class, 'tarif_kategori_id');
    }

    public function billings(): HasMany
    {
        return $this->hasMany(DlhSampahBilling::class, 'account_id');
    }
}
