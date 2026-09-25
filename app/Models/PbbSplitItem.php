<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PbbSplitItem extends Model
{
    protected $fillable = [
        'pbb_mutation_id',
        'item_index',
        'target_nik',
        'target_name',
        'land_area_m2',
        'building_area_m2',
        'proportional_debt_amount',
        'generated_nop',
        // NIB: hasil TERBITAN setelah verifikasi BPN, bukan input pendaftar
        'nib',
        'nib_status',
        'nib_issued_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'land_area_m2' => 'decimal:2',
        'building_area_m2' => 'decimal:2',
        'proportional_debt_amount' => 'decimal:2',
        'nib_issued_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(PbbMutation::class, 'pbb_mutation_id');
    }
}
