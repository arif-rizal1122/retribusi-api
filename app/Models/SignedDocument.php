<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SignedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_id',
        'opd_id',
        'document_number',
        'file_path',
        'signature_hash',
        'sha256_checksum',
        'signed_by',
        'signer_name',
        'signer_nip',
        'signer_role',
        'signature_type', // ELECTRONIC_SEAL, PERSONAL_TTE, NUMERIC_TOKEN_TRANSITION
        'signed_at',
        'metadata',
        'verification_url',
        'status', // pending, signed, revoked
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the parent document model (Bill, EnforcementNotice, AssetRental, etc.)
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo('document', 'document_type', 'document_id');
    }

    /**
     * Get the user who signed the document
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * Get OPD related to document
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }
}
