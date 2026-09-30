<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParkingSpot extends Model
{
    use HasUlids;

    protected $table = 'parking_spots';

    protected $fillable = [
        'zone_id',
        'spot_name',
        'capacity_r2',
        'capacity_r4',
        'assigned_jukir_id',
        'is_occupied',
        'polygon_boundary',
        'polygon_geojson',
        'is_geofence_locked',
    ];

    protected $casts = [
        'capacity_r2' => 'integer',
        'capacity_r4' => 'integer',
        'is_occupied' => 'boolean',
        'polygon_geojson' => 'array',
        'is_geofence_locked' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ParkingZone::class, 'zone_id');
    }

    public function assignedJukir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_jukir_id');
    }

    public function jukirProfile(): HasOne
    {
        return $this->hasOne(JukirProfile::class, 'assigned_spot_id');
    }
}
