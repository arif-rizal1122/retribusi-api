<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_code',
        'opd_code',
        'asset_id',
        'asset_rental_id',
        'bill_id',
        'contract_number',
        'tenant_identity',
        'valid_from',
        'valid_until',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetItem::class, 'asset_id');
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(AssetRental::class, 'asset_rental_id');
    }
}
