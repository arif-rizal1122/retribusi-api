<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketDailyTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'collector_user_id',
        'collector_name',
        'market_name',
        'building_name',
        'merchant_name',
        'quantity',
        'unit_amount',
        'total_amount',
        'payment_method',
        'qr_token',
        'issued_at',
    ];

    protected $casts = [
        'unit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }
}
