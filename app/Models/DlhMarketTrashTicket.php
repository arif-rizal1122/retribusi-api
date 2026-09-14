<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DlhMarketTrashTicket extends Model
{
    use HasFactory;

    protected $table = 'dlh_market_trash_tickets';

    protected $fillable = [
        'ticket_code',
        'collector_user_id',
        'remittance_id',
        'market_name',
        'stall_name_or_number',
        'merchant_name',
        'amount',
        'payment_method',
        'qr_token',
        'issued_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'issued_at' => 'datetime',
    ];

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(DlhCollectorRemittance::class, 'remittance_id');
    }
}
