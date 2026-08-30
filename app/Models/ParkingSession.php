<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingSession extends Model
{
    protected $fillable = [
        'parking_location_id',
        'jukir_user_id',
        'shift_date',
        'vehicle_type',
        'plate_hint',
        'amount',
        'payment_method',
        'qris_reference',
        'bill_id',
        'status',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // ── Scopes ──

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForJukir($query, int $userId)
    {
        return $query->where('jukir_user_id', $userId);
    }

    public function scopeForShiftDate($query, string $date)
    {
        return $query->where('shift_date', $date);
    }

    // ── Relations ──

    public function parkingLocation(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class);
    }

    public function jukir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jukir_user_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
