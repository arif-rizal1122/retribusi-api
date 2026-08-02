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
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function documents()
    {
        return $this->hasMany(BphtbDocument::class, 'bphtb_submission_id');
    }
}
