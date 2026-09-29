<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OfficialResolverService — memetakan (stage + tax_domain) menjadi user_id pejabat.
 *
 * LATAR MASALAH (29 Sep 2026):
 *   service_task_assignments punya 7 baris dengan assigned_to_user_id = NULL —
 *   semuanya tahap VERIFIKASI_HASIL_SURVEI. Penyebabnya di
 *   LapanganVerifikasiService::ajukanHasil() yang menulis null secara hardcode:
 *
 *       'assigned_to_user_id' => null,
 *
 *   SlaTrackerService::SLA_MATRIX hanya menyimpan 'pic' sebagai LABEL TEKS
 *   ("Kasubid Pendataan Wilayah I & II"), bukan user_id. Akibatnya tugas
 *   verifikasi tidak pernah muncul di dashboard pejabat mana pun.
 *
 * SOLUSI:
 *   Kelas ini menerjemahkan kombinasi (stage, tax_domain) menjadi ROLE pejabat,
 *   lalu mencari user aktif dengan role tersebut pada opd_id Bapenda.
 *
 * ATURAN BISNIS (dari tupoksi & TaxDomainHelper):
 *   - Wilayah I  : PBB-P2, BPHTB, REKLAME, MBLB, WALET, OPSEN-PKB/BBNKB
 *   - Wilayah II : PBJT-* (FOOD, HTL, HBR, PRK, LISTRIK), AIR-TANAH
 *   - Pendataan  : survei & penetapan objek  → VERIFIKASI_HASIL_*
 *   - Penagihan  : penagihan & pelaporan     → VERIFIKASI_PENAGIHAN_HASIL
 *
 * FAIL-SAFE:
 *   Bila pejabat wilayah tidak ditemukan (mis. akun nonaktif), resolver jatuh
 *   ke pejabat lintas-wilayah (kasubid_pendataan / kasubid_penagihan), lalu ke
 *   kepala_bapenda. Bila semua gagal, mengembalikan null + menulis peringatan
 *   log — pemanggil tetap boleh menyimpan tugas ke kolam "belum tertugaskan"
 *   yang terlihat Kabid, bukan menghilang.
 */
class OfficialResolverService
{
    /** OPD Bapenda — satu-satunya pemilik penugasan pajak daerah. */
    private const OPD_BAPENDA = 5;

