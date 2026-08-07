<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class H2hConnectorLog extends Model
{
    protected $fillable = [
        'connector',
        'status',
        'endpoint',
        'message',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Catat log konektor H2H dengan dedup sederhana:
     * maksimal 1 record per (connector + status) per hari agar tidak spam.
     * Status penting (fallback/failed) memicu notifikasi admin (default off).
     */
    public static function record(string $connector, string $status, ?string $endpoint = null, ?string $message = null, array $meta = []): ?self
    {
        $already = static::where('connector', $connector)
            ->where('status', $status)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if ($already) {
            return null;
        }

        $log = static::create([
            'connector' => $connector,
            'status'    => $status,
            'endpoint'  => $endpoint,
            'message'   => $message,
            'meta'      => $meta,
        ]);

        if (in_array($status, ['fallback', 'failed'], true)) {
            app(\App\Services\H2HNotifierService::class)->notifyAdmin(
                "H2H {$connector}: {$status}",
                ($message ?? 'Status konektor berubah.') . "\nEndpoint: " . ($endpoint ?? '-')
            );
        }

        return $log;
    }
}
