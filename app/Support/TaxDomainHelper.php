<?php

namespace App\Support;

use App\Models\Taxpayer;
use App\Models\User;

/**
 * TaxDomainHelper — Pemisah Tupoksi Bidang Pajak Daerah Kota Baubau
 *
 * Mengatur pembagian wilayah dan kewenangan antara:
 * - Bidang Pajak Daerah Wilayah I: PBB-P2, BPHTB, Reklame, MBLB, Walet, Opsen
 * - Bidang Pajak Daerah Wilayah II: PBJT (Makanan/Minuman, Perhotelan, Hiburan, Parkir, Kesenian, Listrik), Air Tanah
 *
 * Super admin & admin memiliki akses penuh ('all').
 */
class TaxDomainHelper
{
    public const DOMAIN_WILAYAH_1 = 'wilayah_1';
    public const DOMAIN_WILAYAH_2 = 'wilayah_2';
    public const DOMAIN_ALL = 'all';

    public const DOMAIN_CONFIG = [
        self::DOMAIN_WILAYAH_1 => [
            'name' => 'Bidang Pajak Daerah Wilayah I',
            'short_name' => 'Wilayah I',
            'classification_codes' => [
                'PBB-P2', 'BPHTB', 'REKLAME', 'MBLB', 'WALET', 'OPSEN-PKB', 'OPSEN-BBNKB',
            ],
            // ID tipe retribusi/pajak resmi Baubau (Dokumen Regulasi Perda 1/2024)
            'retribution_type_ids' => [186, 187, 188, 189, 190, 191],
            'approval_gates' => [
                'pbb_mutation',
                'pbb_reversal',
                'notaris_verification',
                'penalty_waiver',
            ],
        ],
        self::DOMAIN_WILAYAH_2 => [
            'name' => 'Bidang Pajak Daerah Wilayah II',
            'short_name' => 'Wilayah II',
            'classification_codes' => [
                'PBJT-FOOD', 'PBJT-HTL', 'PBJT-HBR', 'PBJT-PRK', 'PBJT-LISTRIK', 'AIR-TANAH',
            ],
            // 172: PBJT Parkir, 192-196: PBJT Makanan/Hotel/Hiburan/Listrik, 197: Air Tanah
            'retribution_type_ids' => [172, 192, 193, 194, 195, 196, 197],
            'approval_gates' => [
                'pbjt_self_assessment',
                'penalty_waiver',
            ],
        ],
    ];

    /**
     * Dapatkan domain pajak untuk subjek auth.
     *
     * Menerima dua model karena endpoint /api/me melayani dua jenis token:
     * - App\Models\User    → petugas/admin Bapenda (punya role & metadata wilayah)
     * - App\Models\Taxpayer → wajib pajak / warga (TIDAK punya konsep wilayah)
     *
     * Taxpayer selalu dipetakan ke DOMAIN_ALL karena cakupan datanya dibatasi
     * per-NOP, bukan per-wilayah bidang.
     */
    public static function getDomain(User|Taxpayer|null $user): string
    {
        if (! $user) {
            return self::DOMAIN_ALL;
        }

        // Warga hanya melihat objek pajaknya sendiri; wilayah tidak relevan.
        if ($user instanceof Taxpayer) {
            return self::DOMAIN_ALL;
        }

        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return self::DOMAIN_ALL;
        }

        $metadata = $user->metadata;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $domain = $metadata['tax_domain'] ?? $metadata['domain'] ?? null;

        // Normalisasi format lama/variasi
        if ($domain === 'wilayah_2_pbjt' || $domain === 'wilayah2') {
            return self::DOMAIN_WILAYAH_2;
        }
        if ($domain === 'wilayah1') {
            return self::DOMAIN_WILAYAH_1;
        }

        if ($domain === self::DOMAIN_WILAYAH_1 || $domain === self::DOMAIN_WILAYAH_2) {
            return $domain;
        }

        // Default jika role bukan kepala_bapenda atau tidak dispesifikasikan
        return self::DOMAIN_ALL;
    }

    /**
     * Dapatkan daftar ID retribution_type untuk suatu domain.
     *
     * @return int[]
     */
    public static function getRetributionTypeIds(string $domain): array
    {
        if ($domain === self::DOMAIN_ALL) {
            return array_merge(
                self::DOMAIN_CONFIG[self::DOMAIN_WILAYAH_1]['retribution_type_ids'],
                self::DOMAIN_CONFIG[self::DOMAIN_WILAYAH_2]['retribution_type_ids']
            );
        }

        return self::DOMAIN_CONFIG[$domain]['retribution_type_ids'] ?? [];
    }

    /**
     * Dapatkan daftar kode klasifikasi untuk suatu domain.
     *
     * @return string[]
     */
    public static function getClassificationCodes(string $domain): array
    {
        if ($domain === self::DOMAIN_ALL) {
            return array_merge(
                self::DOMAIN_CONFIG[self::DOMAIN_WILAYAH_1]['classification_codes'],
                self::DOMAIN_CONFIG[self::DOMAIN_WILAYAH_2]['classification_codes']
            );
        }

        return self::DOMAIN_CONFIG[$domain]['classification_codes'] ?? [];
    }

    /**
     * Cek apakah tipe retribusi/pajak dapat diakses oleh domain tertentu.
     */
    public static function canAccessRetributionType(string $domain, int $typeId): bool
    {
        if ($domain === self::DOMAIN_ALL) {
            return true;
        }

        $allowedIds = self::getRetributionTypeIds($domain);
        return in_array($typeId, $allowedIds, true);
    }

    /**
     * Cek apakah kode klasifikasi dapat diakses oleh domain tertentu.
     */
    public static function canAccessClassification(string $domain, string $code): bool
    {
        if ($domain === self::DOMAIN_ALL) {
            return true;
        }

        $allowedCodes = self::getClassificationCodes($domain);
        return in_array(strtoupper($code), $allowedCodes, true);
    }

    /**
     * Otorisasi approval bertingkat berdasarkan Tupoksi Bidang.
     */
    public static function canApprove(User $user, string $gate, ?int $retributionTypeId = null): bool
    {
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }

        $domain = self::getDomain($user);

        // Jika kepala bapenda umum (tanpa domain khusus) atau super, izinkan
        if ($domain === self::DOMAIN_ALL) {
            return true;
        }

        // Jika gate spesifik per wilayah
        if (in_array($gate, ['pbb_mutation', 'pbb_reversal', 'notaris_verification'], true)) {
            return $domain === self::DOMAIN_WILAYAH_1;
        }

        if ($gate === 'pbjt_self_assessment') {
            return $domain === self::DOMAIN_WILAYAH_2;
        }

        // Dispensasi denda: periksa apakah jenis pajaknya sesuai wilayah
        if ($gate === 'penalty_waiver') {
            if ($retributionTypeId !== null) {
                return self::canAccessRetributionType($domain, $retributionTypeId);
            }
            // Jika retributionTypeId tidak ada, izinkan hanya jika gate terdaftar
            return in_array($gate, self::DOMAIN_CONFIG[$domain]['approval_gates'] ?? [], true);
        }

        return false;
    }
}