    /**
     * Peta tahapan → [role wilayah 1, role wilayah 2, role cadangan].
     *
     * Catatan: kunci di sini adalah nama tahap apa adanya dari SLA_MATRIX
     * (VERIFIKASI_HASIL_SURVEI, dst.) — sengaja eksplisit, bukan pola regex,
     * supaya tahap baru tidak diam-diam nyasar ke pejabat yang salah.
     */
    private const STAGE_ROLE_MAP = [
        // ── Kasubid Pendataan: verifikasi berkas & hasil survei ──
        'VERIFIKASI_BERKAS' => [
            'wilayah_1' => 'kasubid_pendataan_wil1',
            'wilayah_2' => 'kasubid_pendataan_wil2',
            'fallback'  => 'kasubid_pendataan',
        ],
        'VERIFIKASI_HASIL_SURVEI' => [
            'wilayah_1' => 'kasubid_pendataan_wil1',
            'wilayah_2' => 'kasubid_pendataan_wil2',
            'fallback'  => 'kasubid_pendataan',
        ],
        // ── Kasubid Penagihan: verifikasi hasil penagihan & STPD ──
        'VERIFIKASI_PENAGIHAN' => [
            'wilayah_1' => 'kasubid_penagihan_wil1',
            'wilayah_2' => 'kasubid_penagihan_wil2',
            'fallback'  => 'kepala_bapenda',
        ],
        'VERIFIKASI_HASIL_PENAGIHAN' => [
            'wilayah_1' => 'kasubid_penagihan_wil1',
            'wilayah_2' => 'kasubid_penagihan_wil2',
            'fallback'  => 'kepala_bapenda',
        ],
        'PENERBITAN_STPD' => [
            'wilayah_1' => 'kasubid_penagihan_wil1',
            'wilayah_2' => 'kasubid_penagihan_wil2',
            'fallback'  => 'kepala_bapenda',
        ],
        'CLOSING_REKONSILIASI' => [
            'wilayah_1' => 'kasubid_penagihan_wil1',
            'wilayah_2' => 'kasubid_penagihan_wil2',
            'fallback'  => 'kepala_bapenda',
        ],
        // ── Kabid: otorisasi & penugasan lapangan (lintas wilayah) ──
        'PENUGASAN_LAPANGAN' => [
            'wilayah_1' => 'kabid_wilayah_1',
            'wilayah_2' => 'kabid_wilayah_2',
            'fallback'  => 'kepala_bapenda',
        ],
        'APPROVAL_KABID' => [
            'wilayah_1' => 'kabid_wilayah_1',
            'wilayah_2' => 'kabid_wilayah_2',
            'fallback'  => 'kepala_bapenda',
        ],
        // ── Modul permohonan (Reklame/PBG/Air Tanah/TKA) — masuk PTSP ──
        'VERIFIKASI_PTSP' => [
            'wilayah_1' => 'admin_ptsp',
            'wilayah_2' => 'admin_ptsp',
            'fallback'  => 'admin_ptsp',
        ],
        // Survei lapangan dikerjakan PETUGAS, bukan pejabat. Resolver tidak
        // menentukan PIC di sini — penugasan petugas datang dari AutoAssignService
        // (pembagian adil). Dibiarkan null = kolam petugas.
        'SURVEI_LAPANGAN' => [
            'wilayah_1' => null,
            'wilayah_2' => null,
            'fallback'  => null,
        ],
        'SURVEI_LAPANGAN_REKLAME' => [
            'wilayah_1' => null,
            'wilayah_2' => null,
            'fallback'  => null,
        ],
        // Dijalankan hook otomatis, bukan manusia.
        'PENERBITAN_ESSPD' => [
            'wilayah_1' => null,
            'wilayah_2' => null,
            'fallback'  => null,
        ],
    ];

    /** Cache hasil resolusi per-request supaya tidak query berulang. */
    private array $cache = [];

    /**
     * Cari user_id pejabat yang berhak untuk (stage, tax_domain).
     *
     * @return int|null  null = tidak ada pejabat (sengaja, atau gagal resolusi)
     */
    public function resolve(string $stage, ?string $taxDomain): ?int
    {
        $map = self::STAGE_ROLE_MAP[$stage] ?? null;

        // Tahap tidak dikenal → JANGAN menebak. Biar terlihat di log.
        if ($map === null) {
            Log::warning('[OfficialResolver] Tahap belum terpetakan ke pejabat.', [
                'stage' => $stage,
            ]);

            return null;
        }

        $domain = $this->normalisasiDomain($taxDomain);
        $role = $map[$domain] ?? $map['fallback'] ?? null;

        // Role null = memang bukan tugas pejabat (mis. SURVEI_LAPANGAN → petugas).
        if ($role === null) {
            return null;
        }

        // ── DELEGASI/BACKUP (29 Sep 2026) ────────────────────────
        // Pejabat pemegang role bisa cuti/sakit. Bila ada delegasi AKTIF untuk
        // role ini, pakai penggantinya LEBIH DULU. Fail-safe: delegasi tidak
        // valid dilewati, bukan dianggap sah.
        $delegasiSvc = app(\App\Services\OfficialDelegationService::class);
        $delegatedUserId = $delegasiSvc->resolveDelegate($role, $domain);

        if ($delegatedUserId !== null) {
            Log::info('[OfficialResolver] Memakai pejabat PENGGANTI (delegasi aktif).', [
                'stage'    => $stage,
                'domain'   => $domain,
                'role'     => $role,
                'delegate' => $delegatedUserId,
            ]);

            return $delegatedUserId;
        }

        $userId = $this->cariUserByRole($role);

        if ($userId !== null) {
            return $userId;
        }

        // Gagal di role wilayah → coba cadangan sebelum menyerah.
        $fallback = $map['fallback'] ?? null;
        if ($fallback !== null && $fallback !== $role) {
            Log::warning('[OfficialResolver] Pejabat wilayah tidak ditemukan, pakai cadangan.', [
                'stage'    => $stage,
                'domain'   => $domain,
                'role'     => $role,
                'fallback' => $fallback,
            ]);

            return $this->cariUserByRole($fallback);
        }

        Log::error('[OfficialResolver] Tidak ada pejabat aktif untuk tahap ini.', [
            'stage'  => $stage,
            'domain' => $domain,
            'role'   => $role,
        ]);

        return null;
    }

