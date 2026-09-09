<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TpiRincianFasilitas extends Model
{
    use HasFactory;

    protected $table = 'tpi_rincian_fasilitas';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'transaksi_lelang_id',
        'jenis_fasilitas',
        'qty',
        'satuan',
        'tarif_satuan',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'tarif_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function transaksiLelang()
    {
        return $this->belongsTo(TpiTransaksiLelang::class, 'transaksi_lelang_id');
    }
}
