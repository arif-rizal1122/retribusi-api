<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingSpotApplication extends Model
{
    use HasUlids;

    protected $table = 'parking_spot_applications';

    protected $fillable = [
        'user_id',
        'target_zone_id',
        'proposed_spot_name',
        'proposed_polygon',
        'proposed_polygon_geojson',
        'verified_polygon',
        'verified_polygon_geojson',
        'surveyor_id',
        'survey_photos',
        'capacity_r2',
        'capacity_r4',
        'status',
        'rejection_reason',
        'geofence_tolerance_meters',
        'created_spot_id',
    ];

    protected $casts = [
        'proposed_polygon_geojson' => 'array',
        'verified_polygon_geojson' => 'array',
        'survey_photos' => 'array',
        'capacity_r2' => 'integer',
        'capacity_r4' => 'integer',
        'geofence_tolerance_meters' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetZone(): BelongsTo
    {
        return $this->belongsTo(ParkingZone::class, 'target_zone_id');
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }

    public function createdSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class, 'created_spot_id');
    }
}
