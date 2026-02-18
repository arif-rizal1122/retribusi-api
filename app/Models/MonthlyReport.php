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
        'tax_amount',
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
        'tax_amount' => 'float',
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
