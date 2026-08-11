<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BphtbSubmission extends Model
{
    protected $fillable = [
        'nib',
        'nop',
        'nik_wp',
        'nama_wp',
        'alamat_wp',
        'ppat_user_id',
        'reported_npop',
        'znt_applied',
        'final_npop',
        'status_flag',
        'billing_code',
        'status',
        'bill_id',
        'paid_at',
        'ntpd',
        'sspd_number',
        'sspd_issued_at',
        'sspd_issued_by',
        'sspd_qr_hash',
        'verified_at',
        'verified_by',
        'verified_notes',
        'splp_number',
        'splp_status',
        'splp_issued_at',
        'splp_notes',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'sspd_issued_at' => 'datetime',
        'verified_at' => 'datetime',
        'splp_issued_at' => 'datetime',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function documents()
    {
        return $this->hasMany(BphtbDocument::class, 'bphtb_submission_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->status === 'sspd_issued' || $this->status === 'verified';
    }

    public function isVerified(): bool
    {
        return in_array($this->status, ['verified', 'paid', 'sspd_issued']);
    }
}
