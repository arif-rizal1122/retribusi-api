<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTransaction extends Model
{
    protected $fillable = [
        'opd_id',
        'taxpayer_id',
        'tax_object_id',
        'transaction_date',
        'amount',
        'tax_amount',
        'source',
        'description',
        'metadata',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
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
