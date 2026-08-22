<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\H2hMonitorLog;
use App\Services\H2HMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class H2HMonitorController extends Controller
{
    /**
     * Status terakhir tiap skema H2H + uptime sederhana 24 jam + latensi
     * transaksi nyata dari payment_requests (read-only).
     */
    public function overview(): JsonResponse
    {
        $connectors = H2hMonitorLog::query()
            ->select('connector')
            ->distinct()
            ->orderBy('connector')
            ->pluck('connector');

        $since = now()->subHours(24);
        $rows = [];

        foreach ($connectors as $connector) {
            $latest = H2hMonitorLog::where('connector', $connector)
                ->orderByDesc('checked_at')
                ->first();

            $total = H2hMonitorLog::where('connector', $connector)
                ->where('checked_at', '>=', $since)
                ->count();

            $downCount = H2hMonitorLog::where('connector', $connector)
                ->where('checked_at', '>=', $since)
                ->where('status', 'down')
                ->count();

            $uptime = $total > 0 ? round((($total - $downCount) / $total) * 100, 1) : null;

            $rows[] = [
                'connector'     => $connector,
                'status'        => $latest?->status,
                'latency_ms'    => $latest?->latency_ms,
                'endpoint'      => $latest?->endpoint,
                'error_message' => $latest?->error_message,
                'checked_at'    => $latest?->checked_at?->toIso8601String(),
                'uptime_24h'    => $uptime,
                'checks_24h'    => $total,
            ];
        }

        $realTransactions = app(H2HMonitorService::class)->realTransactionLatency(24);

        return response()->json([
            'success'           => true,
            'data'              => $rows,
            'real_transactions' => $realTransactions,
            'meta'              => [
                'generated_at' => now()->toIso8601String(),
                'window_hours' => 24,
            ],
        ]);
    }

    /**
     * Riwayat monitoring 24 jam terakhir untuk satu connector (read-only).
     */
    public function history(Request $request, string $connector): JsonResponse
    {
        $logs = H2hMonitorLog::where('connector', $connector)
            ->where('checked_at', '>=', now()->subHours(24))
            ->orderByDesc('checked_at')
            ->limit((int) $request->query('limit', 100))
            ->get(['connector', 'status', 'latency_ms', 'error_message', 'endpoint', 'checked_at']);

        if ($logs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Belum ada data monitor untuk connector: {$connector}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $logs->map(fn ($l) => [
                'status'        => $l->status,
                'latency_ms'    => $l->latency_ms,
                'error_message' => $l->error_message,
                'endpoint'      => $l->endpoint,
                'checked_at'    => $l->checked_at?->toIso8601String(),
            ]),
            'meta' => [
                'connector'    => $connector,
                'window_hours' => 24,
            ],
        ]);
    }
}
