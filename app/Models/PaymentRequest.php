<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends Model
{
    protected $fillable = [
        'bill_id',
        'tax_object_id',
        'taxpayer_id',
        'payment_channel',
        'method',
        'va_number',
        'qr_content',
        'amount_snapshot',
        'admin_fee_snapshot',
        'penalty_snapshot',
        'expires_at',
        'external_id',
        'provider_reference',
        'status',
        'paid_at',
        'raw_request_safe',
        'raw_response_safe',
    ];

    protected $casts = [
        'amount_snapshot' => 'decimal:2',
        'admin_fee_snapshot' => 'decimal:2',
        'penalty_snapshot' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'raw_request_safe' => 'array',
        'raw_response_safe' => 'array',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentRequestItem::class);
    }
}
