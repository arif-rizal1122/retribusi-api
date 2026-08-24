<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoDeductLog extends Model
{
    protected $fillable = [
        'city_id', 'taxpayer_id', 'tax_object_id', 'bill_id',
        'source', 'transaction_type',
        'transaction_amount', 'tax_amount', 'deducted_amount',
        'reference_number', 'payment_channel',
        'metadata', 'status', 'escrow_settlement_status', 'failure_reason', 'processed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'transaction_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'deducted_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
