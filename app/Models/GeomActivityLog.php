<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeomActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'geom_activity_logs';

    protected $fillable = [
        'user_type',
        'user_id',
        'action',
        'nop',
        'latitude',
        'longitude',
        'device_lat',
        'device_lng',
        'distance_km',
        'is_flagged_remote',
        'ip_address',
        'user_agent',
        'details',
        'created_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'device_lat' => 'decimal:7',
        'device_lng' => 'decimal:7',
        'distance_km' => 'decimal:2',
        'is_flagged_remote' => 'boolean',
        'details' => 'array',
        'created_at' => 'datetime',
    ];
}
