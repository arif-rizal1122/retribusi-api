<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AutoAssignService;
use App\Services\LapanganVerifikasiService;
use App\Services\OfficialDocumentService;
use App\Services\TTEService;
use App\Services\SlaTrackerService;
use App\Support\ServiceAccessMatrix;
use App\Support\TaxDomainHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * KabidPerObjekController — aksi KEPALA BIDANG atas satu objek pekerjaan.
 *
 * Lanjutan rantai yang sudah ada:
 *   petugas mengajukan  -> status MENUNGGU_VERIFIKASI
 *   Kasubid memverifikasi -> status TERVERIFIKASI   (LapanganVerifikasiService)
 *   KABID mengonfirmasi  -> status APPROVED          <-- DI SINI
 *
 * Kewenangan Kabid di controller ini:
 *   1. KONFIRMASI  — menutup pekerjaan yang sudah diverifikasi Kasubid.
 *   2. GANTI PIC   — mengalihkan tugas ke petugas lain, WAJIB beralasan.
 *   3. LIHAT KANDIDAT — daftar petugas kompeten + beban, dasar memilih PIC baru.
 *
 * Aturan yang dijaga:
 *   - Kabid tidak bisa mengonfirmasi pekerjaan yang BELUM diverifikasi Kasubid.
 *     Ini yang membuat pemisahan maker-checker bermakna: Kabid menutup atas
 *     dasar pemeriksaan orang lain, bukan atas klaim petugas.
 *   - Ganti PIC wajib beralasan (jejak di tabel audit).
 *   - PIC baru harus punya penetapan master aktif untuk layanan objek itu.
 *   - Wilayah dijaga: lintas wilayah -> 403.
 */
class KabidPerObjekController extends Controller
{
    public function __construct(
        private AutoAssignService $autoAssign,
        private LapanganVerifikasiService $lapangan,
        private OfficialDocumentService $dokumen,
        private TTEService $tteService,
    ) {
    }

    // ==================================================================
    // 1) DAFTAR OBJEK UNTUK KABID
    // ==================================================================

    /**
     * GET /api/bidang-pajak/kabid/objek
     *
     * Daftar pekerjaan yang menjadi tanggung jawab Kabid, disaring wilayah.
     * Bawaan menampilkan yang SUDAH diverifikasi Kasubid (menunggu konfirmasi)
     * plus yang masih berjalan — supaya Kabid punya gambaran utuh.
     */
    public function daftar(Request $request)
    {
        $c = $this->ctx($request);
        $status = $request->query('status');

        $q = DB::table('service_task_assignments as sta')
            ->leftJoin('users as p', 'p.id', '=', 'sta.assigned_to_user_id')
            ->select(
                'sta.id', 'sta.reference_type', 'sta.reference_id', 'sta.stage',
                'sta.tax_domain', 'sta.tax_sector_code', 'sta.status',
                'sta.sla_deadline', 'sta.escalation_alert_at', 'sta.escalated_at',
                'sta.started_at', 'sta.completed_at', 'sta.updated_at',
                'sta.rejection_reason', 'p.name as petugas_nama', 'p.id as petugas_id'
            )
            ->orderByRaw("FIELD(sta.status, 'TERVERIFIKASI','MENUNGGU_VERIFIKASI','REVISI','IN_PROGRESS','PENDING','ESCALATED','APPROVED','REJECTED','CANCELLED')")
            ->orderBy('sta.updated_at');

        if ($status) {
            $q->where('sta.status', $status);
        }

        $this->batasiDomain($q, $c);

        $rows = $q->limit(200)->get();

        // Ringkasan supaya Kabid tahu beban tanpa harus membuka satu-satu.
        $semua = $rows;
        $ringkas = [
            'menunggu_konfirmasi' => $semua->where('status', SlaTrackerService::STATUS_TERVERIFIKASI)->count(),
            'menunggu_verifikasi' => $semua->where('status', LapanganVerifikasiService::AWAITING)->count(),
            'revisi'              => $semua->where('status', SlaTrackerService::STATUS_REVISI)->count(),
            'berjalan'            => $semua->whereIn('status', [
                SlaTrackerService::STATUS_PENDING,
                SlaTrackerService::STATUS_IN_PROGRESS,
            ])->count(),
            'lewat_tenggat'       => $semua->filter(fn ($r) => $r->sla_deadline !== null
                && ! in_array($r->status, ['APPROVED', 'REJECTED', 'CANCELLED'], true)
                && \Illuminate\Support\Carbon::parse($r->sla_deadline)->isPast())->count(),
        ];

        return response()->json([
            'peran'    => $c['profile']['group'] ?? null,
            'domain'   => $c['domain'],
            'layanan'  => $c['services'],
            'ringkasan' => $ringkas,
            'jumlah'   => $rows->count(),
            'data'     => $rows,
        ]);
    }

