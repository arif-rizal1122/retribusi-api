<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JukirMonthlyClaim extends Model
{
    use HasUlids;

    protected $table = 'jukir_monthly_claims';

    protected $fillable = [
        'jukir_id',
        'period_month',
        'period_year',
        'total_transactions_count',
        'total_gross_turnover',
        'cash_turnover',
        'qris_turnover',
        'jukir_gross_share',
        'cash_already_retained',
        'bpjs_deduction',
        'net_payable_amount',
        'bank_name',
        'account_number',
        'disbursement_status',
        'paid_at',
        'bank_reference_number',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'total_transactions_count' => 'integer',
        'total_gross_turnover' => 'decimal:2',
        'cash_turnover' => 'decimal:2',
        'qris_turnover' => 'decimal:2',
        'jukir_gross_share' => 'decimal:2',
        'cash_already_retained' => 'decimal:2',
        'bpjs_deduction' => 'decimal:2',
        'net_payable_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function jukir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jukir_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('disbursement_status', 'APPROVED_BY_DISHUB');
    }

    public function scopePaid($query)
    {
        return $query->where('disbursement_status', 'PAID_BY_KASDA');
    }
}
