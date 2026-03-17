<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Auditable;

class Verification extends Model
{
    use Auditable;

    protected $fillable = [
        'opd_id',
        'user_id',
        'document_number',
        'taxpayer_name',
        'type',
        'amount',
        'status',
        'notes',
        'verifier_id',
        'submitted_at',
        'verified_at',
        'proof_file_url',
        'taxpayer_id',
        'tax_object_id',
        'is_assessment_final',
        'assessed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'assessed_at' => 'datetime',
        'is_assessment_final' => 'boolean',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function taxObject(): BelongsTo
    {
        return $this->belongsTo(TaxObject::class);
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }
}
