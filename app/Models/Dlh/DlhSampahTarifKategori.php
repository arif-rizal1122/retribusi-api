<?php

namespace App\Models\Dlh;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DlhSampahTarifKategori extends Model
{
    use HasFactory;

    protected $table = 'dlh_sampah_tarif_kategori';

    protected $fillable = [
        'kode_tarif',
        'nama_kategori',
        'kelompok',
        'tarif_bulanan',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tarif_bulanan' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(DlhSampahAccount::class, 'tarif_kategori_id');
    }
}
