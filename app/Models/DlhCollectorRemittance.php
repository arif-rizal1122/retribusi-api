<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DlhCollectorRemittance extends Model
{
    use HasFactory;

    protected $table = 'dlh_collector_remittances';

    protected $fillable = [
        'remittance_number',
        'collector_user_id',
        'opd_id',
        'period_month',
        'period_year',
        'total_tickets_issued',
        'total_amount_collected',
        'remitted_amount',
        'remittance_date',
        'bank_reference_number',
        'sts_proof_url',
        'status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'total_tickets_issued' => 'integer',
        'total_amount_collected' => 'float',
        'remitted_amount' => 'float',
        'remittance_date' => 'date',
        'verified_at' => 'datetime',
        'notes' => 'array',
    ];

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(DlhMarketTrashTicket::class, 'remittance_id');
    }
}
