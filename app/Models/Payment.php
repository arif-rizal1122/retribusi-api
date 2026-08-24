<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\RetributionTypeScope);
    }

    protected $fillable = [
        'bill_id',
        'payment_request_id',
        'bank_config_id',
        'taxpayer_id',
        'tax_object_id',
        'transaction_id',
        'reference_number',
        'receipt_number',
        'payment_method',
        'channel',
        'raw_callback_data',
        'amount',
        'status',
        'billing_period',
        'paid_at',
        'approved_by',
        'proof_url',
        'metadata',
        'tendered_amount',
        'change_amount',
        'reference_number',
        'receipt_number',
        'channel',
        'raw_callback_data',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'raw_callback_data' => 'array',
        'tendered_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'raw_callback_data' => 'array',
    ];

    public function bankConfig(): BelongsTo
    {
        return $this->belongsTo(BankConfig::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
