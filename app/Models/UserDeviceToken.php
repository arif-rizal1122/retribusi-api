<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'app',
        'token',
        'keys',
        'last_active_at',
    ];

    protected $casts = [
        'keys' => 'array',
        'last_active_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
