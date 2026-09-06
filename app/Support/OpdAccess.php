<?php

namespace App\Support;

use App\Models\User;
use App\Models\RetributionClassification;
use App\Models\SectorOpdMapping;

/**
 * OpdAccess — Ringkasan akses menu/layanan OPD (tupoksi) untuk frontend admin.
 *
 * Disuntikkan ke response login & /api/user sebagai key `access` agar sidebar
 * M-PAD bisa menyaring modul sesuai layanan yang benar-benar diampu OPD tsb.
 *
 * Sumber kebenaran:
 *  - retribution_classifications.opd_id  → layanan pendapatan milik OPD (pemungut/pengelola)
 *  - sector_opd_mappings (Dok 143)       → sektor usaha yang diampu OPD (pengawasan/kalkulator bisnis)
 */
class OpdAccess
{
    public static function for(?User $user): ?array
    {
        if (! $user || ! $user->opd_id) {
            return null;
        }

        $codes = RetributionClassification::where('opd_id', $user->opd_id)
            ->where('is_active', 1)
            ->orderBy('code')
            ->pluck('code')
            ->values()
            ->all();

        $hasSectorMapping = SectorOpdMapping::where('opd_id', $user->opd_id)->exists();

        $startsWith = static fn (string $c, string $prefix): bool => str_starts_with($c, $prefix);

        return [
            'opd_id' => (int) $user->opd_id,
            'has_owned_service' => count($codes) > 0,
            'codes' => $codes,
            'has_sector_mapping' => $hasSectorMapping,
            'flags' => [
                'pbb'      => in_array('PBB-P2', $codes, true),
                'bphtb'    => in_array('BPHTB', $codes, true),
                'aset'     => (bool) array_filter($codes, static fn ($c) => $startsWith($c, 'JU-ASET') || $startsWith($c, 'AS-') || $c === 'JU-PROD'),
                'ptsp'     => (bool) array_filter($codes, static fn ($c) => $startsWith($c, 'PZ-')),
                'rekreasi' => in_array('JU-REK', $codes, true),
            ],
        ];
    }
}
