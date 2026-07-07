<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        "taxpayer_id", "phone", "channel", "type",
        "reference_type", "reference_id",
        "message_preview", "status", "error_message", "sent_at",
    ];

    protected $casts = [
        "sent_at" => "datetime",
    ];

    public function taxpayer(): BelongsTo { return $this->belongsTo(Taxpayer::class); }
}
