<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DlhLabOrder extends Model
{
    use HasFactory;

    protected $table = 'dlh_lab_orders';

    protected $fillable = [
        'order_number',
        'taxpayer_id',
        'applicant_name',
        'institution_name',
        'contact_phone',
        'applicant_address',
        'sample_matrix',
        'sampling_location',
        'sampling_date',
        'total_amount',
        'bill_id',
        'status',
        'lhu_document_url',
        'tte_status',
        'tte_signature_id',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'sampling_date' => 'date',
        'total_amount' => 'float',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class, 'taxpayer_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    public function orderParameters(): HasMany
    {
        return $this->hasMany(DlhLabOrderParameter::class, 'lab_order_id');
    }

    public function parameters(): BelongsToMany
    {
        return $this->belongsToMany(
            DlhLabParameter::class,
            'dlh_lab_order_parameters',
            'lab_order_id',
            'parameter_id'
        )->withPivot('tariff_snapshot', 'result_value', 'quality_standard_value', 'compliance_status')
         ->withTimestamps();
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereIn('status', ['PAID', 'SAMPLING', 'ANALYZING', 'COMPLETED']);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'COMPLETED');
    }
}
