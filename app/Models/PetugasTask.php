<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetugasTask extends Model
{
    protected $fillable = [
        'user_id',
        'zone_id',
        'taxpayer_id',
        'due_date',
        'notes',
        'status',
        'completed_at',
        'created_by'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function taxpayer()
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
