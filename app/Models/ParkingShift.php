<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Auditable;

class ParkingShift extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'parking_location_id',
        'shift_date',
        'shift_status',
        'shift_opened_at',
        'shift_closed_at',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'shift_opened_at' => 'datetime',
        'shift_closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ParkingSession::class, 'shift_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('shift_status', 'open');
    }
}