<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new \App\Models\Scopes\RetributionTypeScope);
    }

    protected $fillable = [
        'user_id',
        'taxpayer_id',
        'tax_object_id',
        'spot_check_id',
        'opd_id',
        'retribution_type_id',
        'retribution_classification_id',
        'bill_number',
        'amount',
        'status',
        'period',
        'period_start',
        'period_end',
        'metadata',
        'due_date',
        'penalty_amount',
        'penalty_type',
        'fixed_fine_amount',
        'surcharge_amount',
        'waived_penalty_amount',
        'admin_fee',
        'postponed_at',
        'reason_postponed',
        'bank_code',
        'expiry_time',
        'penalty_at_payment',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'json',
        'penalty_amount' => 'decimal:2',
        'fixed_fine_amount' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'waived_penalty_amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'postponed_at' => 'datetime',
        'expiry_time' => 'datetime',
        'penalty_at_payment' => 'decimal:2',
    ];

    protected $appends = [
        'total_amount',
        'is_signed',
    ];

    public function getStatusAttribute($value): string
    {
        if ($value === 'pending' && $this->due_date && $this->due_date->isPast()) {
            return 'overdue';
        }
        
        // Handle variations between 'paid' and 'lunas' for frontend consistency
        if ($value === 'paid') {
            return 'lunas';
        }

        return $value;
    }

    public function getIsSignedAttribute(): bool
    {
        return $this->signedDocuments()->where('status', 'signed')->exists();
    }

    public function signedDocuments(): HasMany
    {
        return $this->hasMany(SignedDocument::class, 'document_id')
            ->where('document_type', self::class);
    }

    public function getTotalAmountAttribute(): float
    {
        $basePenalty = (float) $this->penalty_amount + (float) $this->fixed_fine_amount + (float) $this->surcharge_amount;
        $effectivePenalty = max(0, $basePenalty - (float) $this->waived_penalty_amount);
        
        return (float) $this->amount + (float) $this->admin_fee + $effectivePenalty;
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function retributionType(): BelongsTo
    {
        return $this->belongsTo(RetributionType::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(RetributionClassification::class, 'retribution_classification_id');
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function spotCheck(): BelongsTo
    {
        return $this->belongsTo(SpotCheck::class);
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(PenaltyWaiver::class);
    }
}
