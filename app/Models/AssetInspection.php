<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInspection extends Model
{
    use HasFactory;

    protected $table = 'asset_inspections';

    protected $fillable = [
        'asset_rental_id',
        'asset_item_id',
        'inspector_user_id',
        'inspection_type',
        'hour_meter',
        'fuel_level_percent',
        'checklist',
        'condition_notes',
        'photos',
        'latitude',
        'longitude',
        'is_overtime',
        'overtime_hours',
        'denda_penalty_bill_id',
        'inspected_at',
    ];

    protected $casts = [
        'hour_meter' => 'decimal:2',
        'fuel_level_percent' => 'integer',
        'checklist' => 'array',
        'photos' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_overtime' => 'boolean',
        'overtime_hours' => 'decimal:2',
        'inspected_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(AssetRental::class, 'asset_rental_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class, 'asset_item_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    public function dendaBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'denda_penalty_bill_id');
    }
}
