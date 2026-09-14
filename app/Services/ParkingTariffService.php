<?php

namespace App\Services;

use App\Models\ParkingLocation;

/**
 * Source-of-truth tarif retribusi parkir Dishub sesuai Perda No. 1 Tahun 2024.
 * Frontend TIDAK diperbolehkan menjadi sumber kebenaran nominal.
 */
class ParkingTariffService
{
    /** Tarif tetap (non-zonasi). */
    public const RATE_TRUK_BUS = 5000;
    public const RATE_INAP_TRUK = 25000;          // Pasal 88 (penitipan inap dermaga)

    /** Tarif tambat labuh kapal rakyat per 24 jam (Pasal 91). */
    public const PROXY_GT_RATES = [
        'proxy_gt_1' => 1000,   // < 5 GT  (Gol I: Katinting)
        'proxy_gt_2' => 3000,   // 5-10 GT (Gol II: Speedboat)
        'proxy_gt_3' => 5000,   // 11-20GT (Gol III: KLM Sedang)
        'proxy_gt_4' => 10000,  // > 20 GT (Gol IV: KM Besar)
    ];

    /** Bagi hasil. */
    public const SPLIT_RKUD_PERCENT = 70;
    public const SPLIT_JUKIR_PERCENT = 30;

    public function isHarborProxy(string $vehicleType): bool
    {
        return array_key_exists($vehicleType, self::PROXY_GT_RATES);
    }

    public function unitRate(ParkingLocation $location, string $vehicleType): int
    {
        if ($this->isHarborProxy($vehicleType)) {
            return self::PROXY_GT_RATES[$vehicleType];
        }

        return match ($vehicleType) {
            'r4' => (int) $location->rate_r4,
            'truk_bus' => self::RATE_TRUK_BUS,
            'inap_truk' => self::RATE_INAP_TRUK,
            default => (int) $location->rate_r2,
        };
    }

    public function amountFor(ParkingLocation $location, string $vehicleType, int $durationDays = 1): float
    {
        $unit = $this->unitRate($location, $vehicleType);
        $days = max(1, $durationDays);

        // Durasi hanya dikalikan untuk tambat labuh kapal (per 24 jam).
        $amount = $this->isHarborProxy($vehicleType) ? $unit * $days : $unit;

        return (float) $amount;
    }

    /** Bagi 70% RKUD / 30% Jukir. */
    public function split(float $amount): array
    {
        $rkud = (int) round($amount * self::SPLIT_RKUD_PERCENT / 100);

        return [
            'rkud' => $rkud,
            'jukir' => (int) round($amount - $rkud),
        ];
    }
}