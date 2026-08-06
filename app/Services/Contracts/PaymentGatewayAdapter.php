<?php

namespace App\Services\Contracts;

/**
 * PaymentGatewayAdapter
 *
 * Kontrak untuk mengintegrasikan payment gateway produksi (BRI API, QRIS, dll)
 * tanpa mengubah business logic. Tambahkan adapter baru tanpa menyentuh service lain.
 */
interface PaymentGatewayAdapter
{
    /**
     * Buat instruksi pembayaran di gateway.
     *
     * @param array $params [reference, amount, taxpayer_name, description]
     * @return array ['external_id' => string, 'va_number' => ?string, 'qris_string' => ?string, 'instructions' => string[]]
     */
    public function createPayment(array $params): array;

    /**
     * Cek status pembayaran di gateway.
     *
     * @return array ['status' => 'pending|paid|expired|failed', 'paid_at' => ?string, 'reference_number' => ?string]
     */
    public function checkStatus(string $externalId): array;
}
