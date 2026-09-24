<?php

namespace App\Support;

/**
 * ServiceAccessMatrix — RBAC berbasis LAYANAN untuk Bidang Pajak Daerah.
 *
 * Menggantikan pola "cek role mentah di array roles" yang tersebar, dengan
 * pemetaan terpusat: SETIAP LAYANAN memetakan tupoksi Kabid vs Kasubid.
 *
 * Dasar pembagian (dokumen Tupoksi Kabid & Kasubid Wilayah I/II):
 *   - KASUBID  = memeriksa data untuk VERIFIKASI (maker/checker L1-L2)
 *   - KABID    = melakukan PENUGASAN + otorisasi akhir (L3 signer)
 *
 * Pemetaan jenis pajak diambil dari tabel `retribution_classifications`:
 *   Wilayah I : 186 PBB-P2, 187 BPHTB, 188 Reklame, 189 MBLB, 190 Walet, 191 Opsen
 *   Wilayah II: 172 Parkir, 192 Makan-Minum, 193 Hotel, 194 Hiburan,
 *               195 Hiburan Malam, 196 Listrik, 197 Air Tanah
 */
class ServiceAccessMatrix
{
    /** Aksi dasar yang dikenali sistem. */
    public const ACT_VIEW = 'view';           // melihat daftar/detail
    public const ACT_VERIFY = 'verify';       // memeriksa & memverifikasi data
    public const ACT_ASSIGN = 'assign';       // menugaskan pekerjaan ke orang lain
    public const ACT_APPROVE = 'approve';     // otorisasi akhir (L3)
    public const ACT_MANAGE = 'manage';       // kelola master/konfigurasi
    public const ACT_ENFORCE = 'enforce';     // penegakan & uji petik

    /**
     * Matriks layanan -> aksi yang diizinkan per kelompok role.
     *
     * Bentuk: 'kode_layanan' => [
     *    'domain'   => wilayah_1|wilayah_2|all,
     *    'label'    => nama layanan,
     *    'kasubid'  => [aksi...],   // memeriksa/verifikasi
     *    'kabid'    => [aksi...],   // penugasan/otorisasi
     * ]
     */
    public const SERVICES = [
        // ─────────────── WILAYAH I ───────────────
        'PBB-P2' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_1,
            'label'   => 'PBB-P2 (Pendaftaran, Mutasi & Pembetulan)',
            // Kasubid memeriksa; Kabid menelaah + menugaskan + otorisasi akhir (L3).
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'BPHTB' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_1,
            'label'   => 'BPHTB (Bea Perolehan Hak)',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'REKLAME' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_1,
            'label'   => 'Pajak Reklame',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'MBLB' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_1,
            'label'   => 'Pajak MBLB',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'WALET' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_1,
            'label'   => 'Pajak Sarang Burung Walet',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'OPSEN-PKB' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_1,
            'label'   => 'Opsen PKB & BBNKB',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],

        // ─────────────── WILAYAH II ───────────────
        'PBJT-PRK' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_2,
            'label'   => 'PBJT Jasa Parkir',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'PBJT-FOOD' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_2,
            'label'   => 'PBJT Makanan & Minuman',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'PBJT-HTL' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_2,
            'label'   => 'PBJT Jasa Perhotelan',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'PBJT-HBR' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_2,
            'label'   => 'PBJT Kesenian & Hiburan',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'PBJT-LISTRIK' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_2,
            'label'   => 'PBJT Tenaga Listrik',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'AIR-TANAH' => [
            'domain'  => TaxDomainHelper::DOMAIN_WILAYAH_2,
            'label'   => 'Pajak Air Tanah',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],

