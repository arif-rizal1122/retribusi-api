<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DlhMarketTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'collector_user_id',
        'market_name',
        'stall_name_or_number',
        'merchant_name',
        'amount',
        'payment_method',
        'qr_token',
        'remittance_id',
        'issued_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    /**
     * Petugas yang menerbitkan karcis.
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }

    /**
     * Batch penyetoran (kliring akhir bulan) yang menyetorkan karcis ini,
     * null berarti masih dalam holding balance.
     */
    public function remittance(): BelongsTo
    {
        return $this->belongsTo(DlhRemittance::class, 'remittance_id');
    }
}