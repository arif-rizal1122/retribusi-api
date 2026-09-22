<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PbbMutation extends Model
{
    protected $fillable = [
        'ticket_no',
        'user_id',
        'mutation_type',
        'applicant_nik',
        'applicant_name',
        'applicant_phone',
        'applicant_address',
        'parent_nop',
        'status',
        'notes',
        'rejection_reason',
        'parent_land_area',
        'total_parent_debt',
        'proportional_debt_paid',
        'correction_data',
        'previous_taxpayer_nik',
        'previous_taxpayer_name',
        'new_taxpayer_nik',
        'new_taxpayer_name',
        'bphtb_ntpd_ref',
        'primary_target_nop',
        'merged_source_nops_json',
        'duplicate_nop_target',
        'cancellation_reason_code',
        'attachment_files',
        'survey_notes',
        'survey_photo_path',
        'survey_metadata',
        'assigned_officer_id',
        'verified_l1_by',
        'verified_l1_at',
        'verified_l2_by',
        'verified_l2_at',
        'approved_l3_by',
        'approved_l3_at',
        'metadata',
    ];

    protected $casts = [
        'correction_data' => 'array',
        'merged_source_nops_json' => 'array',
        'attachment_files' => 'array',
        'survey_metadata' => 'array',
        'metadata' => 'array',
        'parent_land_area' => 'decimal:2',
        'total_parent_debt' => 'decimal:2',
        'proportional_debt_paid' => 'decimal:2',
        'verified_l1_at' => 'datetime',
        'verified_l2_at' => 'datetime',
        'approved_l3_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function verifierL1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_l1_by');
    }

    public function verifierL2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_l2_by');
    }

    public function approverL3(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_l3_by');
    }

    public function splitItems(): HasMany
    {
        return $this->hasMany(PbbSplitItem::class, 'pbb_mutation_id')->orderBy('item_index');
    }
}
