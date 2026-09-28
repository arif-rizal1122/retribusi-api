<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        "taxpayer_id",
        "user_id",
        "phone",
        "channel",
        "type",
        "template_code",
        "reference_type",
        "reference_id",
        "message_preview",
        "status",
        "error_message",
        "idempotency_key",
        "gateway_message_id",
        "sent_at",
        "delivered_at",
        "read_at",
    ];

    protected $casts = [
        "sent_at" => "datetime",
        "delivered_at" => "datetime",
        "read_at" => "datetime",
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_code', 'code');
    }
}
