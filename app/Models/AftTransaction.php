<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AftTransaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_FAILED = 'failed';

    public const SOURCE_OFFICER = 'officer_payment';
    public const SOURCE_MERCHANT_OMZET = 'merchant_omzet';

    protected $fillable = [
        'payment_id',
        'payment_request_id',
        'taxpayer_id',
        'transaction_amount',
        'tax_amount',
        'beneficiary_account',
        'beneficiary_bank',
        'status',
        'settled_at',
        'metadata',
    ];

    protected $casts = [
        'transaction_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'settled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }
}
