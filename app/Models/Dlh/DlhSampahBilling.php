<?php

namespace App\Models\Dlh;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DlhSampahBilling extends Model
{
    use HasFactory;

    protected $table = 'dlh_sampah_billings';

    protected $fillable = [
        'account_id',
        'bill_id',
        'periode_bulan',
        'nominal_pokok',
        'nominal_denda',
        'total_tagihan',
        'status_bayar',
        'tgl_jatuh_tempo',
        'tgl_bayar',
        'metode_bayar',
        'collector_user_id',
    ];

    protected $casts = [
        'nominal_pokok' => 'decimal:2',
        'nominal_denda' => 'decimal:2',
        'total_tagihan' => 'decimal:2',
        'tgl_jatuh_tempo' => 'date',
        'tgl_bayar' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(DlhSampahAccount::class, 'account_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }
}
