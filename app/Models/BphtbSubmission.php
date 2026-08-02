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
        'skb_number',
        'skb_issued_at',
        'skb_issued_by',
        'skb_qr_hash',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'skb_issued_at' => 'datetime',
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
        return $this->status === 'paid' || $this->status === 'skb_issued' || $this->status === 'verified';
    }
}
