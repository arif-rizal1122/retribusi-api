<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalkulatorSimulation extends Model
{
    use HasUlids;

    protected $table = 'kalkulator_simulations';

    protected $fillable = [
        'user_id',
        'simulation_type',
        'target_zone_id',
        'input_payload',
        'result_payload',
        'converted_to_registration',
        'application_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'input_payload' => 'array',
        'result_payload' => 'array',
        'converted_to_registration' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetZone(): BelongsTo
    {
        return $this->belongsTo(ParkingZone::class, 'target_zone_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ParkingSpotApplication::class, 'application_id');
    }
}
