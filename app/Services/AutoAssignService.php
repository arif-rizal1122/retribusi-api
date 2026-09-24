<?php

namespace App\Services;

use App\Models\User;
use App\Support\TaxDomainHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AutoAssignService — pembagian penugasan petugas yang ADIL.
 *
 * === Revisi 24 Sep 2026 (keputusan pengelola) ===
 *
 * Pembagian wilayah BERBASIS JENIS LAYANAN/PAJAK, bukan lokasi geografis.
 * Pemetaan ada di TaxDomainHelper::DOMAIN_CONFIG (classification_ids).
 *
 * Alur:
 *  1. KABID menetapkan master: petugas X memegang layanan {A,B,C}
 *     (user_retribution_assignments level retribution_classification_id).
 *  2. SISTEM auto-assign objek ke petugas kompeten yang paling ADIL:
 *       - PRIORITAS UTAMA: frekuensi penugasan BULAN INI paling sedikit
 *       - SERI: dipilih ACAK (bukan urutan ID) supaya tidak selalu orang sama
 *       - Beban aktif (PENDING/IN_PROGRESS/REVISI) dipakai sebagai penyaring kedua
 *         untuk mencegah penumpukan pada satu orang di hari yang sama.
 *  3. KASUBID verifikasi dokumen → teruskan ke Kabid.
 *  4. KABID konfirmasi + boleh ganti petugas (jejak ke tabel audit).
 *
 * Ketentuan tambahan:
 *  - Satu objek = SATU petugas (PIC tunggal).
 *  - Petugas TIDAK bisa menolak tugas.
 *  - Tidak ada petugas kompeten → return null + status perlu eskalasi ke Kabid.
 *  - Beban dihitung TOTAL LINTAS LAYANAN (bukan per layanan).
 *  - Tidak ada penghapusan data apa pun.
 */
class AutoAssignService
{
    /** opd_id Bapenda Kota Baubau. */
    public const OPD_BAPENDA = 5;

    public const STAGE_FIELD_SURVEY = 'SURVEY';
    public const STAGE_VERIFIKASI = 'VERIFIKASI';
    public const STAGE_OTORISASI = 'OTORISASI';

    /** Status penugasan yang masih membebani petugas. */
    public const STATUS_AKTIF = ['PENDING', 'IN_PROGRESS', 'REVISI'];

    public const EVENT_ASSIGNED = 'ASSIGNED';
    public const EVENT_REASSIGNED = 'REASSIGNED';
    public const EVENT_ESCALATED = 'ESCALATED';

    // ==================================================================
    // PEMILIHAN PETUGAS
    // ==================================================================

    /**
     * Pilih petugas paling adil untuk satu objek.
     *
     * Urutan keadilan:
     *   1. frekuensi penugasan BULAN INI (paling sedikit menang)
     *   2. beban aktif saat ini (paling sedikit menang)
     *   3. ACAK di antara yang seri
     *
     * @param  int       $classificationId  klasifikasi pajak objek (mis. 188 = Reklame)
     * @param  int|null  $excludeUserId     pembuat objek (anti self-assign)
     * @return array{
     *     petugas: User|null,
     *     alasan: string,
     *     frekuensi: array<int,int>,
     *     beban: array<int,int>,
     *     jumlah_kandidat: int,
     *     butuh_eskalasi: bool
     * }
     */
    public function pilihPetugas(int $classificationId, ?int $excludeUserId = null): array
    {
        $kandidat = $this->kandidatPetugas($classificationId);

        if ($excludeUserId) {
            $kandidat = $kandidat
                ->reject(fn (User $u) => (int) $u->id === (int) $excludeUserId)
                ->values();
        }

        $jumlah = $kandidat->count();

        if ($jumlah === 0) {
            $pesan = 'Tidak ada petugas kompeten untuk klasifikasi #' . $classificationId
                . ($excludeUserId ? ' (setelah mengecualikan pembuat objek)' : '')
                . '. Perlu penetapan Kabid.';

            Log::warning('[AutoAssign] ' . $pesan, ['classification_id' => $classificationId]);

            return [
                'petugas' => null,
                'alasan' => $pesan,
                'frekuensi' => [],
                'beban' => [],
                'jumlah_kandidat' => 0,
                'butuh_eskalasi' => true,
            ];
        }

        $ids = $kandidat->pluck('id')->map(fn ($v) => (int) $v)->all();
        $frekuensi = $this->frekuensiBulanIni($ids);
        $beban = $this->bebanAktif($ids);

        // Kelompokkan kandidat berdasarkan (frekuensi, beban) terkecil,
        // lalu pilih ACAK di antara yang seri.
        $minFrek = min(array_map(fn ($id) => $frekuensi[$id] ?? 0, $ids));
        $kandidatFrekMin = $kandidat->filter(
            fn (User $u) => ($frekuensi[(int) $u->id] ?? 0) === $minFrek
        )->values();

        $minBeban = min($kandidatFrekMin->map(fn (User $u) => $beban[(int) $u->id] ?? 0)->all());
        $finalis = $kandidatFrekMin->filter(
            fn (User $u) => ($beban[(int) $u->id] ?? 0) === $minBeban
        )->values();

        // ACAK di antara yang seri — memakai random_int (bukan shuffle bergantung seed).
        $terpilih = $finalis[random_int(0, $finalis->count() - 1)];

        return [
            'petugas' => $terpilih,
            'alasan' => sprintf(
                'Dipilih acak dari %d petugas seri (frekuensi bulan ini: %d, beban aktif: %d) — pool %d petugas kompeten',
                $finalis->count(),
                $minFrek,
                $minBeban,
                $jumlah
            ),
            'frekuensi' => $frekuensi,
            'beban' => $beban,
            'jumlah_kandidat' => $jumlah,
            'butuh_eskalasi' => false,
        ];
    }

