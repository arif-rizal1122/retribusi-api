<?php

namespace App\Services;

use App\Models\ParkingSession;

/**
 * Membangun thermal_print_payload untuk struk Bluetooth 58mm (ESC/POS Dishub).
 * Dasar hukum (legal_notice) selalu mengacu Perda No. 1 Tahun 2024.
 */
class ParkingReceiptBuilder
{
    public function build(ParkingSession $session): array
    {
        $payload = [
            'header' => 'PEMERINTAH KOTA BAUBAU',
            'sub_header' => 'DINAS PERHUBUNGAN',
            'location_name' => $session->location?->name ?? '-',
            'receipt_no' => $this->receiptNo($session),
            'datetime' => $session->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'vehicle_type' => strtoupper((string) $session->vehicle_type),
            'plate_hint' => $session->plate_hint ?? '-',
            'amount_total' => (float) $session->amount,
            'qr_verification_url' => $this->verificationUrl($session),
            'footer_notice' => 'MINTA STRUK RESMI M-PAD',
            'reward_notice' => 'Simpan struk untuk klaim hadiah Gebyar Struk Parkir',
            'legal_notice' => 'Sesuai Perda No. 1 Tahun 2024 tentang PDRD Kota Baubau.',
        ];

        if ($session->duration_days && $session->duration_days > 1) {
            $payload['duration_days'] = $session->duration_days;
        }

        return $payload;
    }

    public function receiptNo(ParkingSession $session): string
    {
        return 'MPD-PRK-' . now()->format('Ymd') . '-' . str_pad((string) $session->id, 5, '0', STR_PAD_LEFT);
    }

    private function verificationUrl(ParkingSession $session): string
    {
        return rtrim((string) config('app.url'), '/') . '/verify/parking/' . $session->id;
    }
}