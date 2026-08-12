<?php

namespace App\Console\Commands;

use App\Models\H2hMonitorLog;
use App\Services\H2HNotifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MonitorH2HCommand extends Command
{
    protected $signature = 'monitor:h2h';

    protected $description = 'Cek ketersediaan endpoint H2H (GET ringan, timeout 3s) dan catat ke h2h_monitor_logs';

    /**
     * Daftar 12 skema H2H yang dimonitor.
     * Format: connector => [configKey|null, fallbackConfigKey|null, literalUrl|null]
     * Resolusi: configKey -> fallbackConfigKey -> literalUrl; null bila kosong semua.
     */
    protected array $schemas = [
        'bri_snap'       => ['snap.briva.base_url', null, 'https://apidev.bri.co.id'],
        'bank_sultra'    => ['snap.bank_sultra_aft.base_url', 'snap.bank_sultra.base_url', null],
        'bni'            => ['services.bni.base_url', null, null],
        'btn'            => ['services.btn.base_url', null, 'https://devapi.btn.co.id'],
        'mandiri'        => ['payment.drivers.mandiri.api_url', null, 'https://api-dev.bankmandiri.co.id'],
        'qris'           => ['services.qris.base_url', null, null],
        'bhumi'          => ['services.bhumi.base_url', null, null],
        'dukcapil'       => ['services.dukcapil.base_url', null, null],
        'pbb_bapenda'    => ['services.pbb_bapenda.base_url', null, 'http://103.182.72.241:8000/pospbb/Api_service'],
        'simpad'         => [null, null, 'http://103.182.72.241'],
        'wa_gateway'     => ['services.wa_gateway.url', null, 'http://localhost:5001'],
        'telegram_notif' => [null, null, 'https://api.telegram.org'],
    ];

    public function handle(): int
    {
        $summary = ['ok' => 0, 'slow' => 0, 'down' => 0, 'not_configured' => 0];
        $down = [];

        $this->info('Monitoring H2H — ' . now()->format('Y-m-d H:i:s'));

        foreach ($this->schemas as $connector => [$key, $altKey, $literal]) {
            $endpoint = $this->resolveEndpoint($key, $altKey, $literal);

            if ($endpoint === null) {
                $this->record($connector, 'not_configured', null, 'Endpoint belum dikonfigurasi', null);
                $summary['not_configured']++;
                $this->line(sprintf('  %-16s not_configured', $connector));
                continue;
            }

            [$status, $latencyMs, $error] = $this->ping($endpoint);
            $this->record($connector, $status, $latencyMs, $error, $endpoint);
            $summary[$status]++;
            $this->line(sprintf('  %-16s %-16s %s ms%s', $connector, $status, $latencyMs ?? '-', $error ? " ({$error})" : ''));

            if ($status === 'down') {
                $down[] = $connector;
            }
        }

        // Notifikasi ringan untuk skema down. Notifier default off (H2H_NOTIFY_TELEGRAM=false)
        // sehingga hanya menulis Log::warning — tidak spam Telegram.
        foreach ($down as $connector) {
            app(H2HNotifierService::class)->notifyAdmin(
                "H2H Monitor: {$connector} DOWN",
                'Endpoint tidak merespons pada ' . now()->format('Y-m-d H:i:s') . '.'
            );
        }

        $this->newLine();
        $this->info(sprintf(
            'Selesai: %d ok, %d slow, %d down, %d not_configured (total %d skema).',
            $summary['ok'],
            $summary['slow'],
            $summary['down'],
            $summary['not_configured'],
            count($this->schemas)
        ));

        return self::SUCCESS;
    }

    /**
     * Resolusi endpoint: configKey -> fallbackConfigKey -> literalUrl.
     * Mengembalikan null bila tidak ada sumber yang terkonfigurasi.
     */
    protected function resolveEndpoint(?string $key, ?string $altKey, ?string $literal): ?string
    {
        foreach ([$key, $altKey] as $k) {
            if ($k === null) {
                continue;
            }

            $value = config($k);

            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return $literal;
    }

    /**
     * GET ringan (TCP/HTTP ping) dengan timeout 3 detik — tanpa payload/auth,
     * aman untuk endpoint bank produksi.
     *
     * Klasifikasi:
     * - down  : koneksi gagal (DNS/timeout/refused/TLS) atau HTTP 5xx (server error)
     * - ok    : server merespons (2xx/3xx/4xx) dengan latency < 1000 ms
     * - slow  : server merespons dengan latency 1000–3000 ms
     * Catatan: 4xx dianggap reachable (endpoint butuh path/auth khusus), HTTP
     * status-nya tetap dicatat di error_message agar transparan.
     *
     * @return array{0: string, 1: ?int, 2: ?string} [status, latency_ms, error]
     */
    protected function ping(string $endpoint): array
    {
        $start = hrtime(true);

        try {
            $response = Http::timeout(3)->get($endpoint);
            $latencyMs = (int) round((hrtime(true) - $start) / 1_000_000);

            if ($response->serverError()) {
                // 5xx = server merespons tapi error — dianggap down (gagal).
                return ['down', $latencyMs, sprintf('HTTP %d', $response->status())];
            }

            if ($response->successful()) {
                return [$latencyMs < 1000 ? 'ok' : 'slow', $latencyMs, null];
            }

            // 3xx/4xx: host reachable (redirect/not-found/unauthorized) — nilai latency.
            $status = $latencyMs < 1000 ? 'ok' : 'slow';

            return [$status, $latencyMs, sprintf('HTTP %d', $response->status())];
        } catch (\Throwable $e) {
            $latencyMs = (int) round((hrtime(true) - $start) / 1_000_000);

            return ['down', $latencyMs, $e->getMessage()];
        }
    }

    protected function record(string $connector, string $status, ?int $latencyMs, ?string $error, ?string $endpoint): void
    {
        H2hMonitorLog::create([
            'connector'     => $connector,
            'status'        => $status,
            'latency_ms'    => $latencyMs,
            'error_message' => $error,
            'endpoint'      => $endpoint,
            'checked_at'    => now(),
        ]);
    }
}