    /**
     * GET /api/bidang-pajak/kabid/objek/{id}
     * Detail satu objek + riwayat lengkap + kandidat pengganti.
     */
    public function detail(Request $request, int $id)
    {
        $c = $this->ctx($request);

        $hasil = $this->lapangan->detail($c['user'], $id);
        if (! $hasil) {
            return response()->json([
                'message' => 'Objek pekerjaan tidak ditemukan atau di luar wilayah Anda.',
            ], 404);
        }

        $tugas = $hasil['tugas'];

        // Kandidat pengganti: hanya petugas yang ditetapkan Kabid untuk
        // layanan objek ini. Kabid boleh mengganti, tapi tidak boleh
        // menugaskan orang di luar penetapan — itu akan merusak keadilan
        // pembagian yang sudah ia sendiri tetapkan.
        $kandidat = [];
        $classificationId = $this->klasifikasiDariObjek($tugas);
        if ($classificationId) {
            $kandidat = $this->saringKandidat($c['domain'], (int) $classificationId, $tugas->assigned_to_user_id);
        }

        return response()->json([
            'domain'   => $c['domain'],
            'tugas'    => $tugas,
            'riwayat'  => $hasil['riwayat'],
            'klasifikasi_id' => $classificationId,
            'kandidat_pengganti' => $kandidat,
            'aksi_tersedia' => $this->aksiTersedia($tugas->status),
        ]);
    }

    // ==================================================================
    // 2) KONFIRMASI (menutup pekerjaan) — kewenangan Kabid
    // ==================================================================

