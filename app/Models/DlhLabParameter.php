<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DlhLabParameter extends Model
{
    use HasFactory;

    protected $table = 'dlh_lab_parameters';

    protected $fillable = [
        'code',
        'name',
        'method',
        'unit',
        'tariff',
        'standard_reference',
        'is_active',
    ];

    protected $casts = [
        'tariff' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(
            DlhLabOrder::class,
            'dlh_lab_order_parameters',
            'parameter_id',
            'lab_order_id'
        )->withPivot('tariff_snapshot', 'result_value', 'quality_standard_value', 'compliance_status')
         ->withTimestamps();
    }

    public function orderParameters(): HasMany
    {
        return $this->hasMany(DlhLabOrderParameter::class, 'parameter_id');
    }
}
