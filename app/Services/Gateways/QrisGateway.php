<?php

namespace App\Services\Gateways;

use App\Services\Contracts\PaymentGatewayAdapter;
use Illuminate\Support\Str;

/**
 * QrisGateway
 *
 * Adapter untuk QRIS (EMVCo / standard merchant presented QR).
 * Implementasi simulasi; ganti dengan integrasi QRIS penyedia (GPN/PJSP)
 * tanpa mengubah business logic.
 */
class QrisGateway implements PaymentGatewayAdapter
{
    public function createPayment(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0);
        $ref = (string) ($params['reference'] ?? Str::random(12));

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

        return [
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
