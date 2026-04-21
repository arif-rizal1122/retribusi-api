<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = [
        'opd_id',
        'retribution_type_id',
        'retribution_classification_id',
        'name',
        'code',
        'description',
        'latitude',
        'longitude',
        'geometry_type',
        'coordinates',
    ];

    protected $casts = [
        'coordinates' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\RetributionTypeScope);
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function retributionType()
    {
        return $this->belongsTo(RetributionType::class);
    }

    public function classification()
    {
        return $this->belongsTo(RetributionClassification::class, 'retribution_classification_id');
    }

    public function rates()
    {
        return $this->hasMany(RetributionRate::class);
    }
}
