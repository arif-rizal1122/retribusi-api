<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DlhLabOrderParameter extends Model
{
    use HasFactory;

    protected $table = 'dlh_lab_order_parameters';

    protected $fillable = [
        'lab_order_id',
        'parameter_id',
        'tariff_snapshot',
        'result_value',
        'quality_standard_value',
        'compliance_status',
    ];

    protected $casts = [
        'tariff_snapshot' => 'decimal:2',
    ];

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(DlhLabOrder::class, 'lab_order_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(DlhLabParameter::class, 'parameter_id');
    }
}
