<?php

namespace App\Services\Gateways;

use App\Models\BankConfig;
use App\Models\PaymentGatewayLog;
use App\Services\Contracts\PaymentGatewayAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * QrisGateway
 *
 * Adapter untuk QRIS (EMVCo / standard merchant presented QR). Konfigurasi
 * dibaca dari `bank_configs` (driver qris) dan pemanggilan dicatat ke
 * `payment_gateway_logs` untuk audit.
 *
 * Implementasi simulasi; ganti dengan integrasi QRIS penyedia (GPN/PJSP)
 * tanpa mengubah business logic.
 */
class QrisGateway implements PaymentGatewayAdapter
{
    protected function bankConfig(): ?BankConfig
    {
        return BankConfig::active()
            ->where('tipe_driver', 'qris')
            ->orWhere('nama_singkat', 'QRIS')
            ->first();
    }

    protected function log(string $endpoint, array $payloadIn, array $payloadOut): void
    {
        try {
            PaymentGatewayLog::create([
                'endpoint' => 'qris/' . $endpoint,
                'method' => 'POST',
                'payload_in' => $payloadIn,
                'payload_out' => $payloadOut,
                'ip_address' => request()->ip(),
                'status_code' => 200,
            ]);
        } catch (\Throwable $e) {
            Log::warning('QRIS gateway log gagal', ['error' => $e->getMessage()]);
        }
    }

    public function createPayment(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0);
        $ref = (string) ($params['reference'] ?? Str::random(12));

        $config = $this->bankConfig();
        $feePercent = (float) ($config?->fee_persen ?? 0);

        $amountString = str_pad((string) round($amount * 100), 12, '0', STR_PAD_LEFT);

        // Standard QRIS payload (EMVCo) — contoh struktur merchant presented QR
        $qrisString = '000201010211'
            . '2658' . '00' . 'ID.ID.MPAD.OFFICER.' . strtoupper(Str::random(20))
            . '52040000'
            . '5303360'
            . '54' . strlen($amountString) . $amountString
            . '5802ID'
            . '59' . str_pad((string) strlen('M-PAD KOTA BAUBAU'), 2, '0', STR_PAD_LEFT) . 'M-PAD KOTA BAUBAU'
            . '60' . '11' . 'BAUBAU'
            . '6304'
            . '0000';

        $result = [
            'external_id' => 'QRIS-' . strtoupper($ref),
            'va_number' => null,
            'qris_string' => $qrisString,
            'instructions' => [
                'Buka aplikasi e-wallet / m-banking yang mendukung QRIS',
                'Pilih menu Scan QR / QRIS',
                'Pindai kode QR yang ditampilkan petugas',
                'Konfirmasi nominal dan selesaikan pembayaran',
            ],
            'expires_in_minutes' => 1440,
            'provider' => 'QRIS',
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
