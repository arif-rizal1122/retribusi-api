<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'opd_id',
        'code',
        'name',
        'type',
        'address',
        'district',
        'latitude',
        'longitude',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function stalls(): HasMany
    {
        return $this->hasMany(MarketStall::class);
    }

    public function officerAssignments(): HasMany
    {
        return $this->hasMany(MarketOfficerAssignment::class);
    }
}