        // ── Layanan lintas wilayah (koordinasi & pengawasan) ──
        'PENAGIHAN' => [
            'domain'  => TaxDomainHelper::DOMAIN_ALL,
            'label'   => 'Penagihan & Surat Paksa',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_VERIFY, self::ACT_ASSIGN, self::ACT_APPROVE],
        ],
        'PENGAWASAN' => [
            'domain'  => TaxDomainHelper::DOMAIN_ALL,
            'label'   => 'Pengawasan, Uji Petik & Penegakan',
            'kasubid' => [self::ACT_VIEW, self::ACT_ENFORCE],
            'kabid'   => [self::ACT_VIEW, self::ACT_ASSIGN, self::ACT_ENFORCE],
        ],
        'MASTER_DATA' => [
            'domain'  => TaxDomainHelper::DOMAIN_ALL,
            'label'   => 'Master Data & Konfigurasi Tarif',
            'kasubid' => [self::ACT_VIEW],
            'kabid'   => [self::ACT_VIEW, self::ACT_MANAGE],
        ],
        'PELAPORAN' => [
            'domain'  => TaxDomainHelper::DOMAIN_ALL,
            'label'   => 'Pelaporan & Rekonsiliasi',
            'kasubid' => [self::ACT_VIEW, self::ACT_VERIFY],
            'kabid'   => [self::ACT_VIEW, self::ACT_APPROVE],
        ],
    ];

    /**
     * Pemetaan role -> kelompok tupoksi ('kabid' | 'kasubid') + domain penugasan.
     *
     * Role 'kabid_wilayah_1' adalah Kabid untuk SEMUA layanan ber-domain wilayah_1;
     * 'kasubid_pendataan_wil1' adalah Kasubid PENDATAAN (verifikasi) untuk wilayah_1;
     * 'kasubid_penagihan_wil1' adalah Kasubid PENAGIHAN (verifikasi + penagihan) wilayah_1.
     */
    public const ROLE_PROFILE = [
        'kabid_wilayah_1' => ['group' => 'kabid',  'domain' => TaxDomainHelper::DOMAIN_WILAYAH_1, 'focus' => 'all'],
        'kabid_wilayah_2' => ['group' => 'kabid',  'domain' => TaxDomainHelper::DOMAIN_WILAYAH_2, 'focus' => 'all'],
        'kasubid_pendataan_wil1' => ['group' => 'kasubid', 'domain' => TaxDomainHelper::DOMAIN_WILAYAH_1, 'focus' => 'pendataan'],
        'kasubid_penagihan_wil1' => ['group' => 'kasubid', 'domain' => TaxDomainHelper::DOMAIN_WILAYAH_1, 'focus' => 'penagihan'],
        'kasubid_pendataan_wil2' => ['group' => 'kasubid', 'domain' => TaxDomainHelper::DOMAIN_WILAYAH_2, 'focus' => 'pendataan'],
        'kasubid_penagihan_wil2' => ['group' => 'kasubid', 'domain' => TaxDomainHelper::DOMAIN_WILAYAH_2, 'focus' => 'penagihan'],
        // Kasubid Koordinasi, Kerjasama & Pengembangan — tupoksinya teknis
        // (integrasi bank H2H, simulator tarif, pemetaan objek baru, evaluasi
        // regulasi). BUKAN pengambil keputusan otorisasi.
        // Sebelumnya group-nya 'kabid' sehingga ikut mendapat aksi
        // `approve` + `assign` — wewenang yang bukan miliknya. Dikoreksi ke
        // 'kasubid' (view + verify) selaras sifat pemeriksaan teknisnya.
        'kasubid_pengembangan'   => ['group' => 'kasubid', 'domain' => TaxDomainHelper::DOMAIN_ALL,       'focus' => 'koordinasi'],
        'kasubid_pengawas'       => ['group' => 'kasubid', 'domain' => TaxDomainHelper::DOMAIN_ALL,       'focus' => 'pengawasan'],
        'kasubid_pendataan'      => ['group' => 'kasubid', 'domain' => TaxDomainHelper::DOMAIN_ALL,       'focus' => 'pendataan'],

        // Kabid Pengawas — sebelumnya TIDAK terdaftar di matriks sehingga
        // akunnya (kabid@retribusi.id) lolos semua gate `service_action`
        // tanpa pemeriksaan. Tupoksinya pengawasan lintas wilayah: boleh
        // menugaskan & menegakkan, TIDAK boleh `approve` (otorisasi akhir
        // tetap milik Kabid Pendapatan wilayah / Kepala Bapenda).
        'kabid_pengawas'         => ['group' => 'kabid',   'domain' => TaxDomainHelper::DOMAIN_ALL,       'focus' => 'all'],

        'kepala_bapenda'         => ['group' => 'kabid',   'domain' => TaxDomainHelper::DOMAIN_ALL,       'focus' => 'all'],
    ];

    /** Fokus Kasubid -> layanan yang menjadi tanggung jawabnya. */
    public const FOCUS_SERVICES = [
        'pendataan'  => ['PBB-P2', 'BPHTB', 'REKLAME', 'MBLB', 'WALET', 'OPSEN-PKB',
                         'PBJT-PRK', 'PBJT-FOOD', 'PBJT-HTL', 'PBJT-HBR', 'PBJT-LISTRIK', 'AIR-TANAH'],
        // Kasubid Penagihan tupoksinya "Penagihan, VERIFIKASI & Pelaporan"
        // (lihat metadata akun: role_code kasubid_penagihan_wilN). Karena itu
        // ia harus memegang layanan pajak wilayahnya juga — bukan hanya
        // PENAGIHAN/PELAPORAN — supaya bisa memverifikasi dokumen.
        // Filter domain di servicesForRole() otomatis memangkas daftar ini
        // ke layanan wilayah masing-masing (W1 atau W2).
        'penagihan'  => ['PBB-P2', 'BPHTB', 'REKLAME', 'MBLB', 'WALET', 'OPSEN-PKB',
                         'PBJT-PRK', 'PBJT-FOOD', 'PBJT-HTL', 'PBJT-HBR', 'PBJT-LISTRIK', 'AIR-TANAH',
                         'PENAGIHAN', 'PELAPORAN'],
        'pengawasan' => ['PENGAWASAN'],
        // Kasubid Pengembangan memeriksa/menelaah lintas wilayah (portofolio:
        // integrasi H2H, simulator tarif, pemetaan objek baru, evaluasi
        // regulasi). Diberi seluruh layanan pajak lintas wilayah + PENAGIHAN
        // + PELAPORAN + MASTER_DATA agar `verify` benar-benar berguna.
        'koordinasi' => ['PBB-P2', 'BPHTB', 'REKLAME', 'MBLB', 'WALET', 'OPSEN-PKB',
                         'PBJT-PRK', 'PBJT-FOOD', 'PBJT-HTL', 'PBJT-HBR', 'PBJT-LISTRIK', 'AIR-TANAH',
                         'PENAGIHAN', 'PELAPORAN', 'MASTER_DATA'],
        'all'        => [], // kosong = semua layanan dalam domainnya
    ];

    /**
     * Apakah role ini memakai matriks layanan (bukan role generik)?
     */
    public static function isServiceScopedRole(?string $role): bool
    {
        return $role !== null && isset(self::ROLE_PROFILE[$role]);
    }

    public static function profileFor(?string $role): ?array
    {
        return self::ROLE_PROFILE[$role] ?? null;
    }

    /**
     * Daftar kode layanan yang boleh diakses role ini (sudah difilter domain + fokus).
     *
     * @return string[]
     */
    public static function servicesForRole(?string $role): array
    {
        $profile = self::profileFor($role);
        if (! $profile) {
            return [];
        }

        // Kabid / Kepala Bapenda / pengembangan: semua layanan dalam domainnya.
        if ($profile['focus'] === 'all') {
            return array_values(array_filter(
                array_keys(self::SERVICES),
                fn ($code) => self::domainMatches($profile['domain'], self::SERVICES[$code]['domain'])
            ));
        }

        $focusServices = self::FOCUS_SERVICES[$profile['focus']] ?? [];

        return array_values(array_filter($focusServices, function ($code) use ($profile) {
            if (! isset(self::SERVICES[$code])) {
                return false;
            }
            return self::domainMatches($profile['domain'], self::SERVICES[$code]['domain']);
        }));
    }

    /**
     * Apakah role boleh melakukan aksi tertentu pada layanan tertentu.
     *
     * @param  string  $serviceCode  mis. 'PBB-P2'
     * @param  string  $action       salah satu konstanta ACT_*
     */
    public static function can(?string $role, string $serviceCode, string $action): bool
    {
        $profile = self::profileFor($role);
        if (! $profile) {
            return false;
        }

        $service = self::SERVICES[$serviceCode] ?? null;
        if (! $service) {
            return false;
        }

        // Layanan di luar domain role -> tolak.
        if (! self::domainMatches($profile['domain'], $service['domain'])) {
            return false;
        }

        // Layanan di luar fokus Kasubid -> tolak.
        if ($profile['group'] === 'kasubid') {
            $allowed = self::FOCUS_SERVICES[$profile['focus']] ?? [];
            if (! in_array($serviceCode, $allowed, true)) {
                return false;
            }
        }

        $actions = $service[$profile['group']] ?? [];

        return in_array($action, $actions, true);
    }

    /**
     * Ringkasan hak akses role — dipakai FE saat login agar sidebar
     * dapat disusun tanpa duplikasi logika.
     *
     * @return array{group:?string,domain:?string,focus:?string,services:string[],actions:array<string,string[]>}
     */
    public static function accessForRole(?string $role): array
    {
        $profile = self::profileFor($role);

        if (! $profile) {
            return [
                'group'    => null,
                'domain'   => null,
                'focus'    => null,
                'services' => [],
                'actions'  => [],
            ];
        }

        $services = self::servicesForRole($role);
        $actions = [];

        foreach ($services as $code) {
            // Ambil aksi sesuai kelompok tupoksi role (kasubid = verifikasi, kabid = penugasan).
            $actions[$code] = array_values(self::SERVICES[$code][$profile['group']] ?? []);
        }

        return [
            'group'    => $profile['group'],
            'domain'   => $profile['domain'],
            'focus'    => $profile['focus'],
            'services' => $services,
            'actions'  => $actions,
        ];
    }

    /** Apakah domain role mencakup domain layanan. */
    public static function domainMatches(string $roleDomain, string $serviceDomain): bool
    {
        if ($roleDomain === TaxDomainHelper::DOMAIN_ALL) {
            return true;
        }
        if ($serviceDomain === TaxDomainHelper::DOMAIN_ALL) {
            return true; // layanan lintas wilayah dapat diakses kedua wilayah
        }
        return $roleDomain === $serviceDomain;
    }
}
