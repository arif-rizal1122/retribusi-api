<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetugasTask extends Model
{
    protected $fillable = [
        'user_id',
        'zone_id',
        'taxpayer_id',
        'status',
        'due_date',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function taxpayer()
    {
        return $this->belongsTo(Taxpayer::class);
    }
}
