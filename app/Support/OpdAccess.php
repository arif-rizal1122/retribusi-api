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

        $query = RetributionClassification::where(function ($q) use ($user) {
            $q->where('opd_id', $user->opd_id)
              ->orWhereHas('retributionType', function ($tq) use ($user) {
                  $tq->where('opd_id', $user->opd_id);
              });
        });

        if (\Illuminate\Support\Facades\Schema::hasColumn('retribution_classifications', 'is_active')) {
            $query->where('is_active', 1);
        }

        $codes = $query->orderBy('code')
            ->pluck('code')
            ->values()
            ->all();

        $hasSectorMapping = SectorOpdMapping::where('opd_id', $user->opd_id)->exists();

        $startsWith = static fn (string $c, string $prefix): bool => str_starts_with($c, $prefix);

        $isDishubAset = in_array($user->role, ['admin_aset_dishub', 'dishub_aset'], true)
            || in_array(($user->metadata['role_code'] ?? null), ['admin_aset_dishub', 'dishub_aset'], true)
            || str_contains(strtolower($user->email ?? ''), 'aset.dishub');

        $isPupr = (int) $user->opd_id === 9 || str_contains(strtolower($user->email ?? ''), 'pupr');

        $isPuprAset = in_array($user->role, ['admin_aset_pupr', 'pupr_aset', 'admin_pupr_aset'], true)
            || in_array(($user->metadata['role_code'] ?? null), ['admin_aset_pupr', 'pupr_aset', 'admin_pupr_aset'], true)
            || str_contains(strtolower($user->email ?? ''), 'aset.pupr')
            || $isPupr;

        $hasAset = (bool) array_filter($codes, static fn ($c) => $startsWith($c, 'JU-ASET') || $startsWith($c, 'AS-') || $c === 'JU-PROD') || $isDishubAset || $isPuprAset || $isPupr;

        return [
            'opd_id' => (int) $user->opd_id,
            'has_owned_service' => count($codes) > 0 || $isDishubAset || $isPuprAset,
            'codes' => $codes,
            'has_sector_mapping' => $hasSectorMapping,
            'flags' => [
                'pbb'         => in_array('PBB-P2', $codes, true),
                'bphtb'       => in_array('BPHTB', $codes, true),
                'aset'        => $hasAset,
                'ptsp'        => (bool) array_filter($codes, static fn ($c) => $startsWith($c, 'PZ-')),
                'rekreasi'    => in_array('JU-REK', $codes, true),
                'perikanan'   => in_array('JU-PEL', $codes, true) || (int) $user->opd_id === 12,
                'dishub'      => (int) $user->opd_id === 2,
                'dishub_aset' => $isDishubAset,
                'pupr'        => (int) $user->opd_id === 9,
                'pupr_aset'   => $isPuprAset,
            ],
        ];
    }
}
