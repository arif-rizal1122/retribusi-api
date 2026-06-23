<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BphtbSubmission extends Model
{
    protected $fillable = [
        'nib',
        'nop',
        'ppat_user_id',
        'reported_npop',
        'znt_applied',
        'final_npop',
        'status_flag',
        'billing_code',
    ];
}
