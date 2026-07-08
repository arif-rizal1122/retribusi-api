<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionLog extends Model
{
    protected $fillable = [
        'tax_transaction_id', 'auto_deduct_log_id', 'bill_id',
        'taxpayer_id', 'tax_object_id',
        'type', 'trigger',
        'original_amount', 'new_amount',
        'original_tax', 'new_tax', 'difference',
        'details', 'status', 'reason',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'details' => 'array',
        'original_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'original_tax' => 'decimal:2',
        'new_tax' => 'decimal:2',
        'difference' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function taxTransaction(): BelongsTo
    {
        return $this->belongsTo(TaxTransaction::class);
    }

    public function autoDeductLog(): BelongsTo
    {
        return $this->belongsTo(AutoDeductLog::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }
}
