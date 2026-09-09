<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TpiTransaksiLelang extends Model
{
    use HasFactory;

    protected $table = 'tpi_transaksi_lelang';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'kode_transaksi',
        'kode_tpi',
        'waktu_transaksi',
        'kapal_id',
        'kapal_nama',
        'kapal_gt',
        'nomor_kusuka',
        'bakul_id',
        'bakul_nama',
        'bakul_nik_nib',
        'volume_lelang_kg',
        'nilai_transaksi_lelang',
        'biaya_fasilitas',
        'retribusi_lelang_persen',
        'total_retribusi',
        'status_bayar',
        'metode_bayar',
        'ntpd',
        'bank_ref',
        'officer_user_id',
        'cash_settlement_batch_id',
        'catatan',
    ];

    protected $casts = [
        'waktu_transaksi' => 'datetime',
        'kapal_gt' => 'decimal:2',
        'volume_lelang_kg' => 'decimal:2',
        'nilai_transaksi_lelang' => 'decimal:2',
        'biaya_fasilitas' => 'decimal:2',
        'retribusi_lelang_persen' => 'decimal:2',
        'total_retribusi' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->kode_transaksi)) {
                $prefix = 'TPI-WM';
                if ($model->kode_tpi === 'TPI-BIMA') {
                    $prefix = 'TPI-BM';
                }
                $dateStr = now()->format('Ymd');
                $countToday = static::whereDate('created_at', now()->toDateString())->count() + 1;
                $model->kode_transaksi = sprintf('%s/%s/%04d', $prefix, $dateStr, $countToday);
            }
        });
    }

    public function rincianFasilitas()
    {
        return $this->hasMany(TpiRincianFasilitas::class, 'transaksi_lelang_id');
    }

    public function officer()
    {
        return $this->belongsTo(User::class, 'officer_user_id');
    }

    /**
     * Generate Nomor Transaksi Penerimaan Daerah (NTPD) unik kas daerah.
     */
    public function generateNtpd(): string
    {
        if ($this->ntpd) {
            return $this->ntpd;
        }
        // Format: 88 + YYYYMMDD + HHmmss + 2 digit random
        $this->ntpd = sprintf('88%s%02d', now()->format('YmdHis'), mt_rand(10, 99));
        $this->status_bayar = 'LUNAS';
        $this->save();

        return $this->ntpd;
    }
}
