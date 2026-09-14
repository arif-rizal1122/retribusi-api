<?php

namespace App\Support;

/** Presentation at the citizen boundary; never rewrites stored billing/settlement status. */
final class CitizenBillStatus
{
    public static function normalize(
        ?string $storedStatus,
        bool $hasPendingClaim = false,
        bool $hasActiveRequest = false,
        bool $isPastDue = false,
    ): string {
        $status = match (strtolower(trim($storedStatus ?? ''))) {
            'paid', 'lunas', 'success', 'settled' => 'paid',
            'cancelled', 'canceled', 'void', 'voided' => 'cancelled',
            'expired' => 'expired',
            'disputed' => 'disputed',
            'pending_verification', 'pending_confirmation', 'processing_receipt' => 'pending_verification',
            'overdue', 'late' => 'overdue',
            'pending', 'unpaid' => 'pending',
            default => 'unknown',
        };

        // Claims and due dates can only refine known unpaid states.
        if (! self::canPay($status)) {
            return $status;
        }

        if ($hasPendingClaim || $hasActiveRequest) {
            return 'pending_verification';
        }

        return $isPastDue ? 'overdue' : $status;
    }

    public static function canPay(string $status): bool
    {
        return in_array($status, ['pending', 'overdue'], true);
    }

    public static function label(string $status, bool $hasActiveRequest = false): string
    {
        return match ($status) {
            'pending' => 'Menunggu pembayaran',
            'overdue' => 'Jatuh tempo',
            'paid' => 'Lunas',
            'pending_verification' => $hasActiveRequest
                ? 'Menunggu pembayaran BRIVA'
                : 'Menunggu verifikasi pembayaran',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
            'disputed' => 'Dalam sengketa',
            default => 'Status belum diketahui',
        };
    }
}
