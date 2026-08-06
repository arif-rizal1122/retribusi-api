<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentRequest extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PROCESSING_RECEIPT = 'processing_receipt';

    public const METHOD_BRI_VA = 'bri_va';
    public const METHOD_QRIS = 'qris';
    public const METHOD_OFFICER = 'officer';

    protected $fillable = [
        'token',
        'taxpayer_id',
        'method',
        'provider',
        'status',
        'amount',
        'admin_fee',
        'total_amount',
        'bill_ids',
        'bill_numbers',
        'va_number',
        'qris_string',
        'qr_payload',
        'external_id',
        'reference_number',
        'expired_at',
        'paid_at',
        'instructions',
        'can_refresh',
        'can_cancel',
        'receipts',
        'metadata',
    ];

    protected $casts = [
        'bill_ids' => 'array',
        'bill_numbers' => 'array',
        'instructions' => 'array',
        'receipts' => 'array',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'can_refresh' => 'boolean',
        'can_cancel' => 'boolean',
    ];

    protected $appends = [
        'status_label',
        'qr_data',
    ];

    protected static function booted()
    {
        static::creating(function (PaymentRequest $request) {
            if (empty($request->token)) {
                $request->token = static::generateToken();
            }
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = strtoupper('MPR-' . Str::random(20));
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft payment request',
            self::STATUS_PENDING => 'Menunggu pembayaran',
            self::STATUS_PAID => 'Pembayaran diterima',
            self::STATUS_EXPIRED => 'Payment request kedaluwarsa',
            self::STATUS_FAILED => 'Pembayaran gagal',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_PROCESSING_RECEIPT => 'Pembayaran sedang diproses',
            default => ucfirst($this->status),
        };
    }

    public function getQrDataAttribute(): ?string
    {
        return $this->qr_payload ?? ($this->token ? 'MPAD://pay/' . $this->token : null);
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expired_at && $this->expired_at->isPast()
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_DRAFT, self::STATUS_PROCESSING_RECEIPT]);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ]) || $this->isExpired();
    }

    public function taxpayer(): BelongsTo
    {
        return $this->belongsTo(Taxpayer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bills()
    {
        $ids = collect($this->bill_ids ?? [])->map(fn ($id) => (int) $id)->all();

        return Bill::whereIn('id', $ids)->get();
    }

    public function toApiArray(): array
    {
        $receipts = collect($this->receipts ?? []);

        return [
            'id' => (string) $this->id,
            'external_id' => $this->external_id ?: $this->reference_number ?: (string) $this->id,
            'token' => $this->token,
            'method' => $this->method,
            'provider' => $this->provider ?: ($this->method === self::METHOD_BRI_VA ? 'BRI' : null),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'bill_ids' => array_map('intval', $this->bill_ids ?? []),
            'bill_numbers' => $this->bill_numbers ?? [],
            'amount' => (float) $this->amount,
            'admin_fee' => (float) $this->admin_fee,
            'total_amount' => (float) $this->total_amount,
            'va_number' => $this->va_number,
            'qris_string' => $this->qris_string,
            'qr_payload' => $this->qr_payload,
            'expired_at' => $this->expired_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'instructions' => $this->instructions ?? [],
            'receipt_url' => $this->status === self::STATUS_PAID && $this->receipts ? (string) $this->id : null,
            'receipt_number' => $this->reference_number,
            'receipts' => $receipts->values()->all(),
            'reference_number' => $this->reference_number,
            'can_refresh' => (bool) $this->can_refresh,
            'can_cancel' => (bool) $this->can_cancel,
            'metadata' => $this->metadata ?? [],
        ];
    }
}
