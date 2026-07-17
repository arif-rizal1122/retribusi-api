<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestItem extends Model
{
    protected $fillable = [
        'payment_request_id',
        'bill_id',
        'amount_snapshot',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount_snapshot' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
