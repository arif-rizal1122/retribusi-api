<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessUnit extends Model
{
    protected $fillable = [
        'taxpayer_id',
        'business_name',
        'unit_type',
        'location_ref',
        'status',
        'first_transaction_at',
    ];

    protected $casts = [
        'first_transaction_at' => 'datetime',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }
}
