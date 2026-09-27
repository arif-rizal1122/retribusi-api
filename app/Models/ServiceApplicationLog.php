<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceApplicationLog extends Model
{
    protected $table = 'service_application_logs';

    public $timestamps = false;

    protected $fillable = [
        'application_id', 'from_status', 'to_status',
        'actor_id', 'actor_role', 'notes', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at ??= now();
        });
    }
}
