<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AftTransaction
 *
 * Pemotongan Auto Fund Transfer (AFT). Model ini menunjuk ke ledger produksi
 * `auto_deduct_logs` — bukan tabel terpisah — sehingga tidak ada dua sumber
 * kebenaran untuk nilai pajak/retribusi yang diteruskan.
 */
class AftTransaction extends Model
{
    use HasFactory;

    protected $table = 'auto_deduct_logs';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public const ESCROW_SETTLED = 'settled';

    public const SOURCE_OFFICER = 'officer_payment';
    public const SOURCE_MERCHANT_OMZET = 'merchant_omzet';

    protected $fillable = [
        'payment_id',
        'payment_request_id',
        'taxpayer_id',
        'tax_object_id',
        'bill_id',
        'source',
        'transaction_type',
        'transaction_amount',
        'tax_amount',
        'deducted_amount',
        'beneficiary_account',
        'beneficiary_bank',
        'reference_number',
        'payment_channel',
        'status',
        'escrow_settlement_status',
        'settled_at',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'transaction_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'deducted_amount' => 'decimal:2',
        'settled_at' => 'datetime',
        'processed_at' => 'datetime',
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
