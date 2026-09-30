<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JukirWallet extends Model
{
    protected $fillable = [
        'user_id',
        'parking_location_id',
        'shift_date',
        'shift_status',
        'shift_opened_at',
        'shift_closed_at',
        'total_cash',
        'total_qris',
        'total_sessions',
        'settled',
        'settled_at',
        'settled_by',
        'total_cash_collected',
        'total_qris_collected',
        'jukir_share_amount',
        'korlap_share_amount',
        'net_kasda_due',
        'settlement_status',
        'handshake_qr_token',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'shift_opened_at' => 'datetime',
        'shift_closed_at' => 'datetime',
        'settled_at' => 'datetime',
        'total_cash' => 'decimal:2',
        'total_qris' => 'decimal:2',
        'total_cash_collected' => 'decimal:2',
        'total_qris_collected' => 'decimal:2',
        'jukir_share_amount' => 'decimal:2',
        'korlap_share_amount' => 'decimal:2',
        'net_kasda_due' => 'decimal:2',
        'total_sessions' => 'integer',
        'settled' => 'boolean',
    ];

    // ── Computed ──

    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->total_cash + (float) $this->total_qris;
    }

    // ── Scopes ──

    public function scopeOpen($query)
    {
        return $query->where('shift_status', 'open');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->where('shift_date', now()->toDateString());
    }

    // ── Relations ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parkingLocation(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class);
    }

    public function settledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    // ── Business Methods ──

    public function addSession(float $amount, string $paymentMethod): void
    {
        if ($paymentMethod === 'qris') {
            $this->increment('total_qris', $amount);
        } else {
            $this->increment('total_cash', $amount);
        }
        $this->increment('total_sessions');
    }

    public function closeShift(): void
    {
        $this->update([
            'shift_status' => 'closed',
            'shift_closed_at' => now(),
        ]);
    }
}
