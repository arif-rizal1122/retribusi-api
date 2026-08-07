<?php

namespace App\Services\Payment\Snap;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menyimpan RAW request SNAP (BRI) ke file untuk analisis pola signature.
 * File: storage/logs/bri_capture/<timestamp>_<ip>.json
 */
class SnapRequestCapture
{
    private string $dir;

    public function __construct()
    {
        $this->dir = storage_path('logs/bri_capture');
        if (! is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function capture(Request $request, string $stage): void
    {
        try {
            $name = date('Ymd_His') . '_' . str_replace('.', '_', (string) $request->ip()) . '_' . $stage . '.json';
            $data = [
                'captured_at' => date('Y-m-d H:i:s T'),
                'stage' => $stage,
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => '/' . ltrim($request->path(), '/'),
                'full_url' => $request->fullUrl(),
                'headers' => $this->headers($request),
                'raw_body' => (string) $request->getContent(),
                'signature_received' => (string) $request->header('X-SIGNATURE'),
            ];
            file_put_contents($this->dir . '/' . $name, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('SNAP_CAPTURE_FAIL', ['err' => $e->getMessage()]);
        }
    }

    private function headers(Request $request): array
    {
        $keys = ['X-CLIENT-KEY', 'X-TIMESTAMP', 'X-SIGNATURE', 'X-PARTNER-ID', 'X-EXTERNAL-ID', 'Content-Type', 'Authorization', 'User-Agent'];
        $out = [];
        foreach ($keys as $k) {
            $v = $request->header($k);
            if ($v !== null) {
                $out[$k] = is_array($v) ? implode(',', $v) : (string) $v;
            }
        }
        return $out;
    }
}
