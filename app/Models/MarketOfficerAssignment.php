<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketOfficerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_id',
        'market_name',
        'officer_name',
        'user_id',
        'assigned_areas',
        'collects_daily_pkl',
        'signed_by',
        'decree_date',
        'notes',
    ];

    protected $casts = [
        'assigned_areas' => 'array',
        'collects_daily_pkl' => 'boolean',
        'decree_date' => 'date',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
