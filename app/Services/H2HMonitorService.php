<?php

namespace App\Services;

use App\Models\H2hMonitorLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class H2HMonitorService
{
    /**
     * Latensi transaksi NYATA per payment_channel dari tabel payment_requests.
     *
     * Menghitung rata-rata durasi (menit) antara created_at (VA/QR dibuat)
     * dan paid_at (VA/QR dibayar) untuk transaksi yang sudah lunas dalam
     * jendela waktu tertentu. Digunakan sebagai indikator pelengkap status
     * ping monitor (latency aplikasi/bisnis, bukan sekadar reachability).
     */
    public function realTransactionLatency(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        // Channel yang PERNAH punya transaksi lunas (menghindari channel asing).
        $everPaidChannels = DB::table('payment_requests')
            ->whereNotNull('paid_at')
            ->distinct()
            ->orderBy('payment_channel')
            ->pluck('payment_channel');

        // Transaksi lunas dalam jendela waktu.
        $rows = DB::table('payment_requests')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $since)
            ->get(['payment_channel', 'created_at', 'paid_at']);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->payment_channel][] = $row;
        }

        $perChannel = [];
        $totalCount = 0;
        $totalMinutes = 0.0;

        foreach ($everPaidChannels as $channel) {
            $items = $grouped[$channel] ?? [];
            $count = count($items);
            $sumMinutes = 0.0;

            foreach ($items as $item) {
                $minutes = ((int) strtotime((string) $item->paid_at) - (int) strtotime((string) $item->created_at)) / 60;
                $sumMinutes += max(0, $minutes);
            }

            $perChannel[] = [
                'payment_channel'  => $channel,
                'transactions'     => $count,
                'avg_paid_minutes' => $count > 0 ? round($sumMinutes / $count, 1) : null,
            ];

            $totalCount += $count;
            $totalMinutes += $sumMinutes;
        }

        return [
            'window_hours' => $hours,
            'summary'      => [
                'transactions'     => $totalCount,
                'avg_paid_minutes' => $totalCount > 0 ? round($totalMinutes / $totalCount, 1) : null,
            ],
            'per_channel'  => $perChannel,
        ];
    }

    /**
     * Jumlah pengecekan 'down' beruntun TERAKHIR untuk satu connector,
     * diurutkan dari checked_at terbaru (anti false-positive alert).
     */
    public function consecutiveDownCount(string $connector): int
    {
        $statuses = H2hMonitorLog::where('connector', $connector)
            ->orderByDesc('checked_at')
            ->limit(100)
            ->pluck('status');

        $count = 0;
        foreach ($statuses as $status) {
            if ($status === 'down') {
                $count++;
            } else {
                break;
            }
        }

        return $count;
    }

    /**
     * Waktu notifikasi 'down' terakhir untuk connector (berdasarkan flag
     * meta->notified pada baris log). null = belum pernah dinotifikasi.
     */
    public function lastNotifiedAt(string $connector): ?Carbon
    {
        $row = H2hMonitorLog::where('connector', $connector)
            ->where('meta->notified', true)
            ->orderByDesc('checked_at')
            ->first();

        return $row?->checked_at;
    }
}
