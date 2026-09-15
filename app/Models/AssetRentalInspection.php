<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class AssetRentalInspection extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'asset_rental_id',
        'inspected_by',
        'inspection_type',
        'hour_meter',
        'fuel_level_percent',
        'checklist',
        'notes',
        'latitude',
        'longitude',
        'photo_path',
        'is_overtime',
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'denda_bill_id',
        'inspected_at',
    ];

    protected $casts = [
        'hour_meter' => 'float',
        'fuel_level_percent' => 'integer',
        'checklist' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_overtime' => 'boolean',
        'overtime_hours' => 'float',
        'overtime_rate' => 'float',
        'overtime_amount' => 'float',
        'inspected_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(AssetRental::class, 'asset_rental_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function dendaBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'denda_bill_id');
    }
}