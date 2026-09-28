<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'code',
        'group',
        'label',
        'channels',
        'priority',
        'wa_template',
        'push_title',
        'push_body',
        'deep_link_pattern',
        'is_active',
    ];

    protected $casts = [
        'channels' => 'array',
        'is_active' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'template_code', 'code');
    }
}
