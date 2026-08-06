<?php

namespace App\Services\Gateways;

use App\Services\Contracts\PaymentGatewayAdapter;
use Illuminate\Support\Str;

/**
 * BriVaGateway
 *
 * Adapter untuk Virtual Account BRI (BRIVA).
 * Saat ini implementasi simulasi; ganti dengan koneksi BRI SNAP API
 * tanpa mengubah controller/service lain.
 */
class BriVaGateway implements PaymentGatewayAdapter
{
    public function createPayment(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0);
        $ref = (string) ($params['reference'] ?? Str::random(10));

        return [
            'external_id' => 'BRI-' . strtoupper($ref),
            'va_number' => '9880' . substr(preg_replace('/\D/', '', $ref), 0, 10) . rand(0, 9),
            'qris_string' => null,
            'instructions' => [
                'Buka aplikasi m-banking BRI lalu pilih menu BRIVA',
                'Masukkan nomor virtual account di atas',
                'Konfirmasi nominal dan selesaikan pembayaran',
                'Status pembayaran diperbarui otomatis setelah transfer diterima',
            ],
            'expires_in_minutes' => 1440,
        ];
    }

    public function checkStatus(string $externalId): array
    {
        return [
            'status' => 'pending',
            'paid_at' => null,
            'reference_number' => null,
        ];
    }
}
