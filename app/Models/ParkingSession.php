<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class ParkingSession extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'parking_location_id',
        'jukir_user_id',
        'shift_id',
        'shift_date',
        'vehicle_type',
        'plate_hint',
        'duration_days',
        'amount',
        'payment_method',
        'qris_reference',
        'bill_id',
        'status',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'duration_days' => 'integer',
        'amount' => 'float',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function jukir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jukir_user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ParkingShift::class, 'shift_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}