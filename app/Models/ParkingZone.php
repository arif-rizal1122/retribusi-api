<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingZone extends Model
{
    use HasUlids;

    protected $table = 'parking_zones';

    protected $fillable = [
        'opd_id',
        'zone_code',
        'zone_name',
        'zone_type',
        'latitude',
        'longitude',
        'polygon_geom',
        'polygon_geojson',
        'annual_target_revenue',
        'korlap_id',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'polygon_geojson' => 'array',
        'annual_target_revenue' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function korlap(): BelongsTo
    {
        return $this->belongsTo(User::class, 'korlap_id');
    }

    public function spots(): HasMany
    {
        return $this->hasMany(ParkingSpot::class, 'zone_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ParkingSpotApplication::class, 'target_zone_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
