<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenaltyWaiver extends Model
{
    protected $fillable = [
        'bill_id',
        'requested_by',
        'approved_by',
        'reason',
        'reduction_type',
        'reduction_value',
        'status',
        'approval_notes',
    ];

    protected $casts = [
        'reduction_value' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
