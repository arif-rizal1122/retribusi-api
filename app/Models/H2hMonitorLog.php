<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class H2hMonitorLog extends Model
{
    protected $fillable = [
        'connector',
        'status',
        'latency_ms',
        'error_message',
        'endpoint',
        'checked_at',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
        'checked_at' => 'datetime',
    ];
}
