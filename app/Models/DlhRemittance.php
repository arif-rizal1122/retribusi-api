<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DlhRemittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'collector_user_id',
        'period_month',
        'period_year',
        'remitted_amount',
        'bank_reference_number',
        'notes',
        'status',
        'remitted_at',
    ];

    protected $casts = [
        'remitted_amount' => 'decimal:2',
        'remitted_at' => 'datetime',
    ];

    /**
     * Petugas/juru pungut pasar yang melakukan penyetoran.
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }

    /**
     * Karcis yang terikat pada batch penyetoran ini.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(DlhMarketTicket::class, 'remittance_id');
    }

    /**
     * Total nominal karcis dalam batch ini.
     */
    public function getBatchAmountAttribute(): float
    {
        return (float) $this->tickets()->sum('amount');
    }
}