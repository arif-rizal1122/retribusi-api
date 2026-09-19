<?php

namespace App\Support;

/** Presentation at the citizen boundary; never rewrites payment request storage. */
final class CitizenPaymentRequestStatus
{
    public static function normalize(?string $storedStatus): string
    {
        return match (strtolower(trim($storedStatus ?? ''))) {
            'draft' => 'draft',
            'pending', 'waiting', 'waiting_payment' => 'pending',
            'paid', 'success', 'settled', 'completed', 'complete' => 'paid',
            'processing', 'in_progress', 'pending_confirmation', 'processing_receipt' => 'processing_receipt',
            'expired', 'expire' => 'expired',
            'failed', 'failure', 'error', 'declined' => 'failed',
            'cancelled', 'canceled', 'void', 'voided' => 'cancelled',
            default => 'unknown',
        };
    }

    public static function isActive(?string $storedStatus): bool
    {
        return in_array(self::normalize($storedStatus), ['pending', 'processing_receipt'], true);
    }

    public static function label(string $status, bool $isSultra = false): string
    {
        return match ($status) {
            'pending' => $isSultra
                ? 'Menunggu pembayaran melalui channel Bank Sultra.'
                : 'Menunggu pembayaran melalui channel BRI.',
            'paid' => 'Pembayaran diterima.',
            'processing_receipt' => 'Pembayaran sedang diproses.',
            'expired' => 'Payment request telah kedaluwarsa.',
            'cancelled' => 'Payment request dibatalkan.',
            'failed' => 'Pembayaran gagal.',
            'draft' => 'Draft payment request.',
            default => 'Status pembayaran belum dapat dipastikan.',
        };
    }
}