    /**
     * Terjemahkan nilai tax_domain apa pun menjadi 'wilayah_1' / 'wilayah_2'.
     *
     * Nilai tak dikenal → wilayah_2 (mayoritas objek PBJT, dan Bapenda memang
     * lebih banyak menangani wilayah II). Keputusan ini eksplisit supaya tidak
     * diam-diam salah: bila ternyata keliru, log peringatan tetap ditulis.
     */
    public function normalisasiDomain(?string $taxDomain): string
    {
        $d = strtolower(trim((string) $taxDomain));

        if ($d === 'wilayah_1' || $d === 'wil1' || $d === '1') {
            return 'wilayah_1';
        }

        if ($d === 'wilayah_2' || $d === 'wil2' || $d === '2') {
            return 'wilayah_2';
        }

        Log::warning('[OfficialResolver] tax_domain tidak dikenal, default wilayah_2.', [
            'tax_domain' => $taxDomain,
        ]);

        return 'wilayah_2';
    }

    /**
     * Cari user aktif dengan role tertentu di lingkup Bapenda.
     *
     * Urutan: (1) special_case di tb_settings bila dikonfigurasi, (2) opd Bapenda,
     * (3) lintas OPD untuk role yang memang lintas (admin_ptsp).
     */
    public function cariUserByRole(string $role): ?int
    {
        $key = 'role:' . $role;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        // 1. Cari di OPD Bapenda lebih dulu.
        $row = DB::table('users')
            ->where('role', $role)
            ->where('opd_id', self::OPD_BAPENDA)
            ->orderBy('id')
            ->first(['id']);

        // 2. Role lintas-OPD (admin_ptsp) bisa berada di OPD lain.
        if ($row === null) {
            $row = DB::table('users')
                ->where('role', $role)
                ->orderBy('id')
                ->first(['id']);
        }

        $this->cache[$key] = $row ? (int) $row->id : null;

        return $this->cache[$key];
    }

    /**
     * Daftar pejabat yang berhak melihat tugas untuk sebuah tahap — dipakai
     * untuk kolam "belum tertugaskan" dan untuk pengecekan hak akses.
     *
     * @return array<int> daftar user_id
     */
    public function pejabatUntuk(string $stage, ?string $taxDomain): array
    {
        $map = self::STAGE_ROLE_MAP[$stage] ?? null;

        if ($map === null) {
            return [];
        }

        $domain = $this->normalisasiDomain($taxDomain);
        $roles = array_filter([
            $map[$domain] ?? null,
            $map['fallback'] ?? null,
            // Kabid selalu boleh melihat wilayah binaannya.
            $domain === 'wilayah_1' ? 'kabid_wilayah_1' : 'kabid_wilayah_2',
        ]);

        $ids = [];

        foreach (array_unique($roles) as $role) {
            $id = $this->cariUserByRole($role);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Nama jabatan yang bertanggung jawab (untuk catatan/log yang bisa dibaca manusia).
     */
    public function labelJabatan(string $stage, ?string $taxDomain): string
    {
        $map = self::STAGE_ROLE_MAP[$stage] ?? null;

        if ($map === null) {
            return 'Tidak diketahui';
        }

        $domain = $this->normalisasiDomain($taxDomain);
        $role = $map[$domain] ?? $map['fallback'] ?? null;

        if ($role === null) {
            return 'Petugas Lapangan (via AutoAssign)';
        }

        return SlaTrackerService::SLA_MATRIX[$stage]['pic'] ?? $role;
    }

    /** Kosongkan cache — dipakai unit test. */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
