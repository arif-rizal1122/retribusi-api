<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetugasTask extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\RetributionTypeScope);
    }

    protected $fillable = [
        'user_id',
        'zone_id',
        'taxpayer_id',
        'tax_object_id',
        'verification_id',
        'task_type',
        'due_date',
        'notes',
        'status',
        'completed_at',
        'completion_photo_path',
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

    public function taxObject()
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function verification()
    {
        return $this->belongsTo(Verification::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
