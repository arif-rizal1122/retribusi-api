<?php

namespace App\Services\Gateways;

use App\Models\BankConfig;
use App\Models\PaymentGatewayLog;
use App\Services\Contracts\PaymentGatewayAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BriVaGateway
 *
 * Adapter untuk Virtual Account BRI (BRIVA). Konfigurasi (prefix VA, fee,
 * sandbox) dibaca dari tabel produksi `bank_configs`. Setiap pemanggilan
 * dicatat ke `payment_gateway_logs` untuk audit.
 *
 * Saat ini implementasi simulasi; ganti dengan koneksi BRI SNAP API
 * tanpa mengubah controller/service lain.
 */
class BriVaGateway implements PaymentGatewayAdapter
{
    protected function bankConfig(): ?BankConfig
    {
        return BankConfig::active()
            ->where('tipe_driver', 'bri')
            ->orWhere('nama_singkat', 'BRI')
            ->first();
    }

    protected function log(string $endpoint, array $payloadIn, array $payloadOut): void
    {
        try {
            PaymentGatewayLog::create([
                'endpoint' => 'bri_va/' . $endpoint,
                'method' => 'POST',
                'payload_in' => $payloadIn,
                'payload_out' => $payloadOut,
                'ip_address' => request()->ip(),
                'status_code' => 200,
            ]);
        } catch (\Throwable $e) {
            Log::warning('BRI VA gateway log gagal', ['error' => $e->getMessage()]);
        }
    }

    public function createPayment(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0);
        $ref = (string) ($params['reference'] ?? Str::random(10));

        $config = $this->bankConfig();
        $prefix = $config?->kode_va_prefix ?: '9880';
        $feePercent = (float) ($config?->fee_persen ?? 0);

        $vaNumber = $prefix . substr(preg_replace('/\D/', '', $ref), 0, 10) . rand(0, 9);

        $result = [
            'external_id' => 'BRI-' . strtoupper($ref),
            'va_number' => $vaNumber,
            'qris_string' => null,
            'instructions' => [
                'Buka aplikasi m-banking BRI lalu pilih menu BRIVA',
                'Masukkan nomor virtual account di atas',
                'Konfirmasi nominal dan selesaikan pembayaran',
                'Status pembayaran diperbarui otomatis setelah transfer diterima',
            ],
            'expires_in_minutes' => 1440,
            'provider' => 'BRI',
            'bank_config_id' => $config?->id,
            'fee_percent' => $feePercent,
        ];

        $this->log('createPayment', $params, $result);

        return $result;
    }

    public function checkStatus(string $externalId): array
    {
        $result = [
            'status' => 'pending',
            'paid_at' => null,
            'reference_number' => null,
        ];

        $this->log('checkStatus', ['external_id' => $externalId], $result);

        return $result;
    }
}
