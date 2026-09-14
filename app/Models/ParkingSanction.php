<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class ParkingSanction extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'jukir_user_id',
        'inspector_user_id',
        'parking_location_id',
        'sanction_type',
        'reason',
        'status',
    ];

    public function jukir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jukir_user_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ParkingLocation::class, 'parking_location_id');
    }
}