    /**
     * Buat penugasan otomatis untuk sebuah objek.
     * Mengembalikan model penugasan, atau null kalau tidak ada petugas kompeten
     * (pemanggil WAJIB menandai objek butuh penetapan Kabid).
     */
    public function autoAssign(
        string $referenceType,
        int $referenceId,
        int $classificationId,
        ?int $assignedBy = null,
        ?int $excludeUserId = null,
        string $stage = self::STAGE_FIELD_SURVEY,
        ?Carbon $slaDeadline = null
    ): ?object {
        $hasil = $this->pilihPetugas($classificationId, $excludeUserId);

        if (! $hasil['petugas']) {
            return null;
        }

        $domain = $this->domainUntukKlasifikasi($classificationId);

        $assignment = DB::transaction(function () use (
            $referenceType, $referenceId, $classificationId, $domain,
            $assignedBy, $stage, $slaDeadline, $hasil
        ) {
            $id = DB::table('service_task_assignments')->insertGetId([
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'stage' => $stage,
                'tax_domain' => $domain,
                'tax_sector_code' => (string) $classificationId,
                'assigned_to_user_id' => $hasil['petugas']->id,
                'assigned_by_user_id' => $assignedBy,
                'status' => 'PENDING',
                'sla_deadline' => $slaDeadline,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->catatLog([
                'assignment_id' => $id,
                'event' => self::EVENT_ASSIGNED,
                'actor_user_id' => $assignedBy,
                'to_user_id' => $hasil['petugas']->id,
                'stage_to' => $stage,
                'status_to' => 'PENDING',
                'reason' => $hasil['alasan'],
                'meta' => [
                    'classification_id' => $classificationId,
                    'tax_domain' => $domain,
                    'frekuensi_bulan_ini' => $hasil['frekuensi'][(int) $hasil['petugas']->id] ?? 0,
                    'beban_aktif' => $hasil['beban'][(int) $hasil['petugas']->id] ?? 0,
                ],
            ]);

            return $id;
        });

        return DB::table('service_task_assignments')->where('id', $assignment)->first();
    }

    // ==================================================================
    // GANTI PETUGAS (kewenangan Kabid)
    // ==================================================================

    /**
     * Alihkan PIC sebuah penugasan. Jejak ditulis ke TABEL AUDIT, bukan notes.
     */
    public function gantiPetugas(
        int $assignmentId,
        int $petugasBaruId,
        int $olehUserId,
        string $alasan
    ): array {
        $lama = DB::table('service_task_assignments')->where('id', $assignmentId)->first();

        if (! $lama) {
            throw new \InvalidArgumentException('Penugasan #' . $assignmentId . ' tidak ditemukan.');
        }

        if (trim($alasan) === '') {
            throw new \InvalidArgumentException('Alasan penggantian petugas wajib diisi.');
        }

        $baru = DB::table('users')->where('id', $petugasBaruId)->first();
        if (! $baru) {
            throw new \InvalidArgumentException('Petugas #' . $petugasBaruId . ' tidak ditemukan.');
        }

        DB::transaction(function () use ($assignmentId, $lama, $petugasBaruId, $olehUserId, $alasan) {
            DB::table('service_task_assignments')->where('id', $assignmentId)->update([
                'assigned_to_user_id' => $petugasBaruId,
                'assigned_by_user_id' => $olehUserId,
                'updated_at' => now(),
            ]);

            $this->catatLog([
                'assignment_id' => $assignmentId,
                'event' => self::EVENT_REASSIGNED,
                'actor_user_id' => $olehUserId,
                'from_user_id' => $lama->assigned_to_user_id,
                'to_user_id' => $petugasBaruId,
                'stage_from' => $lama->stage,
                'stage_to' => $lama->stage,
                'status_from' => $lama->status,
                'status_to' => $lama->status,
                'reason' => $alasan,
            ]);
        });

        return [
            'assignment_id' => $assignmentId,
            'from_user_id' => $lama->assigned_to_user_id,
            'to_user_id' => $petugasBaruId,
            'alasan' => $alasan,
        ];
    }

    // ==================================================================
    // KUERI PENDUKUNG
    // ==================================================================

    /**
     * Kandidat petugas kompeten untuk klasifikasi tertentu.
     *
     * Kompetensi HANYA dari user_retribution_assignments level klasifikasi
     * yang aktif. Metadata user TIDAK dipakai (metadata petugas kosong di
     * produksi, jadi tidak bisa diandalkan).
     */
    public function kandidatPetugas(int $classificationId)
    {
        $userIds = DB::table('user_retribution_assignments')
            ->where('retribution_classification_id', $classificationId)
            ->where('is_active', true)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('role', 'petugas')
            ->where('status', 'active')
            ->get();
    }

    /**
     * Frekuensi penugasan BULAN INI per petugas (total lintas layanan).
     * Ini indikator keadilan utama: yang paling jarang ditugaskan didahulukan.
     *
     * PENTING: petugas dengan frekuensi 0 WAJIB tetap ada di hasil (bukan
     * dihilangkan) supaya bisa ditampilkan di UI — merekalah yang didahulukan.
     * 0 berarti "belum pernah ditugaskan bulan ini", bukan "tidak layak".
     */
    public function frekuensiBulanIni(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $awalBulan = now()->startOfMonth();

        $rows = DB::table('service_task_assignment_logs')
            ->select('to_user_id', DB::raw('count(*) as n'))
            ->whereIn('to_user_id', $userIds)
            ->whereIn('event', [self::EVENT_ASSIGNED, self::EVENT_REASSIGNED])
            ->where('created_at', '>=', $awalBulan)
            ->groupBy('to_user_id')
            ->pluck('n', 'to_user_id');

        $out = [];
        foreach ($userIds as $id) {
            // ?? 0 -> petugas tanpa penugasan bulan ini tetap tampil dengan nilai 0.
            $out[(int) $id] = (int) ($rows[$id] ?? 0);
        }

        return $out;
    }

    /** Beban penugasan aktif saat ini per petugas (total lintas layanan). */
    public function bebanAktif(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = DB::table('service_task_assignments')
            ->select('assigned_to_user_id', DB::raw('count(*) as n'))
            ->whereIn('assigned_to_user_id', $userIds)
            ->whereIn('status', self::STATUS_AKTIF)
            ->groupBy('assigned_to_user_id')
            ->pluck('n', 'assigned_to_user_id');

        $out = [];
        foreach ($userIds as $id) {
            $out[(int) $id] = (int) ($rows[$id] ?? 0);
        }

        return $out;
    }

    /** Domain wilayah dari sebuah klasifikasi (berbasis layanan, bukan lokasi). */
    public function domainUntukKlasifikasi(int $classificationId): ?string
    {
        foreach (TaxDomainHelper::DOMAIN_CONFIG as $domain => $cfg) {
            if (in_array($classificationId, $cfg['classification_ids'] ?? [], true)) {
                return $domain;
            }
        }

        return null;
    }

    /**
     * Daftar SEMUA layanan di domain + petugas yang memegangnya + penanda kosong.
     *
     * Dipakai halaman master penugasan Kabid (tampilan berbasis layanan).
     *
     * ARAHAN PENGELOLA 24 Sep 2026: "yang kosong tetap tampilkan di semua layanan".
     * Jadi SELURUH klasifikasi domain WAJIB muncul — tidak ada yang dilewati —
     * meski belum ada satu pun petugas yang memegangnya. Baris tanpa petugas
     * ditandai `ada_petugas: false` dan masuk `perlu_penetapan: true` supaya
     * Kabid langsung tahu layanan mana yang masih menggantung.
     *
     * Menyembunyikan layanan kosong berbahaya: Kabid tidak akan pernah tahu ada
     * layanan tanpa PIC, dan objek masuk ke layanan itu akan selalu jatuh ke
     * eskalasi tanpa sebab yang terlihat.
     *
     * @param  string  $taxDomain  batasi ke layanan domain ini; 'all' = semua
     * @return array<int, array{
     *     classification_id: int,
     *     kode: string,
     *     nama: string,
     *     tax_domain: string|null,
     *     ada_petugas: bool,
     *     perlu_penetapan: bool,
     *     jumlah_petugas: int,
     *     petugas: array<int, array{user_id:int,nama:string,email:string}>
     * }>
     */
    public function daftarLayananDenganPetugas(string $taxDomain = TaxDomainHelper::DOMAIN_ALL): array
    {
        $idKlasifikasi = $this->klasifikasiDomain($taxDomain);

        // Perhatian: klasifikasiDomain('all') mengembalikan NULL (artinya "semua"),
        // tetapi di sini null TIDAK boleh diterjemahkan menjadi "tanpa filter" —
        // itu akan membocorkan seluruh klasifikasi sistem (termasuk wisata, pasar,
        // perizinan) ke halaman penugasan pajak daerah.
        //
        // Untuk domain 'all', gabungkan hanya domain pajak daerah yang dikenal
        // (wilayah_1 + wilayah_2). Kalau nanti ada wilayah_3, tambahkan di sini.
        if ($idKlasifikasi === null) {
            $idKlasifikasi = [];
            foreach (TaxDomainHelper::DOMAIN_CONFIG as $cfg) {
                foreach ($cfg['classification_ids'] ?? [] as $cid) {
                    $idKlasifikasi[] = $cid;
                }
            }
            $idKlasifikasi = array_values(array_unique($idKlasifikasi));
        }

        if (empty($idKlasifikasi)) {
            return [];
        }

        $layanan = DB::table('retribution_classifications')
            ->whereIn('id', $idKlasifikasi)
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        if ($layanan->isEmpty()) {
            return [];
        }

        $idLayanan = $layanan->pluck('id')->map(fn ($v) => (int) $v)->all();

        $barisPetugas = DB::table('user_retribution_assignments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->whereIn('a.retribution_classification_id', $idLayanan)
            ->where('a.is_active', true)
            ->where('u.role', 'petugas')
            ->where('u.status', 'active')
            ->select('a.retribution_classification_id', 'u.id', 'u.name', 'u.email')
            ->orderBy('u.name')
            ->get();

        // Frekuensi & beban semua petugas yang terlibat, sekali query.
        $semuaUserId = $barisPetugas->pluck('id')->unique()->map(fn ($v) => (int) $v)->values()->all();
        $frekuensi = $this->frekuensiBulanIni($semuaUserId);
        $beban = $this->bebanAktif($semuaUserId);

        $peta = [];
        foreach ($barisPetugas as $b) {
            $uid = (int) $b->id;
            $peta[(int) $b->retribution_classification_id][] = [
                'user_id' => $uid,
                'nama' => $b->name,
                'email' => $b->email,
                'frekuensi_bulan_ini' => $frekuensi[$uid] ?? 0,
                'beban_aktif' => $beban[$uid] ?? 0,
            ];
        }

        $out = [];
        foreach ($layanan as $l) {
            $cid = (int) $l->id;
            $petugas = $peta[$cid] ?? [];

            $out[] = [
                'classification_id' => $cid,
                'kode' => $l->code ?? '-',
                'nama' => $l->name ?? '-',
                'tax_domain' => $this->domainUntukKlasifikasi($cid),
                // Layanan tanpa petugas TETAP muncul di sini.
                'ada_petugas' => count($petugas) > 0,
                'perlu_penetapan' => count($petugas) === 0,
                'jumlah_petugas' => count($petugas),
                'petugas' => $petugas,
            ];
        }

        return $out;
    }

    /**
     * Daftar petugas + layanan yang dipegang + frekuensi bulan ini + beban aktif.
     *
     * Dipakai UI Kabid untuk halaman master penugasan.
     *
     * CATATAN PENTING (arahan pengelola 24 Sep 2026):
     * Petugas dengan frekuensi 0 TETAP DITAMPILKAN dengan nilai 0 — tidak
     * dikosongkan dan tidak disembunyikan. Nilai 0 justru menandakan petugas
     * tersebut paling jarang ditugaskan bulan ini, sehingga menjadi prioritas
     * pada penugasan berikutnya. Menyembunyikan mereka menghapus informasi
     * paling relevan bagi Kabid.
     *
     * @param  string  $taxDomain  batasi ke layanan domain ini; 'all' = semua
     * @return array<int, array{
     *     user_id: int,
     *     nama: string,
     *     email: string,
     *     layanan: array<int, array{id:int,kode:string,nama:string}>,
     *     jumlah_layanan: int,
     *     frekuensi_bulan_ini: int,
     *     beban_aktif: int,
     *     prioritas: bool
     * }>
     */
    public function daftarPetugasDenganBeban(string $taxDomain = TaxDomainHelper::DOMAIN_ALL): array
    {
        $semuaKlasifikasi = $this->klasifikasiDomain($taxDomain);

        $rows = DB::table('user_retribution_assignments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->whereNotNull('a.retribution_classification_id')
            ->where('a.is_active', true)
            ->where('u.role', 'petugas')
            ->where('u.status', 'active')
            ->when($semuaKlasifikasi !== null, fn ($q) => $q->whereIn('a.retribution_classification_id', $semuaKlasifikasi))
            ->select('u.id', 'u.name', 'u.email', 'a.retribution_classification_id')
            ->orderBy('u.name')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $perUser = [];
        foreach ($rows as $r) {
            $perUser[(int) $r->id]['nama'] = $r->name;
            $perUser[(int) $r->id]['email'] = $r->email;
            $perUser[(int) $r->id]['layanan'][] = (int) $r->retribution_classification_id;
        }

        $userIds = array_keys($perUser);

        // Keduanya mengembalikan 0 untuk petugas tanpa penugasan -> tetap tampil.
        $frekuensi = $this->frekuensiBulanIni($userIds);
        $beban = $this->bebanAktif($userIds);

        $petaKlasifikasi = DB::table('retribution_classifications')
            ->whereIn('id', array_unique(array_merge(...array_column($perUser, 'layanan'))))
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        // Frekuensi terkecil -> prioritas tertinggi.
        $minFrek = min(array_values($frekuensi));

        $out = [];
        foreach ($perUser as $userId => $info) {
            $layanan = [];
            foreach (array_unique($info['layanan']) as $cid) {
                $c = $petaKlasifikasi[$cid] ?? null;
                $layanan[] = [
                    'id' => $cid,
                    'kode' => $c->code ?? '-',
                    'nama' => $c->name ?? '-',
                ];
            }

            $out[] = [
                'user_id' => $userId,
                'nama' => $info['nama'],
                'email' => $info['email'],
                'layanan' => $layanan,
                'jumlah_layanan' => count($layanan),
                'frekuensi_bulan_ini' => $frekuensi[$userId] ?? 0,
                'beban_aktif' => $beban[$userId] ?? 0,
                'prioritas' => ($frekuensi[$userId] ?? 0) === $minFrek,
            ];
        }

        // Urutkan dari yang paling jarang ditugaskan (paling adil didahulukan).
        usort($out, fn ($a, $b) => [
            $a['frekuensi_bulan_ini'], $a['beban_aktif'], $a['user_id'],
        ] <=> [
            $b['frekuensi_bulan_ini'], $b['beban_aktif'], $b['user_id'],
        ]);

        return $out;
    }

    /**
     * Daftar id klasifikasi dalam domain. null = semua (domain 'all').
     */
    public function klasifikasiDomain(string $taxDomain): ?array
    {
        if ($taxDomain === TaxDomainHelper::DOMAIN_ALL) {
            return null;
        }

        return TaxDomainHelper::DOMAIN_CONFIG[$taxDomain]['classification_ids'] ?? [];
    }

    /** Tulis satu baris ke tabel audit penugasan. */
    public function catatLog(array $data): void
    {
        DB::table('service_task_assignment_logs')->insert([
            'assignment_id' => $data['assignment_id'],
            'event' => $data['event'],
            'actor_user_id' => $data['actor_user_id'] ?? null,
            'from_user_id' => $data['from_user_id'] ?? null,
            'to_user_id' => $data['to_user_id'] ?? null,
            'stage_from' => $data['stage_from'] ?? null,
            'stage_to' => $data['stage_to'] ?? null,
            'status_from' => $data['status_from'] ?? null,
            'status_to' => $data['status_to'] ?? null,
            'reason' => $data['reason'] ?? null,
            'meta' => isset($data['meta']) ? json_encode($data['meta']) : null,
            'created_at' => now(),
        ]);
    }
}
