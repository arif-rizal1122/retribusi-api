<?php

namespace App\Support;

use App\Models\Taxpayer;

/**
 * RoleResolver — helper role M-PAD (matriks 29 akun pengguna).
 *
 * Memetakan role_code → grup dashboard (sama dengan resolveDashboardRole /
 * DashboardRoleGroup di FE src/config/dashboardConfig.ts + opdRoleMatrix.ts),
 * serta mengisi metadata role (role_code/opd_role/role_group) saat login
 * agar deteksi role non-warga bekerja di FE.
 *
 * Sumber matriks: Akun_Pengguna_Updated_10Agu.xlsx (sheet "Sheet1") —
 * di-copy-kan ke FE sebagai OPD_ROLE_MATRIX.
 */
class RoleResolver
{
    /**
     * Matriks 29 role. key = role_code resmi.
     * value: name, category (matriks), core_role (role inti sistem), group (dashboard).
     */
    public const ROLE_MATRIX = [
        'kepala_daerah' => ['name' => 'Kepala Daerah & Wakil Kepala Daerah', 'category' => 'eksekutif', 'core_role' => 'viewer', 'group' => 'eksekutif'],
        'pimpinan_dprd' => ['name' => 'Pimpinan DPRD', 'category' => 'legislatif', 'core_role' => 'viewer', 'group' => 'eksekutif'],
        'sekda' => ['name' => 'Sekretaris Daerah (Sekda)', 'category' => 'eksekutif', 'core_role' => 'viewer', 'group' => 'eksekutif'],
        'bapperida' => ['name' => 'Bapperida', 'category' => 'eksekutif', 'core_role' => 'viewer', 'group' => 'eksekutif'],
        'inspektur' => ['name' => 'Inspektur (Inspektorat Daerah)', 'category' => 'pengawas', 'core_role' => 'pengawas', 'group' => 'audit'],
        'bpk' => ['name' => 'BPK (Badan Pemeriksa Keuangan)', 'category' => 'audit', 'core_role' => 'viewer', 'group' => 'audit'],
        'lpm_pt' => ['name' => 'LPM Universitas / Perguruan Tinggi', 'category' => 'lembaga', 'core_role' => 'viewer', 'group' => 'viewer'],
        'bapenda' => ['name' => 'Bapenda (Core Engine)', 'category' => 'instansi', 'core_role' => 'admin', 'group' => 'admin'],
        'bpkad' => ['name' => 'BPKAD', 'category' => 'instansi', 'core_role' => 'opd', 'group' => 'opd'],
        'kpp_pratama' => ['name' => 'KPP Pratama', 'category' => 'instansi', 'core_role' => 'viewer', 'group' => 'viewer'],
        'atr_bpn' => ['name' => 'ATR / BPN', 'category' => 'instansi', 'core_role' => 'opd', 'group' => 'opd'],
        'notaris_ppat' => ['name' => 'Notaris / PPAT', 'category' => 'lembaga', 'core_role' => 'notaris', 'group' => 'notaris'],
        'dukcapil' => ['name' => 'Dinas Dukcapil', 'category' => 'instansi', 'core_role' => 'opd', 'group' => 'opd'],
        'kominfo' => ['name' => 'Dinas Kominfo', 'category' => 'instansi', 'core_role' => 'admin', 'group' => 'admin'],
        'kecamatan' => ['name' => 'Kecamatan & Kelurahan', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'ptsp' => ['name' => 'Dinas PTSP', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'perikanan' => ['name' => 'Dinas Perikanan', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'pupr' => ['name' => 'Dinas PUPR', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'permukiman' => ['name' => 'Dinas Permukiman & Pertanahan', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'pariwisata' => ['name' => 'Dinas Pariwisata', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'perhubungan' => ['name' => 'Dinas Perhubungan', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'perindag' => ['name' => 'Dinas Perindag, Kop, & UMKM', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'dlh' => ['name' => 'Dinas Lingkungan Hidup (DLH)', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'kesehatan' => ['name' => 'Dinas Kesehatan', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'sosial_naker' => ['name' => 'Dinas Sosial & Naker', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'pertanian' => ['name' => 'Dinas Pertanian', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'damkar' => ['name' => 'Dinas Damkar & Penyelamatan', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'pertanahan' => ['name' => 'Dinas Pertanahan', 'category' => 'opd', 'core_role' => 'opd', 'group' => 'opd'],
        'satpol_pp' => ['name' => 'Satuan Polisi Pamong Praja', 'category' => 'opd', 'core_role' => 'petugas', 'group' => 'petugas'],
        'warga' => ['name' => 'Wajib Pajak / Warga', 'category' => 'warga', 'core_role' => 'citizen', 'group' => 'warga'],
    ];

    /** Alias kode ringkas → kode resmi matriks (kompatibilitas penamaan lintas dokumen). */
    public const ROLE_ALIASES = [
        'dprd' => 'pimpinan_dprd',
        'lpm' => 'lpm_pt',
        'kpp' => 'kpp_pratama',
        'notaris' => 'notaris_ppat',
        'admin_pertanahan' => 'pertanahan',
        'dispertanahan' => 'pertanahan',
    ];

    /** Role inti sistem → grup dashboard (sama dengan CORE_ROLE_TO_GROUP di FE). */
    public const CORE_ROLE_GROUPS = [
        'super_admin' => 'admin',
        'admin' => 'admin',
        'opd' => 'opd',
        'petugas' => 'petugas',
        'verifikator' => 'opd',
        'pengawas' => 'audit',
        'kabid_pengawas' => 'audit',
        'kasubid_pengawas' => 'audit',
        'notaris' => 'notaris',
        'viewer' => 'viewer',
        'citizen' => 'warga',
        'wajib_pajak' => 'warga',
        'warga' => 'warga',
        'kepala_bapenda' => 'admin',
        'admin_ptsp' => 'opd',
        'admin_pertanahan' => 'opd',
        'pertanahan' => 'opd',
        'walikota' => 'eksekutif',
    ];

    /** Pemetaan kode OPD (kolom opds.code) → role_code matriks 29 akun. */
    public const OPD_CODE_ROLE_MAP = [
        'BAPENDA' => 'bapenda',
        'PERTANAHAN' => 'pertanahan',
        'DISPERTANAHAN' => 'pertanahan',
        'DISHUB' => 'perhubungan',
        'DISPERINDAG' => 'perindag',
        'DLH' => 'dlh',
        'DISKOMINFO' => 'kominfo',
        'DPMPTSP' => 'ptsp',
        'BAPPERIDA' => 'bapperida',
        'PUPR' => 'pupr',
        'DINAS_PERIKANAN' => 'perikanan',
        'PERKIM' => 'permukiman',
        'DUKCAPIL' => 'dukcapil',
        'DAMKAR' => 'damkar',
        'SATPOL_PP' => 'satpol_pp',
        'DINAS_SOSIAL' => 'sosial_naker',
        'PARIWISATA' => 'pariwisata',
        'SETDA' => 'sekda',
        'SETWAN' => 'pimpinan_dprd',
    ];

    /**
     * Normalisasi kode role: lower/trim + resolve alias.
     * Mengembalikan kode resmi matriks, kode role inti, atau null bila tak dikenal.
     */
    public static function canonicalRoleCode(?string $code): ?string
    {
        $code = strtolower(trim((string) $code));
        if ($code === '') {
            return null;
        }
        if (isset(self::ROLE_MATRIX[$code])) {
            return $code;
        }
        if (isset(self::ROLE_ALIASES[$code])) {
            return self::ROLE_ALIASES[$code];
        }
        if (isset(self::CORE_ROLE_GROUPS[$code])) {
            return $code;
        }

        return null;
    }

    /** Label ramah-manusia untuk role_code. */
    public static function labelFor(string $code): string
    {
        $canonical = self::canonicalRoleCode($code) ?? $code;
        if (isset(self::ROLE_MATRIX[$canonical])) {
            return self::ROLE_MATRIX[$canonical]['name'];
        }

        return ucwords(str_replace('_', ' ', $canonical));
    }

    /** Grup dashboard untuk role_code (eksekutif/audit/admin/opd/petugas/notaris/viewer/warga). */
    public static function groupFor(string $code): string
    {
        $canonical = self::canonicalRoleCode($code);
        if ($canonical && isset(self::ROLE_MATRIX[$canonical])) {
            return self::ROLE_MATRIX[$canonical]['group'];
        }
        if ($canonical && isset(self::CORE_ROLE_GROUPS[$canonical])) {
            return self::CORE_ROLE_GROUPS[$canonical];
        }

        return 'viewer';
    }

    /** Pemetaan kode OPD (opds.code) → role_code matriks. */
    public static function opdRoleCodeForOpdCode(?string $opdCode): ?string
    {
        if (! $opdCode) {
            return null;
        }

        return self::OPD_CODE_ROLE_MAP[strtoupper(trim($opdCode))] ?? null;
    }

    /**
     * Resolve role untuk user (User atau Taxpayer).
     * Urutan prioritas: metadata.role_code → kode OPD → users.role → fallback.
     *
     * @return array{role_code: string, opd_role: string, role_group: string}
     */
    public static function resolveForUser($user): array
    {
        if ($user instanceof Taxpayer) {
            return [
                'role_code' => 'warga',
                'opd_role' => 'Wajib Pajak / Warga',
                'role_group' => 'warga',
            ];
        }

        $role = (string) ($user->role ?? '');

        // 1. Prioritas tertinggi: metadata.role_code (bisa diisi admin / sudah tersimpan)
        $metadata = $user->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }
        $storedCode = strtolower(trim((string) ($metadata['role_code'] ?? '')));
        $canonical = $storedCode !== '' ? self::canonicalRoleCode($storedCode) : null;
        if ($canonical) {
            return [
                'role_code' => $canonical,
                'opd_role' => self::labelFor($canonical),
                'role_group' => self::groupFor($canonical),
            ];
        }

        // 2. Pemetaan dari relasi OPD (opds.code → role_code matriks)
        if (! $user->relationLoaded('opd') && $user->opd_id) {
            $user->load('opd');
        }
        $opd = $user->relationLoaded('opd') ? $user->opd : null;
        $opdCode = $opd ? ($opd->code ?? null) : null;
        $canonical = $opdCode ? self::opdRoleCodeForOpdCode($opdCode) : null;
        if ($canonical) {
            return [
                'role_code' => $canonical,
                'opd_role' => $opd->name ?? self::labelFor($canonical),
                'role_group' => self::groupFor($canonical),
            ];
        }

        // 3. User terikat OPD tapi kode OPD tak dikenal → role generik opd
        if ($user->opd_id) {
            return [
                'role_code' => 'opd',
                'opd_role' => $opd->name ?? 'Staf OPD',
                'role_group' => 'opd',
            ];
        }

        // 4. Role inti sistem (users.role)
        $canonical = self::canonicalRoleCode($role);
        if ($canonical) {
            return [
                'role_code' => $canonical,
                'opd_role' => self::labelFor($canonical),
                'role_group' => self::groupFor($canonical),
            ];
        }

        // 5. Fallback jujur
        $group = self::CORE_ROLE_GROUPS[$role] ?? 'viewer';
        $label = $role !== '' ? ucwords(str_replace('_', ' ', $role)) : 'Viewer';

        return [
            'role_code' => $role !== '' ? $role : 'viewer',
            'opd_role' => $label,
            'role_group' => $group,
        ];
    }

    /**
     * Tulis metadata role ke kolom metadata (role_code/opd_role/role_group)
     * lalu simpan. Dipanggil saat login agar FE dapat mendeteksi role non-warga
     * lewat user.metadata (resolveDashboardRole).
     */
    public static function persistToMetadata($user): void
    {
        $role = self::resolveForUser($user);

        $metadata = $user->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }
        $metadata['role_code'] = $role['role_code'];
        $metadata['opd_role'] = $role['opd_role'];
        $metadata['role_group'] = $role['role_group'];

        $user->metadata = $metadata;
        $user->save();
    }

    /**
     * Suntik atribut role_code/opd_role/role_group ke instance user
     * (hanya untuk serialisasi response, tidak dipersist).
     */
    public static function enrich($user)
    {
        $role = self::resolveForUser($user);
        $user->setAttribute('role_code', $role['role_code']);
        $user->setAttribute('opd_role', $role['opd_role']);
        $user->setAttribute('role_group', $role['role_group']);

        return $user;
    }
}
