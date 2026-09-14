<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RusunRoom extends Model
{
    protected $fillable = [
        'asset_item_id',
        'complex_code',
        'floor',
        'room_number',
        'status',
        'current_rental_id',
        'notes',
    ];

    protected $casts = [
        'floor' => 'integer',
    ];

    public function assetItem(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class, 'asset_item_id');
    }

    public function currentRental(): BelongsTo
    {
        return $this->belongsTo(AssetRental::class, 'current_rental_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(AssetRental::class, 'room_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeByComplex(Builder $query, string $complexCode): Builder
    {
        return $query->where('complex_code', $complexCode);
    }

    public function scopeByFloor(Builder $query, int $floor): Builder
    {
        return $query->where('floor', $floor);
    }
}