    /**
     * POST /api/bidang-pajak/kabid/objek/{id}/konfirmasi
     *
     * Kabid menutup pekerjaan yang SUDAH diverifikasi Kasubid.
     * Tidak boleh dari MENUNGGU_VERIFIKASI — itu melompati pemeriksaan Kasubid.
     */
    public function konfirmasi(Request $request, int $id)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'keputusan' => 'required|in:setujui,tolak',
            'alasan'    => 'nullable|string|max:2000',
        ]);

        if (! $this->dalamWilayah($c, $id)) {
            return response()->json([
                'message' => 'Objek pekerjaan ini berada di luar wilayah penugasan Anda.',
            ], 403);
        }

        $row = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Objek pekerjaan tidak ditemukan.'], 404);
        }

        // Penjaga inti: hanya yang sudah TERVERIFIKASI Kasubid.
        if ($row->status !== SlaTrackerService::STATUS_TERVERIFIKASI) {
            return response()->json([
                'message' => 'Pekerjaan ini belum diverifikasi Kasubid '
                    . 'atau sudah diputuskan (status: ' . $row->status . ').',
                'syarat'  => 'Status harus TERVERIFIKASI sebelum Kabid mengonfirmasi.',
                'tindakan' => $row->status === LapanganVerifikasiService::AWAITING
                    ? 'Menunggu verifikasi Kasubid lebih dulu.'
                    : 'Tidak ada tindakan yang diperlukan.',
            ], 409);
        }

        $setuju = $validated['keputusan'] === 'setujui';

        if (! $setuju && trim((string) ($validated['alasan'] ?? '')) === '') {
            return response()->json([
                'message' => 'Alasan wajib diisi bila menolak hasil yang sudah diverifikasi.',
            ], 422);
        }

        DB::transaction(function () use ($row, $c, $setuju, $validated) {
            DB::table('service_task_assignments')->where('id', $row->id)->update([
                'status'           => $setuju
                    ? SlaTrackerService::STATUS_APPROVED
                    : SlaTrackerService::STATUS_REJECTED,
                'completed_at'     => now(),
                'rejection_reason' => $setuju ? null : ($validated['alasan'] ?? null),
                'notes'            => trim((string) $row->notes
                    . "\n[Kabid: " . ($setuju ? 'DIKONFIRMASI SELESAI' : 'DITOLAK')
                    . ' ' . now()->format('d/m/Y H:i') . ']'
                    . (! $setuju && ! empty($validated['alasan']) ? "\nAlasan: " . $validated['alasan'] : '')),
                'updated_at'       => now(),
            ]);

            $this->autoAssign->catatLog([
                'assignment_id' => $row->id,
                'event'         => $setuju ? 'CONFIRMED' : 'REJECTED_BY_KABID',
                'actor_user_id' => $c['user']->id,
                'from_user_id'  => $row->assigned_to_user_id,
                'to_user_id'    => $row->assigned_to_user_id,
                'stage_from'    => $row->stage,
                'stage_to'      => $row->stage,
                'status_from'   => $row->status,
                'status_to'     => $setuju
                    ? SlaTrackerService::STATUS_APPROVED
                    : SlaTrackerService::STATUS_REJECTED,
                'reason'        => $validated['alasan'] ?? null,
                'meta'          => ['mode' => 'kabid_konfirmasi'],
            ]);
        });

        return response()->json([
            'message' => $setuju
                ? 'Pekerjaan dikonfirmasi selesai (L3).'
                : 'Hasil verifikasi ditolak Kabid — pekerjaan dikembalikan.',
            'id'      => $id,
            'status'  => $setuju ? SlaTrackerService::STATUS_APPROVED : SlaTrackerService::STATUS_REJECTED,
            'oleh'    => $c['user']->name,
        ]);
    }

    // ==================================================================
    // 3) GANTI PETUGAS — kewenangan Kabid
    // ==================================================================

    /**
     * POST /api/bidang-pajak/kabid/objek/{id}/ganti-petugas
     *
     * Mengalihkan PIC. Jejak ke tabel audit (di dalam service), bukan notes.
     * Petugas yang diganti TIDAK diberi hak menolak — itu keputusan pengelola;
     * yang ada adalah penanda "tidak responsif" dari sisi Kabid.
     */
    public function gantiPetugas(Request $request, int $id)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'petugas_id'  => 'required|integer|exists:users,id',
            'alasan'      => 'required|string|min:5|max:2000',
            'tidak_responsif' => 'nullable|boolean',
        ]);

        if (! $this->dalamWilayah($c, $id)) {
            return response()->json([
                'message' => 'Objek pekerjaan ini berada di luar wilayah penugasan Anda.',
            ], 403);
        }

        $row = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Objek pekerjaan tidak ditemukan.'], 404);
        }

        // Pekerjaan yang sudah tuntas tidak bisa dialihkan — tidak ada lagi
        // yang dikerjakan, dan mengalihkannya akan mengaburkan riwayat.
        if (in_array($row->status, [
            SlaTrackerService::STATUS_APPROVED,
            SlaTrackerService::STATUS_REJECTED,
            'CANCELLED',
        ], true)) {
            return response()->json([
                'message' => 'Pekerjaan ini sudah tuntas (status: ' . $row->status . '). '
                    . 'Pengalihan tidak diperlukan.',
            ], 409);
        }

        if ((int) $row->assigned_to_user_id === (int) $validated['petugas_id']) {
            return response()->json([
                'message' => 'Petugas pengganti sama dengan PIC saat ini.',
            ], 422);
        }

        $baru = \App\Models\User::find($validated['petugas_id']);
        if (! $baru) {
            return response()->json(['message' => 'Petugas pengganti tidak ditemukan.'], 404);
        }

        if ($baru->role !== 'petugas') {
            return response()->json([
                'message' => 'Pengganti harus berperan petugas lapangan.',
                'role_pengganti' => $baru->role,
            ], 422);
        }

        if ((int) $baru->opd_id !== (int) $c['user']->opd_id) {
            return response()->json([
                'message' => 'Petugas pengganti harus pegawai pada OPD yang sama.',
            ], 422);
        }

        if ($baru->status !== 'active') {
            return response()->json([
                'message' => 'Petugas pengganti tidak berstatus aktif.',
            ], 422);
        }

        // Pengganti WAJIB punya penetapan master aktif untuk layanan objek ini.
        // Tanpa syarat ini, ganti-petugas jadi celah untuk menugaskan orang
        // di luar pembagian yang sudah Kabid tetapkan sendiri.
        $classificationId = $this->klasifikasiDariObjek($row);

        if ($classificationId) {
            $kompeten = DB::table('user_retribution_assignments')
                ->where('user_id', $baru->id)
                ->where('retribution_classification_id', (int) $classificationId)
                ->where('is_active', true)
                ->exists();

            if (! $kompeten) {
                return response()->json([
                    'message' => 'Petugas pengganti belum ditetapkan untuk layanan objek ini. '
                        . 'Tetapkan lebih dulu lewat POST /api/bidang-pajak/petugas-layanan/tetapkan.',
                    'petugas_id'        => $baru->id,
                    'classification_id' => (int) $classificationId,
                ], 422);
            }
        }

        try {
            $hasil = $this->autoAssign->gantiPetugas(
                $id,
                (int) $validated['petugas_id'],
                (int) $c['user']->id,
                $validated['alasan']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Penanda "tidak responsif" pada petugas LAMA — bukan hukuman, hanya
        // catatan supaya pola berulang terlihat saat Kabid menilai beban.
        if (! empty($validated['tidak_responsif']) && $row->assigned_to_user_id) {
            DB::table('service_task_assignment_logs')->insert([
                'assignment_id' => $id,
                'event'         => 'MARKED_UNRESPONSIVE',
                'actor_user_id' => $c['user']->id,
                'to_user_id'    => $row->assigned_to_user_id,
                'reason'        => $validated['alasan'],
                'meta'          => json_encode(['mode' => 'kabid_ganti_petugas']),
                'created_at'    => now(),
            ]);
        }

        return response()->json([
            'message'     => 'Petugas pengganti ditetapkan.',
            'id'          => $id,
            'dari'        => $row->assigned_to_user_id,
            'ke'          => (int) $validated['petugas_id'],
            'nama_pengganti' => $baru->name,
            'alasan'      => $validated['alasan'],
            'ditandai_tidak_responsif' => (bool) ($validated['tidak_responsif'] ?? false),
        ]);
    }

    /**
     * GET /api/bidang-pajak/kabid/objek/{id}/kandidat
     * Daftar petugas kompeten + beban, dasar memilih pengganti.
     */
    public function kandidat(Request $request, int $id)
    {
        $c = $this->ctx($request);

        if (! $this->dalamWilayah($c, $id)) {
            return response()->json([
                'message' => 'Objek pekerjaan ini berada di luar wilayah penugasan Anda.',
            ], 403);
        }

        $row = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Objek pekerjaan tidak ditemukan.'], 404);
        }

        $classificationId = $this->klasifikasiDariObjek($row);
        if (! $classificationId) {
            return response()->json([
                'message' => 'Klasifikasi layanan objek ini tidak dapat ditentukan.',
            ], 422);
        }

        $kandidat = $this->saringKandidat(
            $c['domain'],
            (int) $classificationId,
            $row->assigned_to_user_id
        );

        return response()->json([
            'id'               => $id,
            'klasifikasi_id'   => (int) $classificationId,
            'pic_sekarang'     => $row->assigned_to_user_id,
            'jumlah_kandidat'  => count($kandidat),
            'data'             => $kandidat,
        ]);
    }

    // ==================================================================
    // INTERNAL
    // ==================================================================

    /** Profil akses Kabid yang sedang login. */
    private function ctx(Request $request): array
    {
        $user = $request->user();
        $role = $user->role ?? null;

        return [
            'user'     => $user,
            'role'     => $role,
            'profile'  => ServiceAccessMatrix::profileFor($role),
            'services' => ServiceAccessMatrix::servicesForRole($role),
            'domain'   => TaxDomainHelper::getDomain($user),
            'typeIds'  => TaxDomainHelper::getRetributionTypeIds(TaxDomainHelper::getDomain($user)),
        ];
    }

    /** Batasi kueri ke wilayah Kabid. Fail-closed bila domain kosong. */
    private function batasiDomain($query, array $c): void
    {
        if ($c['domain'] === TaxDomainHelper::DOMAIN_ALL) {
            return;
        }

        if (empty($c['domain']) || empty($c['typeIds'])) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function ($w) use ($c) {
            $w->where('sta.tax_domain', $c['domain'])
              ->orWhere(function ($w2) use ($c) {
                  $w2->whereNull('sta.tax_domain')
                     ->where('sta.reference_type', 'tax_object')
                     ->whereIn('sta.reference_id', function ($sub) use ($c) {
                         $sub->select('id')->from('tax_objects')
                             ->whereIn('retribution_classification_id', $c['typeIds']);
                     });
              });
        });
    }

    /** Apakah tugas ini di dalam wilayah Kabid? Fail-closed. */
    private function dalamWilayah(array $c, int $id): bool
    {
        if ($c['domain'] === TaxDomainHelper::DOMAIN_ALL) {
            return true;
        }

        if (empty($c['domain']) || empty($c['typeIds'])) {
            return false;
        }

        $row = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $row) {
            return false;
        }

        if ($row->tax_domain !== null && $row->tax_domain !== '') {
            return $row->tax_domain === $c['domain'];
        }

        $cls = $this->klasifikasiDariObjek($row);

        return $cls !== null
            && in_array((int) $cls, array_map('intval', $c['typeIds']), true);
    }

    /**
     * Klasifikasi layanan sebuah tugas.
     *
     * PENTING: `tax_sector_code` kadang berisi KODE klasifikasi (dari
     * autoAssign) dan kadang berisi NAMA layanan (dari buatPenugasan).
     * Keduanya harus tertangani — kalau hanya salah satu, pengecekan
     * kompetensi petugas lolos tanpa pemeriksaan.
     */
    private function klasifikasiDariObjek(object $row): ?int
    {
        // 1. Jalur paling andal: dari objek pajaknya.
        if ($row->reference_type === 'tax_object' && $row->reference_id) {
            $cls = DB::table('tax_objects')
                ->where('id', $row->reference_id)
                ->value('retribution_classification_id');

            if ($cls !== null) {
                return (int) $cls;
            }
        }

        // 2. Cadangan: tax_sector_code berisi kode numerik.
        $sector = trim((string) $row->tax_sector_code);
        if ($sector !== '' && ctype_digit($sector)) {
            return (int) $sector;
        }

        // 3. Cadangan: tax_sector_code berisi nama/kode layanan.
        if ($sector !== '') {
            $kode = DB::table('retribution_classifications')
                ->where('code', $sector)
                ->value('id');

            return $kode !== null ? (int) $kode : null;
        }

        return null;
    }

    /**
     * Petugas kompeten untuk satu klasifikasi, di luar PIC saat ini.
     *
     * `daftarPetugasDenganBeban()` mengembalikan key `layanan` berisi array
     * objek {id, kode, nama} — BUKAN `classification_ids`. Salah membaca bentuk
     * ini membuat penyaringan lolos tanpa hasil, dan daftar kandidat tampak
     * kosong padahal petugasnya ada.
     */
    private function saringKandidat(string $domain, int $classificationId, ?int $picSekarang): array
    {
        $semua = $this->autoAssign->daftarPetugasDenganBeban($domain);

        return array_values(array_filter($semua, function ($k) use ($classificationId, $picSekarang) {
            $ids = array_map(
                fn ($l) => (int) ($l['id'] ?? 0),
                $k['layanan'] ?? []
            );

            return in_array($classificationId, $ids, true)
                && (int) ($k['user_id'] ?? 0) !== (int) $picSekarang;
        }));
    }

    // ==================================================================
    // SURAT PENUGASAN (Surat Tugas) — ber-TTE Kabid
    // ==================================================================

    /**
     * GET /api/bidang-pajak/kabid/objek/{id}/surat-penugasan
     *
     * Pratinjau surat tugas sebelum ditandatangani. Belum mengubah apa pun —
     * pejabat perlu membaca dulu sebelum meneken. Nomor surat bersifat STABIL
     * (tidak berubah tiap kali dibuka), jadi aman dipanggil berkali-kali.
     */
    public function suratPenugasan(Request $request, int $id)
    {
        $c = $this->ctx($request);

        if (! $this->dalamWilayah($c, $id)) {
            return response()->json([
                'message' => 'Penugasan ini berada di luar wilayah Anda.',
            ], 403);
        }

        try {
            $surat = $this->dokumen->generateSuratPenugasan($id, $c['user']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Sertakan status TTE terkini supaya UI tahu apakah sudah diteken.
        $dok = DB::table('signed_documents')
            ->where('document_type', 'surat_penugasan')
            ->where('document_id', $id)
            ->first(['id', 'document_number', 'status', 'signed_by', 'signer_name', 'signed_at', 'verification_url', 'sha256_checksum']);

        return response()->json([
            'message'  => 'Pratinjau surat tugas. Belum ditandatangani.',
            'surat'    => $surat,
            'dokumen'  => $dok,
            'aksi'     => $dok && $dok->status === 'SIGNED'
                ? ['unduh', 'verifikasi']
                : ['tte_pejabat'],
        ]);
    }

    /**
     * POST /api/bidang-pajak/kabid/objek/{id}/surat-penugasan/tte
     *
     * Tandatangani surat tugas secara elektronik (BSrE).
     *
     * Passphrase TIDAK PERNAH disimpan, tidak di-log, dan tidak dikembalikan
     * dalam respons apa pun. Ia hanya diteruskan sekali ke TTEService lalu
     * dilepas dari memori request.
     */
    public function tteSuratPenugasan(Request $request, int $id)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'passphrase' => 'required|string|min:6|max:255',
        ]);

        if (! $this->dalamWilayah($c, $id)) {
            return response()->json([
                'message' => 'Penugasan ini berada di luar wilayah Anda.',
            ], 403);
        }

        $dok = DB::table('signed_documents')
            ->where('document_type', 'surat_penugasan')
            ->where('document_id', $id)
            ->first();

        if (! $dok) {
            return response()->json([
                'message'  => 'Surat tugas belum diterbitkan. Buka pratinjau lebih dulu.',
                'tindakan' => 'Panggil GET /surat-penugasan untuk menerbitkan draf.',
            ], 409);
        }

        if ($dok->status === 'SIGNED') {
            return response()->json([
                'message' => 'Surat tugas ini sudah ditandatangani.',
                'dokumen' => $dok,
            ], 409);
        }

        // Pekerjaan harus sudah punya petugas — tidak masuk akal menugaskan
        // pekerjaan kepada nobody lalu diteken pejabat.
        $sta = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $sta) {
            return response()->json(['message' => 'Penugasan tidak ditemukan.'], 404);
        }
        if (! $sta->assigned_to_user_id) {
            return response()->json([
                'message'  => 'Penugasan belum punya petugas (PIC).',
                'tindakan' => 'Tetapkan petugas lebih dulu, baru terbitkan surat tugas.',
            ], 422);
        }

        // Jejak: catat percobaan TTE tanpa menyimpan passphrase.
        DB::table('tte_logs')->insert([
            'document_number'  => $dok->document_number,
            'endpoint'         => 'surat-penugasan/tte',
            'request_payload'  => json_encode([
                'assignment_id' => $id,
                'pejabat_id'    => $c['user']->id,
                'passphrase'    => '[REDACTED]',
            ]),
            // response_code NOT NULL di skema; 0 = "panggilan belum dijawab".
            'response_code'    => 0,
            'response_body'    => null,
            // duration_ms juga NOT NULL di skema.
            'duration_ms'      => 0,
            'status'           => 'DIMULAI',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // TTEService::signDocument() selalu MENYISIPKAN baris baru dengan nomor
        // dokumen yang kita berikan. Karena nomor itu sudah dipakai baris draf
        // (kolom document_number UNIQUE), kita lepas dulu nomornya dari baris
        // draf dengan menandainya sebagai arsip bernomor unik, baru panggil TTE.
        // Setelah berhasil, data draf dipindahkan ke baris hasil TTE.
        $nomorArsip = $dok->document_number . '#DRAFT-' . $dok->id;

        try {
            DB::table('signed_documents')->where('id', $dok->id)->update([
                'document_number' => $nomorArsip,
                'updated_at'      => now(),
            ]);

            // Passphrase dipakai MEMBUKA sertifikat BSrE; tidak disimpan, tidak
            // di-log, dan dilepas dari memori setelah panggilan ini.
            $docModel = \App\Models\SignedDocument::find($dok->id);

            $hasil = $this->tteService->signDocument(
                $docModel,
                $c['user'],
                'Surat Tugas penugasan #' . $id,
                'BSRE_PERSONAL',
                $dok->document_number,
                $c['user']->opd_id
            );

            // Baris draf TIDAK memakai nomor aslinya lagi — nomor itu kini milik
            // baris hasil TTE (kolom document_number UNIQUE). Draf tetap tersimpan
            // sebagai arsip bernomor unik "#DRAFT-<id>" dengan status SUPERSEDED,
            // supaya jejak penerbitannya tidak hilang.
            DB::table('signed_documents')->where('id', $dok->id)->update([
                'status'     => 'SUPERSEDED',
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // response_body bertipe TEXT dengan batas ketat di sebagian
            // deployment, dan pesan QueryException bisa sangat panjang. Yang
            // penting bagi audit adalah STATUS-nya; rincian error dibiarkan di
            // laravel.log (sudah otomatis dicatat oleh handler Laravel).
            DB::table('tte_logs')
                ->where('document_number', $dok->document_number)
                ->where('status', 'DIMULAI')
                ->update([
                    'status'     => 'GAGAL',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'message'  => 'Penandatanganan gagal: ' . $e->getMessage(),
                'tindakan' => 'Periksa passphrase sertifikat BSrE Anda.',
            ], 422);
        }

        // TTE membuat baris signed_documents BARU (bertipe model SignedDocument).
        // Yang kita butuhkan adalah baris DRAF surat tugas ini yang berubah
        // status menjadi SIGNED, supaya penomoran & riwayat tetap satu baris.
        // Data tanda tangan disalin dari hasil TTE, lalu baris bantu dihapus.
        DB::transaction(function () use ($dok, $hasil) {
            DB::table('signed_documents')->where('id', $dok->id)->update([
                'signature_hash'   => $hasil->signature_hash ?? null,
                'sha256_checksum'  => $hasil->sha256_checksum ?? null,
                'signed_by'        => $hasil->signed_by ?? null,
                'signer_name'      => $hasil->signer_name ?? null,
                'signer_nip'       => $hasil->signer_nip ?? null,
                'signer_role'      => $hasil->signer_role ?? null,
                'signature_type'   => $hasil->signature_type ?? 'BSRE_PERSONAL',
                'signed_at'        => $hasil->signed_at ?? now(),
                'metadata'         => is_string($hasil->metadata)
                    ? $hasil->metadata
                    : json_encode($hasil->metadata),
                'status'           => 'SIGNED',
                'updated_at'       => now(),
            ]);

            // Baris bantu dari TTEService tidak diperlukan lagi — sudah disalin.
            // Ditandai, bukan dihapus, supaya jejak tetap ada (aturan append-only).
            if (($hasil->id ?? null) && (int) $hasil->id !== (int) $dok->id) {
                DB::table('signed_documents')->where('id', $hasil->id)->update([
                    'status'     => 'SUPERSEDED',
                    'updated_at' => now(),
                ]);
            }
        });

        DB::table('tte_logs')
            ->where('document_number', $dok->document_number)
            ->where('status', 'DIMULAI')
            ->update(['status' => 'BERHASIL', 'response_code' => 200, 'updated_at' => now()]);

        // Baris hasil TTE-lah yang kini memegang nomor asli (baris draf jadi arsip
        // bernomor "#DRAFT-<id>"). Kembalikan yang benar supaya klien tidak
        // menerima nomor arsip.
        $dokFinal = DB::table('signed_documents')
            ->where('document_number', $dok->document_number)
            ->where('status', 'SIGNED')
            ->first();

        return response()->json([
            'message' => 'Surat tugas berhasil ditandatangani secara elektronik.',
            'nomor'   => $dok->document_number,
            'siap_unduh' => true,
            'dokumen' => $dokFinal,
        ]);
    }

    /** Aksi yang tersedia untuk status tertentu. */
    private function aksiTersedia(string $status): array
    {
        return match ($status) {
            SlaTrackerService::STATUS_TERVERIFIKASI => ['konfirmasi', 'ganti_petugas'],
            LapanganVerifikasiService::AWAITING     => ['lihat'],
            SlaTrackerService::STATUS_REVISI        => ['ganti_petugas'],
            SlaTrackerService::STATUS_PENDING,
            SlaTrackerService::STATUS_IN_PROGRESS,
            SlaTrackerService::STATUS_ESCALATED     => ['ganti_petugas'],
            default                                 => [],
        };
    }
}
