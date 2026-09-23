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

        // Ownership klasifikasi = retribution_classifications.opd_id SAJA.
        // Klausa orWhereHas('retributionType') DIHAPUS: retribution_type adalah
        // tipe PAYUNG lintas-OPD (mis. id 106 "Retribusi Jasa Usaha" milik Bapenda),
        // sehingga orWhereHas meloloskan klasifikasi milik OPD LAIN (mis. JU-PEL
        // opd_id=10 Dinas Perikanan) ke daftar Bapenda.
        $query = RetributionClassification::where('opd_id', $user->opd_id);

        if (\Illuminate\Support\Facades\Schema::hasColumn('retribution_classifications', 'is_active')) {
            $query->where('is_active', 1);
        }

        $codes = $query->orderBy('code')
            ->pluck('code')
            ->values()
            ->all();

        $hasSectorMapping = SectorOpdMapping::where('opd_id', $user->opd_id)->exists();

        $startsWith = static fn (string $c, string $prefix): bool => str_starts_with($c, $prefix);

        $opd = $user->opd ?: \App\Models\Opd::find($user->opd_id);
        $opdCode = strtoupper($opd?->code ?? $user->opd?->code ?? '');
        $opdName = strtolower($opd?->name ?? $user->opd?->name ?? '');
        $email = strtolower($user->email ?? '');
        $opdId = (int) $user->opd_id;

        $isBapenda = in_array($opdCode, ['BAPENDA', 'DISPENDA'], true)
            || str_contains($opdName, 'pendapatan daerah')
            || str_contains($email, 'bapenda');

        $isDishubAset = ! $isBapenda && (
            in_array($user->role, ['admin_aset_dishub', 'dishub_aset'], true)
            || in_array(($user->metadata['role_code'] ?? null), ['admin_aset_dishub', 'dishub_aset'], true)
            || str_contains($email, 'aset.dishub')
        );

        $isPupr = ! $isBapenda && (
            in_array($opdCode, ['PUPR', 'DINAS_PUPR', 'PEKERJAAN_UMUM'], true)
            || str_contains($opdName, 'pekerjaan umum')
            || str_contains($email, 'pupr')
        );

        $isPuprAset = ! $isBapenda && (
            in_array($user->role, ['admin_aset_pupr', 'pupr_aset', 'admin_pupr_aset'], true)
            || in_array(($user->metadata['role_code'] ?? null), ['admin_aset_pupr', 'pupr_aset', 'admin_pupr_aset'], true)
            || str_contains($email, 'aset.pupr')
            || $isPupr
        );

        $isDishub = ! $isBapenda && (
            in_array($opdCode, ['DISHUB', 'DINAS_PERHUBUNGAN', 'PERHUBUNGAN'], true)
            || str_contains($opdName, 'perhubungan')
            || str_contains($email, 'dishub')
        );

        $isPerikanan = ! $isBapenda && (
            in_array($opdCode, ['DINAS_PERIKANAN', 'DISKAN', 'PERIKANAN'], true)
            || str_contains($opdName, 'perikanan')
            || str_contains($email, 'perikanan')
        );

        $isPariwisata = ! $isBapenda && (
            in_array($opdCode, ['PARIWISATA', 'DISPAR'], true)
            || str_contains($opdName, 'pariwisata')
            || str_contains($email, 'pariwisata')
            || in_array('JU-REK', $codes, true)
        );

        $isPtsp = ! $isBapenda && (
            in_array($opdCode, ['DPMPTSP', 'PTSP'], true)
            || str_contains($opdName, 'penanaman modal')
            || str_contains($email, 'ptsp')
            || str_contains($email, 'dpmptsp')
            || (bool) array_filter($codes, static fn ($c) => $startsWith($c, 'PZ-'))
        );

        $isDisperindag = ! $isBapenda && (
            in_array($opdCode, ['DISPERINDAG', 'PERINDAG', 'DINAS_PERDAGANGAN', 'DISPERINDAGKOP', 'PERDAGINKOP'], true)
            || str_contains($opdName, 'perdagangan')
            || str_contains($opdName, 'perindustrian')
            || str_contains($email, 'disperindag')
            || in_array('RET-PSR', $codes, true)
            || in_array('JU-TKU', $codes, true)
        );

        $hasAset = (bool) array_filter($codes, static fn ($c) => $startsWith($c, 'JU-ASET') || $startsWith($c, 'AS-') || $c === 'JU-PROD') || $isDishubAset || $isPuprAset || $isPupr;

        $taxDomain = \App\Support\TaxDomainHelper::getDomain($user);

        return [
            'opd_id' => $opdId,
            'is_bapenda' => $isBapenda,
            'tax_domain' => $taxDomain,
            'tax_domain_name' => \App\Support\TaxDomainHelper::DOMAIN_CONFIG[$taxDomain]['name'] ?? 'Semua Bidang Pajak',
            'has_owned_service' => count($codes) > 0 || $isDishubAset || $isPuprAset || $isDisperindag,
            'codes' => $codes,
            'has_sector_mapping' => $hasSectorMapping,
            'flags' => [
                'pbb'         => in_array('PBB-P2', $codes, true) || ($isBapenda && ($taxDomain === \App\Support\TaxDomainHelper::DOMAIN_ALL || $taxDomain === \App\Support\TaxDomainHelper::DOMAIN_WILAYAH_1)),
                'bphtb'       => in_array('BPHTB', $codes, true) || ($isBapenda && ($taxDomain === \App\Support\TaxDomainHelper::DOMAIN_ALL || $taxDomain === \App\Support\TaxDomainHelper::DOMAIN_WILAYAH_1)),
                'pbjt'        => $isBapenda && ($taxDomain === \App\Support\TaxDomainHelper::DOMAIN_ALL || $taxDomain === \App\Support\TaxDomainHelper::DOMAIN_WILAYAH_2),
                'aset'        => $hasAset,
                'ptsp'        => $isPtsp,
                'rekreasi'    => $isPariwisata,
                'perikanan'   => $isPerikanan,
                'dishub'      => $isDishub,
                'dishub_aset' => $isDishubAset,
                'pupr'        => $isPupr,
                'pupr_aset'   => $isPuprAset,
                'disperindag' => $isDisperindag,
                'pasar'       => $isDisperindag,
            ],
        ];
    }
}
