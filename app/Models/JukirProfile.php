<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JukirProfile extends Model
{
    protected $table = 'jukir_profiles';

    protected $fillable = [
        'user_id',
        'korlap_id',
        'nik',
        'surat_tugas_no',
        'surat_tugas_expired_at',
        'deposit_balance',
        'split_rkud_percent',
        'split_jukir_percent',
        'revenue_share_pct',
        'bpjs_tk_number',
        'bpjs_tk_status',
        'status',
        'verification_status',
        'assigned_location_id',
        'assigned_spot_id',
        'qr_auth_token',
        'bank_name',
        'bank_account_number',
        'bank_account_holder_name',
        'is_bank_account_verified',
        'assigned_polygon',
        'is_geofence_locked',
    ];

    protected $casts = [
        'surat_tugas_expired_at' => 'datetime',
        'deposit_balance' => 'decimal:2',
        'split_rkud_percent' => 'decimal:2',
        'split_jukir_percent' => 'decimal:2',
        'revenue_share_pct' => 'decimal:2',
        'is_bank_account_verified' => 'boolean',
        'is_geofence_locked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function korlap(): BelongsTo
    {
        return $this->belongsTo(User::class, 'korlap_id');
    }

    public function assignedLocation(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'assigned_location_id');
    }

    public function assignedSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class, 'assigned_spot_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->where('verification_status', 'APPROVED');
    }
}
