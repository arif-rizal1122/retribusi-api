<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPbb extends Model
{
    protected $table = 'transaction_pbb';

    protected $fillable = [
        'user_id',
        'taxpayer_id',
        'nop',
        'tahun',
        'amount',
        'denda',
        'total_bayar',
        'ntpd',
        'payment_status',
        'wp_name',
        'wp_address',
        'kelurahan',
        'kota',
        'reversal_reason',
        'api_response',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'denda'       => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'api_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function scopeSuccess($query)
    {
        return $query->where('payment_status', 'success');
    }

    public function scopeByNop($query, string $nop)
    {
        return $query->where('nop', $nop);
    }
}
