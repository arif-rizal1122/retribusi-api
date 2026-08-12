<?php

namespace App\Console\Commands;

use App\Models\H2hMonitorLog;
use App\Services\H2HMonitorService;
use App\Services\H2HNotifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorH2HCommand extends Command
{
    protected $signature = 'monitor:h2h
        {--once=1 : Jalankan satu siklus lalu keluar (default true untuk cron). Gunakan --once=0 untuk mode daemon loop 5 menit}';

    protected $description = 'Cek ketersediaan endpoint H2H (GET ringan) dan catat ke h2h_monitor_logs. Ambang baca dari config/h2h_monitor.php. Gunakan -v/--verbose untuk output detail per connector.';

    public function handle(): int
    {
        $runOnce = (bool) $this->option('once');

        do {
            try {
                $this->runCycle();
            } catch (\Throwable $e) {
                $this->error('Error siklus monitor: ' . $e->getMessage());
                Log::error('MonitorH2H cycle error', ['error' => $e->getMessage()]);
            }

            if (! $runOnce) {
                $this->line('  ... tidur 300 detik sebelum siklus berikutnya.');
                sleep(300);
            }
        } while (! $runOnce);

        return self::SUCCESS;
    }

    /**
     * Satu siklus pengecekan 12 skema H2H + notifikasi anti-spam.
     */
    protected function runCycle(): void
    {
        $thresholds = config('h2h_monitor.thresholds', []);
        $okMs       = (int) ($thresholds['latency_ok_ms'] ?? 1000);
        $slowMs     = (int) ($thresholds['latency_slow_ms'] ?? 3000);
        $timeout    = (int) ($thresholds['timeout_ms'] ?? 3);

        $alertCfg     = config('h2h_monitor.alert', []);
        $downAfter    = (int) ($alertCfg['down_after_consecutive'] ?? 2);
        $cooldownMin  = (int) ($alertCfg['cooldown_minutes'] ?? 10);

        $connectors = config('h2h_monitor.connectors', []);
        $summary = ['ok' => 0, 'slow' => 0, 'down' => 0, 'not_configured' => 0];
        $verbose = (bool) $this->option('verbose');

        $this->info('Monitoring H2H — ' . now()->format('Y-m-d H:i:s'));

        foreach ($connectors as $connector => $conf) {
            $label    = $conf['label'] ?? $connector;
            $endpoint = $this->resolveEndpoint($conf);

            if ($endpoint === null) {
                $this->record($connector, 'not_configured', null, 'Endpoint belum dikonfigurasi', null);
                $summary['not_configured']++;
                if ($verbose) {
                    $this->line(sprintf('  %-16s %-22s not_configured', $connector, $label));
                }
                continue;
            }

            [$status, $latencyMs, $error] = $this->ping($endpoint, $timeout, $okMs, $slowMs);
            $log = $this->record($connector, $status, $latencyMs, $error, $endpoint);
            $summary[$status]++;

            if ($verbose) {
                $this->line(sprintf(
                    '  %-16s %-22s %-16s %s ms%s',
                    $connector,
                    $label,
                    $status,
                    $latencyMs ?? '-',
                    $error ? " ({$error})" : ''
                ));
            }

            if ($status === 'down') {
                $consecutive = app(H2HMonitorService::class)->consecutiveDownCount($connector);

                if ($verbose) {
                    $this->line(sprintf('  %-16s   consecutive down: %d (ambang notif: %d)', '', $consecutive, $downAfter));
                }

                if ($consecutive >= $downAfter && $this->canNotify($connector, $cooldownMin)) {
                    app(H2HNotifierService::class)->notifyAdmin(
                        "H2H Monitor: {$connector} DOWN",
                        'Endpoint tidak merespons pada ' . now()->format('Y-m-d H:i:s')
                            . " (consecutive: {$consecutive}, endpoint: {$endpoint})."
                            . ($error ? "\nDetail: {$error}" : '')
                    );

                    if ($log) {
                        $meta = (array) ($log->meta ?? []);
                        $meta['notified'] = true;
                        $meta['notified_at'] = now()->toIso8601String();
                        $log->update(['meta' => $meta]);
                    }

                    if ($verbose) {
                        $this->line(sprintf('  %-16s   >> notifikasi dikirim (consecutive %d >= %d)', '', $consecutive, $downAfter));
                    }
                } elseif ($verbose) {
                    $this->line(sprintf('  %-16s   >> notifikasi dilewati (cooldown/ambang belum terpenuhi)', ''));
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Selesai: %d ok, %d slow, %d down, %d not_configured (total %d skema).',
            $summary['ok'],
            $summary['slow'],
            $summary['down'],
            $summary['not_configured'],
            count($connectors)
        ));
    }

    /**
     * Resolusi endpoint dari config connector: config_key -> fallback_key -> literal_url.
     * Mengembalikan null bila tidak ada sumber yang terkonfigurasi.
     */
    protected function resolveEndpoint(array $conf): ?string
    {
        foreach ([$conf['config_key'] ?? null, $conf['fallback_key'] ?? null] as $k) {
            if ($k === null) {
                continue;
            }

            $value = config($k);

            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return $conf['literal_url'] ?? null;
    }

    /**
     * GET ringan (TCP/HTTP ping) dengan timeout 3 detik — tanpa payload/auth,
     * aman untuk endpoint bank produksi.
     *
     * Klasifikasi:
     * - down  : koneksi gagal (DNS/timeout/refused/TLS) atau HTTP 5xx (server error)
     * - ok    : server merespons (2xx/3xx/4xx) dengan latency < ambang ok
     * - slow  : server merespons dengan latency di antara ok..slow
     *
     * @return array{0: string, 1: ?int, 2: ?string} [status, latency_ms, error]
     */
    protected function ping(string $endpoint, int $timeout, int $okMs, int $slowMs): array
    {
        $start = hrtime(true);

        try {
            $response = Http::timeout($timeout)->get($endpoint);
            $latencyMs = (int) round((hrtime(true) - $start) / 1_000_000);

            if ($response->serverError()) {
                // 5xx = server merespons tapi error — dianggap down (gagal).
                return ['down', $latencyMs, sprintf('HTTP %d', $response->status())];
            }

            if ($response->successful()) {
                return [$latencyMs < $okMs ? 'ok' : 'slow', $latencyMs, null];
            }

            // 3xx/4xx: host reachable (redirect/not-found/unauthorized) — nilai latency.
            $status = $latencyMs < $okMs ? 'ok' : 'slow';

            return [$status, $latencyMs, sprintf('HTTP %d', $response->status())];
        } catch (\Throwable $e) {
            $latencyMs = (int) round((hrtime(true) - $start) / 1_000_000);
            $message   = $e->getMessage();

            if ($this->isDnsFailure($endpoint, $message)) {
                $message .= ' | DNS tidak resolve (kemungkinan domain sandbox/tidak aktif)';
            }

            return ['down', $latencyMs, $message];
        }
    }

    /**
     * Deteksi kegagalan DNS: pesan exception menyebut resolve/DNS, atau
     * hostname tidak bisa di-resolve via gethostbynamel. Digunakan agar
     * error_message jelas (domain sandbox mati) bukan bug monitor.
     */
    protected function isDnsFailure(string $endpoint, string $errorMessage): bool
    {
        if (preg_match('/resolve|dns/i', $errorMessage)) {
            return true;
        }

        $host = parse_url($endpoint, PHP_URL_HOST);

        return $host !== null && $host !== '' && gethostbynamel($host) === false;
    }

    /**
     * Cek cooldown notifikasi: true bila belum pernah notif, atau notif
     * terakhir lebih lama dari cooldown_minutes (anti spam).
     */
    protected function canNotify(string $connector, int $cooldownMinutes): bool
    {
        $last = app(H2HMonitorService::class)->lastNotifiedAt($connector);

        if ($last === null) {
            return true;
        }

        return $last->lte(now()->subMinutes($cooldownMinutes));
    }

    protected function record(string $connector, string $status, ?int $latencyMs, ?string $error, ?string $endpoint): H2hMonitorLog
    {
        return H2hMonitorLog::create([
            'connector'     => $connector,
            'status'        => $status,
            'latency_ms'    => $latencyMs,
            'error_message' => $error,
            'endpoint'      => $endpoint,
            'checked_at'    => now(),
        ]);
    }
}
