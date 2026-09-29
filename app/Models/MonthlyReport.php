<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    protected $fillable = [
        'taxpayer_id',
        'tax_object_id',
        'period',
        'turnover_amount',
        'other_income',
        'dpp_amount',
        'tax_amount',
        'tax_rate',
        'penalty_months',
        'penalty_amount',
        'total_bill',
        'device_sync_payload',
        'source',
        'attachments',
        'notes',
        'status',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'validated_at' => 'datetime',
        'turnover_amount' => 'float',
        'other_income' => 'float',
        'dpp_amount' => 'float',
        'tax_amount' => 'float',
        'tax_rate' => 'float',
        'penalty_amount' => 'float',
        'total_bill' => 'float',
        'device_sync_payload' => 'array',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
