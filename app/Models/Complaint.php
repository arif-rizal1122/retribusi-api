<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        "taxpayer_id",
        "name",
        "email",
        "phone",
        "category",
        "complaint_text",
        "rating",
        "suggestion_text",
        "attachments",
        "status",
        "admin_notes",
        "resolved_at",
        "resolved_by",
    ];

    protected $casts = [
        "attachments" => "array",
        "resolved_at" => "datetime",
    ];

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, "resolved_by");
    }
}
